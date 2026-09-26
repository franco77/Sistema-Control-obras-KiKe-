<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Quotes;

use App\Enums\DiscountType;
use App\Enums\MeasurementUnit;
use App\Livewire\Concerns\WithToasts;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\Quote;
use App\Models\QuoteSection;
use App\Models\Trade;
use App\Services\Quotes\QuoteCalculator;
use App\Services\Quotes\QuoteNumberGenerator;
use App\Services\Quotes\QuoteWorkflow;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Constructor de presupuestos.
 *
 * Trabaja siempre contra la base de datos (no sobre un árbol en memoria):
 * cada cambio se persiste y QuoteCalculator recalcula el documento entero.
 * Así los totales que ve el usuario son exactamente los que se guardan y
 * dos pestañas abiertas nunca muestran importes distintos.
 */
#[Layout('components.layouts.admin')]
class QuoteBuilder extends Component
{
    use WithToasts;

    public Quote $quote;

    /** Cabecera del documento. */
    public array $header = [];

    /** section_id => campos editables */
    public array $sections = [];

    /** item_id => campos editables */
    public array $items = [];

    // Buscador del catálogo ------------------------------------------------
    public string $catalogSearch = '';
    public ?int $catalogTargetSection = null;

    public function mount(?Quote $quote = null): void
    {
        if ($quote?->exists) {
            $this->authorize('update', $quote);
            $this->quote = $quote;
        } else {
            $this->authorize('create', Quote::class);
            $this->quote = $this->createDraft();
        }

        $this->loadState();
    }

    /** Crea el borrador en cuanto se abre el constructor. */
    private function createDraft(): Quote
    {
        $clientId = (int) request()->query('cliente');
        $client = Client::find($clientId) ?? Client::query()->orderBy('name')->firstOrFail();

        $quote = Quote::create([
            'number' => app(QuoteNumberGenerator::class)->next(),
            'client_id' => $client->id,
            'property_id' => request()->query('inmueble') ?: $client->properties()->value('id'),
            'title' => 'Reforma',
            'issue_date' => now()->toDateString(),
            'valid_until' => now()->addDays((int) setting('quotes.valid_days', 30))->toDateString(),
            'tax_rate' => (float) setting('company.tax_rate', 21),
            'payment_terms' => setting('quotes.payment_terms'),
            'terms' => setting('quotes.terms'),
            'created_by' => auth()->id(),
        ]);

        $quote->sections()->create(['name' => 'Capítulo 1', 'position' => 0]);

        return $quote;
    }

    private function loadState(): void
    {
        $this->quote->refresh()->load(['sections.items', 'client.properties']);

        $this->header = [
            'client_id' => $this->quote->client_id,
            'property_id' => $this->quote->property_id,
            'title' => $this->quote->title,
            'description' => (string) $this->quote->description,
            'issue_date' => $this->quote->issue_date?->toDateString(),
            'valid_until' => $this->quote->valid_until?->toDateString(),
            'estimated_duration_days' => $this->quote->estimated_duration_days,
            'tax_rate' => (float) $this->quote->tax_rate,
            'discount_type' => $this->quote->discount_type->value,
            'discount_value' => (float) $this->quote->discount_value,
            'payment_terms' => (string) $this->quote->payment_terms,
            'terms' => (string) $this->quote->terms,
            'exclusions' => (string) $this->quote->exclusions,
            'internal_notes' => (string) $this->quote->internal_notes,
        ];

        $this->sections = [];
        $this->items = [];

        foreach ($this->quote->sections as $section) {
            $this->sections[$section->id] = [
                'name' => $section->name,
                'description' => (string) $section->description,
                'trade_id' => $section->trade_id,
            ];

            foreach ($section->items as $item) {
                $this->items[$item->id] = [
                    'name' => $item->name,
                    'description' => (string) $item->description,
                    'unit' => $item->unit->value,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'unit_cost' => (float) $item->unit_cost,
                    'discount_percent' => (float) $item->discount_percent,
                    'is_optional' => (bool) $item->is_optional,
                ];
            }
        }
    }

    // ------------------------------------------------------------ cabecera
    /** Campos de cabecera que admiten null cuando se dejan en blanco. */
    private const NULLABLE_HEADER_FIELDS = [
        'property_id', 'description', 'valid_until', 'estimated_duration_days',
        'payment_terms', 'terms', 'exclusions', 'internal_notes',
    ];

    public function updatedHeader(mixed $value, string $key): void
    {
        $field = $key;

        $this->validateOnly("header.{$field}", [
            'header.client_id' => ['required', 'exists:clients,id'],
            'header.property_id' => ['nullable', 'exists:properties,id'],
            'header.title' => ['required', 'string', 'max:191'],
            'header.description' => ['nullable', 'string', 'max:5000'],
            'header.issue_date' => ['required', 'date'],
            'header.valid_until' => ['nullable', 'date', 'after_or_equal:header.issue_date'],
            'header.estimated_duration_days' => ['nullable', 'integer', 'min:1', 'max:3000'],
            'header.tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'header.discount_type' => ['required', Rule::enum(DiscountType::class)],
            'header.discount_value' => ['required', 'numeric', 'min:0'],
            'header.payment_terms' => ['nullable', 'string', 'max:2000'],
            'header.terms' => ['nullable', 'string', 'max:5000'],
            'header.exclusions' => ['nullable', 'string', 'max:5000'],
            'header.internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $changes = [$field => $this->normalizeHeaderValue($field, $this->header[$field])];

        // Al cambiar de cliente, el inmueble anterior ya no le pertenece.
        if ($field === 'client_id') {
            $this->header['property_id'] = null;
            $changes['property_id'] = null;
        }

        $this->quote->update($changes);
        $this->recalculate();
    }

    /**
     * Un campo en blanco solo se guarda como null si la columna lo admite.
     * Usar `?:` aquí convertiría un IVA del 0 % o un descuento de 0 en null
     * y reventaría contra una columna NOT NULL.
     */
    private function normalizeHeaderValue(string $field, mixed $value): mixed
    {
        if ($value !== '' && $value !== null) {
            return $value;
        }

        return in_array($field, self::NULLABLE_HEADER_FIELDS, true) ? null : 0;
    }

    // ------------------------------------------------------------ capítulos
    public function addSection(): void
    {
        $position = (int) $this->quote->sections()->max('position') + 1;

        $this->quote->sections()->create([
            'name' => 'Capítulo '.($position + 1),
            'position' => $position,
        ]);

        $this->loadState();
    }

    public function updatedSections(mixed $value, string $key): void
    {
        [$sectionId, $field] = explode('.', $key, 2);

        $this->quote->sections()->whereKey($sectionId)->update([$field => $value ?: null]);
    }

    public function removeSection(int $sectionId): void
    {
        $this->quote->sections()->whereKey($sectionId)->delete();
        $this->recalculate();
        $this->loadState();
        $this->toastSuccess('Capítulo eliminado.');
    }

    public function moveSection(int $sectionId, int $direction): void
    {
        $ordered = $this->quote->sections()->orderBy('position')->get();
        $index = $ordered->search(fn (QuoteSection $s) => $s->id === $sectionId);
        $target = $index + $direction;

        if ($index === false || $target < 0 || $target >= $ordered->count()) {
            return;
        }

        $ordered->splice($target, 0, [$ordered->pull($index)]);

        $ordered->values()->each(fn (QuoteSection $section, int $i) => $section->update(['position' => $i]));

        $this->loadState();
    }

    // -------------------------------------------------------------- partidas
    public function addItem(int $sectionId): void
    {
        $section = $this->quote->sections()->findOrFail($sectionId);

        $section->items()->create([
            'name' => 'Nueva partida',
            'unit' => MeasurementUnit::Unit,
            'quantity' => 1,
            'position' => (int) $section->items()->max('position') + 1,
        ]);

        $this->recalculate();
        $this->loadState();
    }

    public function addCatalogItem(int $catalogItemId): void
    {
        abort_if($this->catalogTargetSection === null, 400);

        $catalogItem = CatalogItem::findOrFail($catalogItemId);
        $section = $this->quote->sections()->findOrFail($this->catalogTargetSection);

        $section->items()->create([
            'catalog_item_id' => $catalogItem->id,
            'trade_id' => $catalogItem->trade_id,
            'code' => $catalogItem->code,
            'name' => $catalogItem->name,
            'description' => $catalogItem->description,
            'unit' => $catalogItem->unit,
            'quantity' => $catalogItem->default_quantity,
            'unit_price' => $catalogItem->unit_price,
            'unit_cost' => $catalogItem->unit_cost,
            'position' => (int) $section->items()->max('position') + 1,
        ]);

        $this->recalculate();
        $this->loadState();
        $this->toastSuccess($catalogItem->name.' añadida.');
    }

    public function updatedItems(mixed $value, string $key): void
    {
        [$itemId, $field] = explode('.', $key, 2);

        $numeric = ['quantity', 'unit_price', 'unit_cost', 'discount_percent'];

        if (in_array($field, $numeric, true)) {
            $value = max(0, (float) $value);

            if ($field === 'discount_percent') {
                $value = min(100, $value);
            }

            $this->items[$itemId][$field] = $value;
        }

        $item = $this->quote->items()->findOrFail($itemId);
        $item->update([$field => $value]);

        $this->recalculate();
    }

    public function removeItem(int $itemId): void
    {
        $this->quote->items()->findOrFail($itemId)->delete();
        $this->recalculate();
        $this->loadState();
    }

    public function duplicateItem(int $itemId): void
    {
        $item = $this->quote->items()->findOrFail($itemId);

        $copy = $item->replicate();
        $copy->position = $item->position + 1;
        $copy->save();

        $this->recalculate();
        $this->loadState();
    }

    // ---------------------------------------------------------------- acciones
    private function recalculate(): void
    {
        app(QuoteCalculator::class)->recalculate($this->quote);
        $this->quote->refresh();
    }

    public function save()
    {
        $this->recalculate();
        session()->flash('status', 'Presupuesto guardado.');

        return $this->redirectRoute('admin.quotes.show', $this->quote, navigate: true);
    }

    public function sendToClient(QuoteWorkflow $workflow)
    {
        $this->authorize('send', $this->quote);

        if ($this->quote->items()->count() === 0) {
            $this->toastError('Añade al menos una partida antes de enviarlo.');

            return null;
        }

        $workflow->send($this->quote);

        session()->flash('status', 'Presupuesto enviado al cliente.');

        return $this->redirectRoute('admin.quotes.show', $this->quote, navigate: true);
    }

    public function openCatalog(int $sectionId): void
    {
        $this->catalogTargetSection = $sectionId;
        $this->catalogSearch = '';
    }

    public function closeCatalog(): void
    {
        $this->catalogTargetSection = null;
    }

    public function render()
    {
        return view('livewire.admin.quotes.quote-builder', [
            'quoteModel' => $this->quote->fresh(['sections.items', 'client']),
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'properties' => Client::find($this->header['client_id'])?->properties ?? collect(),
            'trades' => Trade::active()->get(),
            'units' => MeasurementUnit::cases(),
            'discountTypes' => DiscountType::cases(),
            'catalogResults' => $this->catalogTargetSection
                ? CatalogItem::active()->with('trade')->search($this->catalogSearch)->limit(25)->get()
                : collect(),
        ])->title('Presupuesto '.$this->quote->reference);
    }
}
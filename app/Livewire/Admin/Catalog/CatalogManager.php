<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Catalog;

use App\Enums\MeasurementUnit;
use App\Livewire\Concerns\WithToasts;
use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\Trade;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Banco de precios propio. Alimenta el constructor de presupuestos y
 * mantiene coherente el margen entre coste y PVP.
 */
#[Layout('components.layouts.admin')]
#[Title('Catálogo de partidas')]
class CatalogManager extends Component
{
    use WithPagination;
    use WithToasts;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'cat', except: '')]
    public string $categoryId = '';

    public bool $showForm = false;
    public ?int $editingId = null;

    public array $form = [
        'catalog_category_id' => null, 'trade_id' => null, 'code' => '', 'name' => '',
        'description' => '', 'unit' => 'ud', 'unit_cost' => 0, 'unit_price' => 0,
        'default_quantity' => 1, 'yield_per_day' => null, 'is_active' => true,
    ];

    protected function rules(): array
    {
        return [
            'form.catalog_category_id' => ['nullable', 'exists:catalog_categories,id'],
            'form.trade_id' => ['nullable', 'exists:trades,id'],
            'form.code' => ['nullable', 'string', 'max:30', Rule::unique('catalog_items', 'code')->ignore($this->editingId)],
            'form.name' => ['required', 'string', 'max:191'],
            'form.description' => ['nullable', 'string', 'max:2000'],
            'form.unit' => ['required', Rule::enum(MeasurementUnit::class)],
            'form.unit_cost' => ['required', 'numeric', 'min:0'],
            'form.unit_price' => ['required', 'numeric', 'min:0'],
            'form.default_quantity' => ['required', 'numeric', 'min:0'],
            'form.yield_per_day' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function create(): void
    {
        $this->reset('form', 'editingId');
        $this->form['catalog_category_id'] = $this->categoryId ?: null;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $item = CatalogItem::findOrFail($id);
        $this->editingId = $id;
        $this->form = array_merge($this->form, $item->only(array_keys($this->form)));
        $this->form['unit'] = $item->unit->value;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('create', CatalogItem::class);
        $data = $this->validate()['form'];

        $this->editingId
            ? CatalogItem::findOrFail($this->editingId)->update($data)
            : CatalogItem::create($data);

        $this->reset('form', 'editingId', 'showForm');
        $this->toastSuccess('Partida guardada.');
    }

    public function toggleActive(int $id): void
    {
        $item = CatalogItem::findOrFail($id);
        $item->update(['is_active' => ! $item->is_active]);
    }

    /** Margen calculado en vivo mientras se edita la partida. */
    public function getMarginProperty(): float
    {
        $price = (float) ($this->form['unit_price'] ?: 0);
        $cost = (float) ($this->form['unit_cost'] ?: 0);

        return $price > 0 ? round((($price - $cost) / $price) * 100, 1) : 0.0;
    }

    public function render()
    {
        return view('livewire.admin.catalog.catalog-manager', [
            'items' => CatalogItem::with('category', 'trade')
                ->search($this->search)
                ->when($this->categoryId, fn ($q) => $q->where('catalog_category_id', $this->categoryId))
                ->orderBy('name')
                ->paginate(25),
            'categories' => CatalogCategory::active()->orderBy('name')->get(),
            'trades' => Trade::active()->get(),
            'units' => MeasurementUnit::cases(),
        ]);
    }
}
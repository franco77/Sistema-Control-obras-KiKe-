<?php

declare(strict_types=1);

namespace App\Livewire\Portal;

use App\Livewire\Concerns\InteractsWithPortal;
use App\Livewire\Concerns\WithToasts;
use App\Models\Quote;
use App\Notifications\Internal\QuoteDecisionNotification;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Quotes\QuoteCalculator;
use App\Services\Quotes\QuoteWorkflow;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Revisión y decisión del presupuesto por parte del cliente.
 *
 * El presupuesto NO llega como parámetro: se resuelve desde el token de la
 * sesión en cada petición, de modo que no hay forma de apuntar a otro.
 */
#[Layout('components.layouts.portal')]
class QuoteReview extends Component
{
    use InteractsWithPortal;
    use WithToasts;

    public string $decision = '';        // approve | reject
    public string $signerName = '';
    public string $rejectionReason = '';
    public bool $acceptedTerms = false;

    /** Partidas opcionales que el cliente marca o desmarca. */
    public array $optional = [];

    public function mount(QuoteWorkflow $workflow): void
    {
        $quote = $this->quote();

        $this->signerName = $quote->client->name;

        foreach ($quote->sections as $section) {
            foreach ($section->items->where('is_optional', true) as $item) {
                $this->optional[$item->id] = (bool) $item->is_included;
            }
        }

        // Registrar la apertura solo la primera vez de cada visita.
        if (! session()->has('portal.quote_viewed.'.$quote->id)) {
            $workflow->markViewed($quote, request()->ip());
            session()->put('portal.quote_viewed.'.$quote->id, true);
        }
    }

    private function quote(): Quote
    {
        $quote = $this->portalToken()->tokenable;

        abort_unless($quote instanceof Quote, 404);

        return $quote->load(['client', 'property', 'sections.items']);
    }

    /** El cliente activa o desactiva una partida opcional. */
    public function toggleOptional(int $itemId, QuoteCalculator $calculator): void
    {
        $this->portalAbility('quote.decide');

        $quote = $this->quote();

        abort_unless($quote->isDecidable(), 403);

        $item = $quote->items()->findOrFail($itemId);

        abort_unless($item->is_optional, 403);

        $item->update(['is_included' => ! $item->is_included]);
        $this->optional[$itemId] = $item->is_included;

        $calculator->recalculate($quote);
    }

    public function approve(QuoteWorkflow $workflow, NotificationDispatcher $notifications): void
    {
        $this->portalAbility('quote.decide');

        $this->validate([
            'signerName' => ['required', 'string', 'min:3', 'max:191'],
            'acceptedTerms' => ['accepted'],
        ], [
            'signerName.required' => 'Indica tu nombre y apellidos para firmar la aceptación.',
            'signerName.min' => 'Indica tu nombre completo.',
            'acceptedTerms.accepted' => 'Debes aceptar las condiciones para aprobar el presupuesto.',
        ]);

        $quote = $workflow->approve($this->quote(), $this->signerName, request()->ip());

        $notifications->toRole('admin', new QuoteDecisionNotification($quote, approved: true));

        $this->reset('decision');
        $this->toastSuccess('¡Presupuesto aprobado! Nos pondremos en contacto para fijar el inicio.');
    }

    public function reject(QuoteWorkflow $workflow, NotificationDispatcher $notifications): void
    {
        $this->portalAbility('quote.decide');

        $this->validate([
            'rejectionReason' => ['required', 'string', 'min:5', 'max:1000'],
        ], ['rejectionReason.required' => 'Cuéntanos brevemente el motivo; nos ayuda a mejorar la propuesta.']);

        $quote = $workflow->reject($this->quote(), $this->rejectionReason, $this->signerName ?: null, request()->ip());

        $notifications->toRole('admin', new QuoteDecisionNotification($quote, approved: false));

        $this->reset('decision');
        $this->toastInfo('Hemos registrado tu respuesta. Gracias por avisarnos.');
    }

    public function render()
    {
        return view('livewire.portal.quote-review', [
            'quote' => $this->quote(),
        ])->title('Presupuesto');
    }
}
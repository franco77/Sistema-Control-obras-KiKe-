<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Quotes;

use App\Enums\QuoteStatus;
use App\Livewire\Concerns\WithToasts;
use App\Models\Quote;
use App\Models\User;
use App\Services\Portal\PortalTokenService;
use App\Services\Quotes\QuoteConversionService;
use App\Services\Quotes\QuoteVersionService;
use App\Services\Quotes\QuoteWorkflow;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Ficha del presupuesto: documento en solo lectura, histórico de versiones,
 * trazabilidad de envíos/aperturas y acciones de negocio.
 */
#[Layout('components.layouts.admin')]
class QuoteShow extends Component
{
    use WithToasts;

    public Quote $quote;

    public ?string $portalLink = null;

    // Registro manual de una decisión comunicada por teléfono o en persona.
    public bool $showDecisionModal = false;
    public string $decisionType = 'approve';
    public string $decisionSigner = '';
    public string $decisionReason = '';

    // Conversión a obra
    public bool $showConvertModal = false;
    public array $convert = ['name' => '', 'planned_start' => '', 'manager_id' => null];

    public function mount(Quote $quote): void
    {
        $this->authorize('view', $quote);
        $this->quote = $quote;
    }

    public function sendToClient(QuoteWorkflow $workflow): void
    {
        $this->authorize('send', $this->quote);

        $workflow->send($this->quote);
        $this->quote->refresh();

        $this->toastSuccess('Presupuesto enviado al cliente.');
    }

    public function regeneratePortalLink(PortalTokenService $tokens): void
    {
        $this->authorize('update', $this->quote);

        $plain = $tokens->issueOrReuse(
            $this->quote,
            abilities: ['quote.view', 'quote.decide', 'quote.download'],
            ttlDays: (int) setting('quotes.token_ttl_days', 120),
        );

        $this->portalLink = $tokens->urlFor($plain);
        $this->toastInfo('Enlace nuevo generado. El anterior ha quedado revocado.');
    }

    public function openDecision(string $decision): void
    {
        abort_unless(in_array($decision, ['approve', 'reject'], true), 400);

        $this->reset('decisionSigner', 'decisionReason');

        $this->decisionType = $decision;
        $this->decisionSigner = $this->quote->client->name;
        $this->showDecisionModal = true;
    }

    public function recordDecision(QuoteWorkflow $workflow): void
    {
        $this->authorize('update', $this->quote);

        $this->validate([
            'decisionSigner' => ['required', 'string', 'max:191'],
            'decisionReason' => [$this->decisionType === 'reject' ? 'required' : 'nullable', 'string', 'max:1000'],
        ], attributes: ['decisionSigner' => 'firmante', 'decisionReason' => 'motivo']);

        $this->decisionType === 'approve'
            ? $workflow->approve($this->quote, $this->decisionSigner)
            : $workflow->reject($this->quote, $this->decisionReason, $this->decisionSigner);

        $this->quote->refresh();
        $this->showDecisionModal = false;
        $this->toastSuccess('Decisión registrada.');
    }

    public function createVersion(QuoteVersionService $versions)
    {
        $this->authorize('createVersion', $this->quote);

        $new = $versions->createNewVersion($this->quote);

        session()->flash('status', "Creada la versión {$new->version} en borrador.");

        return $this->redirectRoute('admin.quotes.edit', $new, navigate: true);
    }

    public function duplicate(QuoteVersionService $versions)
    {
        $this->authorize('create', Quote::class);

        $copy = $versions->duplicate($this->quote);

        session()->flash('status', 'Presupuesto duplicado como '.$copy->number.'.');

        return $this->redirectRoute('admin.quotes.edit', $copy, navigate: true);
    }

    public function openConvert(): void
    {
        $this->convert = [
            'name' => $this->quote->title,
            'planned_start' => now()->addWeek()->toDateString(),
            'manager_id' => auth()->id(),
        ];
        $this->showConvertModal = true;
    }

    public function convertToProject(QuoteConversionService $conversion)
    {
        $this->authorize('convert', $this->quote);

        $this->validate([
            'convert.name' => ['required', 'string', 'max:191'],
            'convert.planned_start' => ['required', 'date'],
            'convert.manager_id' => ['nullable', 'exists:users,id'],
        ]);

        $project = $conversion->convert($this->quote, $this->convert);

        session()->flash('status', "Obra {$project->code} creada con {$project->phases->count()} fases.");

        return $this->redirectRoute('admin.projects.show', $project, navigate: true);
    }

    public function render()
    {
        $this->quote->load(['client', 'property', 'sections.items', 'author', 'project']);

        return view('livewire.admin.quotes.quote-show', [
            'versions' => $this->quote->versionChain()->get(),
            'activities' => $this->quote->activities()->with('causer')->limit(40)->get(),
            'managers' => User::active()->orderBy('name')->get(['id', 'name']),
        ])->title($this->quote->reference);
    }
}
<?php

declare(strict_types=1);

namespace App\Livewire\Portal;

use App\Livewire\Concerns\InteractsWithPortal;
use App\Livewire\Concerns\WithToasts;
use App\Models\ProjectExtra;
use App\Notifications\Internal\ExtraDecisionNotification;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Projects\ExtraWorkflow;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Pantalla de un único extra, para los enlaces que se envían por email
 * pidiendo exclusivamente esa aprobación.
 */
#[Layout('components.layouts.portal')]
class ExtraDecision extends Component
{
    use InteractsWithPortal;
    use WithToasts;

    public string $decision = '';
    public string $signerName = '';
    public string $rejectionReason = '';

    public function mount(): void
    {
        $this->signerName = $this->extra()->project->client->name;
    }

    private function extra(): ProjectExtra
    {
        $extra = $this->portalToken()->tokenable;

        abort_unless($extra instanceof ProjectExtra, 404);

        return $extra->load('project.client', 'phase', 'incident');
    }

    public function approve(ExtraWorkflow $workflow, NotificationDispatcher $notifications): void
    {
        $this->portalAbility('extra.decide');

        $this->validate([
            'signerName' => ['required', 'string', 'min:3', 'max:191'],
        ], ['signerName.required' => 'Indica tu nombre para dejar constancia.']);

        $extra = $workflow->approve($this->extra(), $this->signerName, request()->ip());
        $notifications->toRole('admin', new ExtraDecisionNotification($extra, approved: true));

        $this->reset('decision');
        $this->toastSuccess('Extra aprobado. Gracias.');
    }

    public function reject(ExtraWorkflow $workflow, NotificationDispatcher $notifications): void
    {
        $this->portalAbility('extra.decide');

        $this->validate([
            'rejectionReason' => ['required', 'string', 'min:5', 'max:1000'],
        ], ['rejectionReason.required' => 'Cuéntanos el motivo.']);

        $extra = $workflow->reject($this->extra(), $this->rejectionReason, request()->ip());
        $notifications->toRole('admin', new ExtraDecisionNotification($extra, approved: false));

        $this->reset('decision');
        $this->toastInfo('Respuesta registrada.');
    }

    public function render()
    {
        return view('livewire.portal.extra-decision', [
            'extra' => $this->extra(),
        ])->title('Trabajo adicional');
    }
}
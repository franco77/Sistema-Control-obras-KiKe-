<?php

declare(strict_types=1);

namespace App\Livewire\Portal;

use App\Enums\ExtraStatus;
use App\Livewire\Concerns\InteractsWithPortal;
use App\Livewire\Concerns\WithToasts;
use App\Models\ProjectExtra;
use App\Notifications\Internal\ExtraDecisionNotification;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Projects\ExtraWorkflow;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Aprobación o rechazo de extras desde el portal de la obra. */
#[Layout('components.layouts.portal')]
class ProjectExtras extends Component
{
    use InteractsWithPortal;
    use WithToasts;

    public ?int $decidingId = null;
    public string $decision = '';
    public string $signerName = '';
    public string $rejectionReason = '';

    public function startDecision(int $extraId, string $decision): void
    {
        $this->portalAbility('extra.decide');

        $this->decidingId = $extraId;
        $this->decision = $decision;
        $this->signerName = $this->portalProject()->client->name;
        $this->rejectionReason = '';
    }

    public function cancelDecision(): void
    {
        $this->reset('decidingId', 'decision', 'rejectionReason');
    }

    public function confirm(ExtraWorkflow $workflow, NotificationDispatcher $notifications): void
    {
        $this->portalAbility('extra.decide');

        $extra = $this->extra($this->decidingId);

        if ($this->decision === 'approve') {
            $this->validate([
                'signerName' => ['required', 'string', 'min:3', 'max:191'],
            ], ['signerName.required' => 'Indica tu nombre para dejar constancia de la aprobación.']);

            $workflow->approve($extra, $this->signerName, request()->ip());
            $notifications->toRole('admin', new ExtraDecisionNotification($extra, approved: true));
            $this->toastSuccess('Extra aprobado. Lo incorporamos a la obra.');
        } else {
            $this->validate([
                'rejectionReason' => ['required', 'string', 'min:5', 'max:1000'],
            ], ['rejectionReason.required' => 'Indícanos el motivo para poder buscar alternativas.']);

            $workflow->reject($extra, $this->rejectionReason, request()->ip());
            $notifications->toRole('admin', new ExtraDecisionNotification($extra, approved: false));
            $this->toastInfo('Hemos registrado tu respuesta.');
        }

        $this->cancelDecision();
    }

    private function extra(?int $id): ProjectExtra
    {
        $extra = $this->portalProject()->extras()->findOrFail($id);

        abort_unless($extra->isDecidable(), 403, 'Este extra ya no admite decisión.');

        return $extra;
    }

    public function render()
    {
        $project = $this->portalProject();

        return view('livewire.portal.project-extras', [
            'project' => $project,
            'pending' => $project->extras()->where('status', ExtraStatus::Sent)->with('phase')->get(),
            'decided' => $project->extras()
                ->whereIn('status', [ExtraStatus::Approved, ExtraStatus::Rejected])
                ->latest('decided_at')->get(),
        ])->title('Trabajos adicionales');
    }
}
<?php

declare(strict_types=1);

namespace App\Livewire\Portal;

use App\Livewire\Concerns\InteractsWithPortal;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Detalle de trabajos completados y pendientes, agrupados por fase. */
#[Layout('components.layouts.portal')]
class ProjectTasks extends Component
{
    use InteractsWithPortal;

    public string $filter = 'all'; // all | done | pending

    public function render()
    {
        $project = $this->portalProject();

        return view('livewire.portal.project-tasks', [
            'project' => $project,
            'phases' => $project->phases()
                ->visibleToClient()
                ->with(['tasks' => fn ($q) => $q->visibleToClient()->orderBy('position')])
                ->get(),
        ])->title('Trabajos');
    }
}
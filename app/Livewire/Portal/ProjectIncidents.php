<?php

declare(strict_types=1);

namespace App\Livewire\Portal;

use App\Livewire\Concerns\InteractsWithPortal;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Incidencias que el equipo ha decidido compartir con el cliente. */
#[Layout('components.layouts.portal')]
class ProjectIncidents extends Component
{
    use InteractsWithPortal;

    public function render()
    {
        $project = $this->portalProject();

        return view('livewire.portal.project-incidents', [
            'project' => $project,
            'incidents' => $project->incidents()->visibleToClient()->with('phase')->get(),
        ])->title('Incidencias');
    }
}
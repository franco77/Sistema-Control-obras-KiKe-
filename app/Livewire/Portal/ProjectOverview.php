<?php

declare(strict_types=1);

namespace App\Livewire\Portal;

use App\Enums\ExtraStatus;
use App\Livewire\Concerns\InteractsWithPortal;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Pantalla principal del portal: estado, avance y últimas novedades. */
#[Layout('components.layouts.portal')]
class ProjectOverview extends Component
{
    use InteractsWithPortal;

    public function render()
    {
        $project = $this->portalProject();

        return view('livewire.portal.project-overview', [
            'project' => $project->load('client', 'property', 'manager'),
            'phases' => $project->phases()->visibleToClient()->with(['tasks' => fn ($q) => $q->visibleToClient()])->get(),
            'updates' => $project->updates()->visibleToClient()->with('author')->limit(5)->get(),
            'recentPhotos' => $project->photos()->visibleToClient()->latest('taken_at')->limit(6)->get(),
            'pendingExtras' => $project->extras()->where('status', ExtraStatus::Sent)->count(),
            'openIncidents' => $project->incidents()->visibleToClient()->open()->count(),
            'unreadMessages' => $project->conversations()->sum('unread_for_client'),
            'nextEvents' => $project->events()->visibleToClient()->upcoming(30)->limit(4)->get(),
        ])->title('Tu obra');
    }
}
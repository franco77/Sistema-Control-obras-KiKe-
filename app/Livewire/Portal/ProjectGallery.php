<?php

declare(strict_types=1);

namespace App\Livewire\Portal;

use App\Enums\PhotoStage;
use App\Livewire\Concerns\InteractsWithPortal;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Galería de avances visible para el cliente. */
#[Layout('components.layouts.portal')]
class ProjectGallery extends Component
{
    use InteractsWithPortal;

    public string $stage = '';

    public function render()
    {
        $project = $this->portalProject();

        return view('livewire.portal.project-gallery', [
            'project' => $project,
            'photos' => $project->photos()
                ->visibleToClient()
                ->stage($this->stage ?: null)
                ->latest('taken_at')
                ->get()
                ->groupBy(fn ($photo) => $photo->taken_at?->format('Y-m') ?? 'otros'),
            'stages' => PhotoStage::cases(),
        ])->title('Fotos de la obra');
    }
}
<?php

declare(strict_types=1);

namespace App\Livewire\Portal;

use App\Livewire\Concerns\InteractsWithPortal;
use App\Models\Document;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Documentación que el equipo ha marcado como visible para el cliente. */
#[Layout('components.layouts.portal')]
class ProjectDocuments extends Component
{
    use InteractsWithPortal;

    public function render()
    {
        $project = $this->portalProject();

        // Documentos de la obra más los del presupuesto que la originó.
        $documents = Document::query()
            ->visibleToClient()
            ->where(function ($query) use ($project) {
                $query->where(fn ($q) => $q
                    ->where('documentable_type', $project->getMorphClass())
                    ->where('documentable_id', $project->id));

                if ($project->quote_id) {
                    $query->orWhere(fn ($q) => $q
                        ->where('documentable_type', (new \App\Models\Quote)->getMorphClass())
                        ->where('documentable_id', $project->quote_id));
                }
            })
            ->orderBy('category')
            ->get()
            ->groupBy(fn (Document $d) => $d->category->label());

        return view('livewire.portal.project-documents', [
            'project' => $project,
            'documents' => $documents,
        ])->title('Documentos');
    }
}
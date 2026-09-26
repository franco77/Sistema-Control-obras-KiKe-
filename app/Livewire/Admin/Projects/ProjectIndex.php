<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Projects;

use App\Enums\ProjectStatus;
use App\Livewire\Concerns\WithSorting;
use App\Models\Project;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Title('Obras')]
class ProjectIndex extends Component
{
    use WithPagination;
    use WithSorting;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'estado', except: '')]
    public string $status = '';

    #[Url(as: 'jefe', except: '')]
    public string $manager = '';

    #[Url(as: 'retrasadas', except: false)]
    public bool $onlyDelayed = false;

    protected function sortableColumns(): array
    {
        return ['code', 'name', 'planned_start', 'planned_end', 'progress', 'budget_total'];
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'manager', 'onlyDelayed'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $projects = $this->applySorting(
            Project::query()
                ->with('client', 'property', 'manager')
                ->withCount(['incidents as open_incidents_count' => fn ($q) => $q->open()])
                ->search($this->search)
                ->status($this->status ?: null)
                ->when($this->manager, fn ($q) => $q->where('manager_id', $this->manager))
                ->when($this->onlyDelayed, fn ($q) => $q->delayed()),
            'planned_start'
        )->paginate(20);

        return view('livewire.admin.projects.project-index', [
            'projects' => $projects,
            'statuses' => ProjectStatus::cases(),
            'managers' => User::active()->orderBy('name')->get(['id', 'name']),
            'summary' => [
                'open' => Project::open()->count(),
                'delayed' => Project::delayed()->count(),
                'contracted' => (float) Project::open()->sum('budget_total'),
                'extras' => (float) Project::open()->sum('extras_total'),
            ],
        ]);
    }
}
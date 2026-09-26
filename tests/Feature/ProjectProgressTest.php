<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PhaseStatus;
use App\Enums\TaskStatus;
use App\Models\Client;
use App\Models\Project;
use App\Services\Projects\ProjectProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectProgressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaseline();
    }

    private function projectWithPhases(): Project
    {
        $client = Client::create(['name' => 'Cliente']);

        $project = Project::create([
            'client_id' => $client->id,
            'name' => 'Reforma',
            'status' => 'in_progress',
        ]);

        // Fase pesada (75) con 2 tareas y fase ligera (25) con 1 tarea.
        $heavy = $project->phases()->create(['name' => 'Albañilería', 'weight' => 75, 'position' => 0]);
        $light = $project->phases()->create(['name' => 'Pintura', 'weight' => 25, 'position' => 1]);

        $project->tasks()->create(['project_phase_id' => $heavy->id, 'name' => 'Tabiquería']);
        $project->tasks()->create(['project_phase_id' => $heavy->id, 'name' => 'Enfoscado']);
        $project->tasks()->create(['project_phase_id' => $light->id, 'name' => 'Dos manos']);

        return $project;
    }

    public function test_el_avance_pondera_por_el_peso_de_cada_fase(): void
    {
        $project = $this->projectWithPhases();
        $service = app(ProjectProgressService::class);

        $service->recalculate($project);
        $this->assertSame(0, $project->fresh()->progress);

        // Completar la fase ligera entera: 25 % del total.
        $project->phases()->where('name', 'Pintura')->first()
            ->tasks()->update(['status' => TaskStatus::Done]);

        $service->recalculate($project);
        $this->assertSame(25, $project->fresh()->progress);

        // Media fase pesada: 75 × 0,5 = 37,5 → 62,5 → redondea a 63.
        $project->tasks()->where('name', 'Tabiquería')->update(['status' => TaskStatus::Done]);

        $service->recalculate($project);
        $this->assertSame(63, $project->fresh()->progress);
    }

    public function test_las_tareas_canceladas_no_penalizan_el_avance(): void
    {
        $project = $this->projectWithPhases();

        $project->tasks()->where('name', 'Tabiquería')->update(['status' => TaskStatus::Done]);
        $project->tasks()->where('name', 'Enfoscado')->update(['status' => TaskStatus::Cancelled]);

        app(ProjectProgressService::class)->recalculate($project);

        $phase = $project->phases()->where('name', 'Albañilería')->first();

        $this->assertSame(100, $phase->progress);
        $this->assertSame(PhaseStatus::Done, $phase->status);
    }

    public function test_una_tarea_iniciada_pone_la_fase_en_curso(): void
    {
        $project = $this->projectWithPhases();

        $project->tasks()->where('name', 'Tabiquería')->update(['status' => TaskStatus::InProgress]);

        app(ProjectProgressService::class)->recalculate($project);

        $this->assertSame(
            PhaseStatus::InProgress,
            $project->phases()->where('name', 'Albañilería')->first()->status
        );
    }

    public function test_una_tarea_bloqueada_bloquea_la_fase(): void
    {
        $project = $this->projectWithPhases();

        $project->tasks()->where('name', 'Enfoscado')->update(['status' => TaskStatus::Blocked]);

        app(ProjectProgressService::class)->recalculate($project);

        $this->assertSame(
            PhaseStatus::Blocked,
            $project->phases()->where('name', 'Albañilería')->first()->status
        );
    }
}
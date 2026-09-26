<?php

declare(strict_types=1);

namespace App\Services\Projects;

use App\Enums\PhaseStatus;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\ProjectPhase;

/**
 * Recalcula el avance de fases y obra.
 *
 * Fase: % de tareas completadas (las canceladas no cuentan).
 * Obra: media de las fases ponderada por su peso económico.
 * Además sincroniza estados y fechas reales de inicio/fin.
 */
class ProjectProgressService
{
    public function recalculate(Project $project): Project
    {
        // load() y no loadMissing(): si quien llama ya traía las relaciones
        // cargadas, estarían obsoletas justo después de tocar las tareas.
        $project->load('phases.tasks');

        $weightedSum = 0;
        $weightTotal = 0;

        foreach ($project->phases as $phase) {
            $progress = $this->phaseProgress($phase);

            $phase->forceFill([
                'progress' => $progress,
                'status' => $this->phaseStatus($phase, $progress),
                'actual_start' => $phase->actual_start ?? ($progress > 0 ? now()->toDateString() : null),
                'actual_end' => $progress === 100 ? ($phase->actual_end ?? now()->toDateString()) : null,
            ])->saveQuietly();

            $weightedSum += $progress * $phase->weight;
            $weightTotal += $phase->weight;
        }

        $projectProgress = $weightTotal > 0 ? (int) round($weightedSum / $weightTotal) : 0;

        $project->forceFill([
            'progress' => $projectProgress,
            'actual_start' => $project->actual_start ?? ($projectProgress > 0 ? now()->toDateString() : null),
        ])->save();

        return $project;
    }

    public function phaseProgress(ProjectPhase $phase): int
    {
        $tasks = $phase->tasks->reject(fn ($task) => $task->status === TaskStatus::Cancelled);

        if ($tasks->isEmpty()) {
            return $phase->status === PhaseStatus::Done ? 100 : 0;
        }

        $done = $tasks->where('status', TaskStatus::Done)->count();

        return (int) round(($done / $tasks->count()) * 100);
    }

    private function phaseStatus(ProjectPhase $phase, int $progress): PhaseStatus
    {
        if ($phase->status === PhaseStatus::Cancelled) {
            return PhaseStatus::Cancelled;
        }

        if ($phase->tasks->contains(fn ($task) => $task->status === TaskStatus::Blocked)) {
            return PhaseStatus::Blocked;
        }

        // Una fase está "en curso" en cuanto alguien ha tocado una tarea,
        // aunque todavía no haya ninguna completada.
        $started = $phase->tasks->contains(
            fn ($task) => $task->status->is(TaskStatus::InProgress, TaskStatus::Review, TaskStatus::Done)
        );

        return match (true) {
            $progress === 100 => PhaseStatus::Done,
            $progress > 0 || $started => PhaseStatus::InProgress,
            default => PhaseStatus::Pending,
        };
    }

    /** Cierra la obra cuando todas sus fases están completadas. */
    public function closeIfCompleted(Project $project): bool
    {
        $this->recalculate($project);

        if ($project->progress < 100 || $project->status === ProjectStatus::Finished) {
            return false;
        }

        $project->forceFill([
            'status' => ProjectStatus::Finished,
            'actual_end' => now()->toDateString(),
        ])->save();

        $project->recordActivity('project.finished', 'Obra finalizada al 100 %');

        return true;
    }
}
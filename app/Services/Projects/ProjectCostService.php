<?php

declare(strict_types=1);

namespace App\Services\Projects;

use App\Enums\ExtraStatus;
use App\Models\Project;

/**
 * Consolida los importes económicos de la obra: coste real ejecutado,
 * extras aprobados y desviación frente al presupuesto contratado.
 */
class ProjectCostService
{
    public function recalculate(Project $project): Project
    {
        $costReal = (float) $project->tasks()->sum('cost_real');
        $costEstimated = (float) $project->tasks()->sum('cost_estimated');
        $extras = (float) $project->extras()->where('status', ExtraStatus::Approved)->sum('amount');
        $incidents = (float) $project->incidents()->sum('cost_impact');

        $project->forceFill([
            'cost_real' => round($costReal + $incidents, 2),
            'cost_estimated' => round($costEstimated ?: (float) $project->cost_estimated, 2),
            'extras_total' => round($extras, 2),
        ])->save();

        return $project;
    }

    /** @return array{contracted: float, cost: float, margin: float, margin_percent: float, deviation: float} */
    public function summary(Project $project): array
    {
        $contracted = (float) $project->budget_total + (float) $project->extras_total;
        $cost = (float) ($project->cost_real ?: $project->cost_estimated);
        $margin = round($contracted - $cost, 2);

        return [
            'contracted' => round($contracted, 2),
            'cost' => round($cost, 2),
            'margin' => $margin,
            'margin_percent' => $contracted > 0 ? round(($margin / $contracted) * 100, 2) : 0.0,
            'deviation' => round((float) $project->cost_real - (float) $project->cost_estimated, 2),
        ];
    }
}
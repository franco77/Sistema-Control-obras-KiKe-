<?php

declare(strict_types=1);

namespace App\Services\Quotes;

use App\Enums\ProjectStatus;
use App\Enums\QuoteStatus;
use App\Exceptions\InvalidTransitionException;
use App\Models\Project;
use App\Models\Quote;
use Illuminate\Support\Carbon;
use App\Models\QuoteItem;
use Illuminate\Support\Facades\DB;

/**
 * Conversión automática de presupuesto aprobado en obra.
 *
 * Mapeo: capítulo → fase de obra, partida → tarea de la fase.
 * El peso de cada fase se deriva de su importe, de modo que el % de avance
 * de la obra refleja el peso económico real de cada capítulo.
 */
class QuoteConversionService
{
    public function convert(Quote $quote, array $overrides = []): Project
    {
        if ($quote->status !== QuoteStatus::Approved) {
            throw new InvalidTransitionException('Solo se pueden convertir en obra los presupuestos aprobados.');
        }

        if ($quote->project_id !== null) {
            throw new InvalidTransitionException("El presupuesto {$quote->reference} ya tiene la obra {$quote->project->code}.");
        }

        return DB::transaction(function () use ($quote, $overrides) {
            // catalogItem se carga por adelantado: estimateHours() lo consulta
            // en cada partida y sin esto sería una consulta por línea.
            $quote->load('sections.items.catalogItem');

            $start = $overrides['planned_start'] ?? now()->addWeek()->toDateString();
            $days = (int) ($quote->estimated_duration_days ?: 30);

            $project = Project::create([
                'client_id' => $quote->client_id,
                'property_id' => $quote->property_id,
                'quote_id' => $quote->id,
                'manager_id' => $overrides['manager_id'] ?? auth()->id(),
                'name' => $overrides['name'] ?? $quote->title,
                'description' => $quote->description,
                'status' => ProjectStatus::Planned,
                'planned_start' => $start,
                'planned_end' => $overrides['planned_end'] ?? Carbon::parse($start)->addDays($days)->toDateString(),
                'budget_total' => $quote->taxable_base,
                'cost_estimated' => $quote->cost_total,
                'portal_enabled' => true,
                'created_by' => auth()->id(),
            ]);

            $totalBase = max((float) $quote->taxable_base, 0.01);

            foreach ($quote->sections as $index => $section) {
                $weight = max(1, (int) round(((float) $section->subtotal / $totalBase) * 100));

                $phase = $project->phases()->create([
                    'trade_id' => $section->trade_id,
                    'name' => $section->name,
                    'description' => $section->description,
                    'position' => $section->position ?: $index,
                    'weight' => $weight,
                    'budget_amount' => $section->subtotal,
                    'visible_to_client' => true,
                ]);

                foreach ($section->items as $itemIndex => $item) {
                    if (! $item->countsTowardsTotal()) {
                        continue;
                    }

                    $project->tasks()->create([
                        'project_phase_id' => $phase->id,
                        'trade_id' => $item->trade_id ?? $section->trade_id,
                        'name' => $item->name,
                        'description' => $item->description,
                        'position' => $item->position ?: $itemIndex,
                        'cost_estimated' => $item->cost_total,
                        'estimated_hours' => $this->estimateHours($item),
                        'visible_to_client' => true,
                    ]);
                }
            }

            $quote->forceFill(['project_id' => $project->id])->save();

            $quote->recordActivity('quote.converted', "Convertido en la obra {$project->code}");
            $project->recordActivity('project.created', "Creada desde el presupuesto {$quote->reference}");

            return $project->fresh(['phases.tasks']);
        });
    }

    /** Estimación de horas a partir del rendimiento del catálogo, si existe. */
    private function estimateHours(QuoteItem $item): ?float
    {
        $yield = $item->catalogItem?->yield_per_day;

        if (! $yield || (float) $yield <= 0) {
            return null;
        }

        return round(((float) $item->quantity / (float) $yield) * 8, 2);
    }
}
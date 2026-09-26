<?php

declare(strict_types=1);

namespace App\Services\Projects;

use App\Enums\AvailabilityType;
use App\Enums\ProviderStatus;
use App\Enums\TaskStatus;
use App\Exceptions\ProviderNotAssignableException;
use App\Models\ProjectTask;
use App\Models\Provider;
use App\Notifications\Provider\TaskAssignedNotification;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Portal\PortalTokenService;
use Illuminate\Support\Facades\DB;

/**
 * Asignación de proveedores a tareas.
 *
 * Al asignar: valida documentación legal vigente, vincula el proveedor a la
 * obra, bloquea su calendario en el rango planificado y le envía un enlace
 * de portal limitado a las tareas de esa obra.
 */
class TaskAssignmentService
{
    public function __construct(
        private readonly PortalTokenService $tokens,
        private readonly NotificationDispatcher $notifications,
    ) {}

    public function assign(ProjectTask $task, Provider $provider, bool $notify = true): ProjectTask
    {
        $this->guardAssignable($provider);

        return DB::transaction(function () use ($task, $provider, $notify) {
            $task->forceFill([
                'provider_id' => $provider->id,
                'status' => $task->status === TaskStatus::Pending ? TaskStatus::Assigned : $task->status,
            ])->save();

            $task->project->providers()->syncWithoutDetaching([
                $provider->id => ['trade_id' => $task->trade_id],
            ]);

            $this->syncCalendarBlock($task->project_id, $provider);

            $task->recordActivity('task.assigned', "Asignada a {$provider->name}");

            if ($notify && filled($provider->email)) {
                $plainToken = $this->tokens->issue(
                    $provider,
                    abilities: ['provider.tasks', 'provider.photos', 'provider.availability'],
                    audience: 'provider',
                    name: 'Portal proveedor '.$provider->name,
                    ttlDays: 180,
                );

                $this->notifications->toProvider(
                    $provider,
                    new TaskAssignedNotification($task, $plainToken),
                    related: $task->project,
                );
            }

            return $task;
        });
    }

    public function unassign(ProjectTask $task): ProjectTask
    {
        $provider = $task->provider;

        if (! $provider) {
            return $task;
        }

        return DB::transaction(function () use ($task, $provider) {
            $task->forceFill([
                'provider_id' => null,
                'status' => $task->status === TaskStatus::Assigned ? TaskStatus::Pending : $task->status,
            ])->save();

            $this->syncCalendarBlock($task->project_id, $provider);

            $task->recordActivity('task.unassigned', "Desasignada de {$provider->name}");

            return $task;
        });
    }

    private function guardAssignable(Provider $provider): void
    {
        if ($provider->status !== ProviderStatus::Active) {
            throw new ProviderNotAssignableException(
                "El proveedor {$provider->name} no está activo ({$provider->status->label()})."
            );
        }

        if (setting('providers.require_documents', true) && ! $provider->hasValidDocumentation()) {
            throw new ProviderNotAssignableException(
                "{$provider->name} tiene documentación obligatoria pendiente o caducada."
            );
        }
    }

    /**
     * Mantiene un único bloque "ocupado" por obra y proveedor, cubriendo el
     * rango de todas sus tareas planificadas. Si ya no le queda ninguna, se
     * elimina el bloque y el proveedor vuelve a aparecer como disponible.
     */
    private function syncCalendarBlock(int $projectId, Provider $provider): void
    {
        $range = ProjectTask::query()
            ->where('project_id', $projectId)
            ->where('provider_id', $provider->id)
            ->whereNotNull('planned_start')
            ->whereNotNull('planned_end')
            ->selectRaw('MIN(planned_start) as starts_on, MAX(planned_end) as ends_on')
            ->first();

        $block = $provider->availabilities()
            ->where('project_id', $projectId)
            ->where('type', AvailabilityType::Booked);

        if (! $range?->starts_on) {
            $block->delete();

            return;
        }

        $provider->availabilities()->updateOrCreate(
            ['project_id' => $projectId, 'type' => AvailabilityType::Booked],
            [
                'starts_on' => $range->starts_on,
                'ends_on' => $range->ends_on,
                'note' => 'Obra '.($provider->projects()->find($projectId)?->code ?? "#{$projectId}"),
            ]
        );
    }
}

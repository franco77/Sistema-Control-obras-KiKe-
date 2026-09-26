<?php

declare(strict_types=1);

namespace App\Services\Projects;

use App\Enums\ExtraStatus;
use App\Exceptions\InvalidTransitionException;
use App\Models\ProjectExtra;
use App\Notifications\Client\ExtraApprovalRequestNotification;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Portal\PortalTokenService;
use Illuminate\Support\Facades\DB;

/**
 * Ciclo de aprobación de extras por parte del cliente.
 * Al aprobarse impacta en el importe contratado y, si procede, en el plazo.
 */
class ExtraWorkflow
{
    public function __construct(
        private readonly PortalTokenService $tokens,
        private readonly NotificationDispatcher $notifications,
        private readonly ProjectCostService $costs,
    ) {}

    public function send(ProjectExtra $extra): ProjectExtra
    {
        if ($extra->status->isNot(ExtraStatus::Draft, ExtraStatus::Rejected)) {
            throw new InvalidTransitionException('Solo se envían extras en borrador o rechazados.');
        }

        return DB::transaction(function () use ($extra) {
            $extra->forceFill([
                'status' => ExtraStatus::Sent,
                'sent_at' => now(),
                'rejection_reason' => null,
            ])->save();

            $plainToken = $this->tokens->issue(
                $extra,
                abilities: ['extra.view', 'extra.decide'],
                name: 'Extra '.$extra->code,
                ttlDays: 60,
            );

            $extra->recordActivity('extra.sent', 'Extra enviado al cliente para aprobación');

            $this->notifications->toClient(
                $extra->project->client,
                new ExtraApprovalRequestNotification($extra, $plainToken),
                related: $extra->project,
            );

            return $extra;
        });
    }

    public function approve(ProjectExtra $extra, string $signerName, ?string $ip = null): ProjectExtra
    {
        if (! $extra->isDecidable()) {
            throw new InvalidTransitionException('Este extra ya no admite decisión.');
        }

        return DB::transaction(function () use ($extra, $signerName, $ip) {
            $extra->forceFill([
                'status' => ExtraStatus::Approved,
                'decided_at' => now(),
                'decision_ip' => $ip,
                'signer_name' => $signerName,
            ])->save();

            $project = $extra->project;

            if ($extra->extra_days > 0 && $project->planned_end) {
                $project->forceFill([
                    'planned_end' => $project->planned_end->addDays($extra->extra_days)->toDateString(),
                ])->save();
            }

            $this->costs->recalculate($project);

            $extra->recordActivity('extra.approved', "Aprobado por {$signerName}", ['ip' => $ip]);
            $project->recordActivity('project.extra_approved', "Extra {$extra->code} aprobado (".money($extra->amount).')');

            return $extra;
        });
    }

    public function reject(ProjectExtra $extra, string $reason, ?string $ip = null): ProjectExtra
    {
        if (! $extra->isDecidable()) {
            throw new InvalidTransitionException('Este extra ya no admite decisión.');
        }

        $extra->forceFill([
            'status' => ExtraStatus::Rejected,
            'decided_at' => now(),
            'decision_ip' => $ip,
            'rejection_reason' => $reason,
        ])->save();

        $extra->recordActivity('extra.rejected', 'Rechazado por el cliente', ['reason' => $reason]);

        return $extra;
    }
}
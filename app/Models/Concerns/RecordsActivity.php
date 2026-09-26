<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

/**
 * Traza de auditoría del modelo. Se registra de forma explícita desde los
 * servicios (no con observers automáticos) para que el log tenga eventos
 * de negocio legibles: quote.sent, extra.approved, project.finished…
 */
trait RecordsActivity
{
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject')->latest('created_at');
    }

    public function recordActivity(string $event, ?string $description = null, array $properties = []): Activity
    {
        $request = request();

        return $this->activities()->create([
            'causer_type' => Auth::check() ? Auth::user()->getMorphClass() : null,
            'causer_id' => Auth::id(),
            'event' => $event,
            'description' => $description,
            'properties' => $properties ?: null,
            'ip' => $request?->ip(),
            'user_agent' => substr((string) $request?->userAgent(), 0, 255) ?: null,
            'created_at' => now(),
        ]);
    }
}
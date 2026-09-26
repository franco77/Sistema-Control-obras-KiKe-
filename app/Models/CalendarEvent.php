<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\EventType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Evento de calendario: visitas, hitos, tareas programadas y reservas de
 * proveedor. Es la fuente única del módulo de agenda.
 */
class CalendarEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'description', 'type', 'status', 'starts_at', 'ends_at', 'all_day',
        'location', 'color', 'client_id', 'property_id', 'project_id', 'project_phase_id',
        'project_task_id', 'provider_id', 'owner_id', 'reminder_at', 'reminder_sent_at',
        'visible_to_client', 'notes', 'created_by',
    ];

    protected $attributes = [
        'type' => 'visit',
        'status' => 'scheduled',
        'all_day' => false,
        'visible_to_client' => false,
    ];
    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'status' => EventStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'reminder_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'all_day' => 'boolean',
            'visible_to_client' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ProjectPhase::class, 'project_phase_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('response')->withTimestamps();
    }

    public function scopeBetween(Builder $query, string $from, string $to): Builder
    {
        return $query->where('starts_at', '<', $to)->where('ends_at', '>', $from);
    }

    public function scopeType(Builder $query, EventType|string|null $type): Builder
    {
        return $type ? $query->where('type', $type) : $query;
    }

    public function scopeUpcoming(Builder $query, int $days = 7): Builder
    {
        return $query->whereBetween('starts_at', [now(), now()->addDays($days)])
            ->whereNotIn('status', [EventStatus::Cancelled])
            ->orderBy('starts_at');
    }

    public function scopeVisibleToClient(Builder $query): Builder
    {
        return $query->where('visible_to_client', true);
    }

    public function scopeNeedingReminder(Builder $query): Builder
    {
        return $query->whereNotNull('reminder_at')
            ->whereNull('reminder_sent_at')
            ->where('reminder_at', '<=', now())
            ->whereNotIn('status', [EventStatus::Cancelled]);
    }

    public function getDisplayColorAttribute(): string
    {
        return $this->color ?: $this->type->color();
    }

    public function getDurationInMinutesAttribute(): int
    {
        return (int) $this->starts_at->diffInMinutes($this->ends_at);
    }
}
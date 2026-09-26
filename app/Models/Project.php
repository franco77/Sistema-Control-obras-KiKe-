<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExtraStatus;
use App\Enums\IncidentStatus;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\Concerns\HasDocuments;
use App\Models\Concerns\HasPortalTokens;
use App\Models\Concerns\HasSequentialCode;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Obra. Nace de un presupuesto aprobado (QuoteConversionService) y es la
 * raíz de fases, tareas, incidencias, fotos, extras y partes de trabajo.
 */
class Project extends Model
{
    use HasDocuments;
    use HasFactory;
    use HasPortalTokens;
    use HasSequentialCode;
    use RecordsActivity;
    use SoftDeletes;

    protected $fillable = [
        'code', 'client_id', 'property_id', 'quote_id', 'manager_id',
        'name', 'description', 'status', 'progress',
        'planned_start', 'planned_end', 'actual_start', 'actual_end', 'warranty_months',
        'budget_total', 'extras_total', 'cost_estimated', 'cost_real',
        'portal_enabled', 'internal_notes', 'created_by',
    ];

    protected $attributes = [
        'status' => 'draft',
        'progress' => 0,
        'warranty_months' => 12,
        'budget_total' => 0,
        'extras_total' => 0,
        'cost_estimated' => 0,
        'cost_real' => 0,
        'portal_enabled' => true,
    ];
    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'planned_start' => 'date',
            'planned_end' => 'date',
            'actual_start' => 'date',
            'actual_end' => 'date',
            'budget_total' => 'decimal:2',
            'extras_total' => 'decimal:2',
            'cost_estimated' => 'decimal:2',
            'cost_real' => 'decimal:2',
            'portal_enabled' => 'boolean',
        ];
    }

    public static function codePrefix(): string
    {
        return 'OBR';
    }

    // -------------------------------------------------------- relaciones --
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function phases(): HasMany
    {
        return $this->hasMany(ProjectPhase::class)->orderBy('position');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(ProjectIncident::class)->latest('opened_at');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ProjectPhoto::class)->latest();
    }

    public function extras(): HasMany
    {
        return $this->hasMany(ProjectExtra::class)->latest();
    }

    public function updates(): HasMany
    {
        return $this->hasMany(ProjectUpdate::class)->latest('published_at');
    }

    public function providers(): BelongsToMany
    {
        return $this->belongsToMany(Provider::class)
            ->withPivot(['trade_id', 'agreed_amount', 'starts_on', 'ends_on', 'notes'])
            ->withTimestamps();
    }

    public function events(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProviderReview::class);
    }

    // ------------------------------------------------------------ scopes --
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(fn (Builder $q) => $q
            ->where('name', 'like', $like)
            ->orWhere('code', 'like', $like)
            ->orWhereHas('client', fn (Builder $c) => $c->where('name', 'like', $like)));
    }

    public function scopeStatus(Builder $query, ProjectStatus|string|null $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ProjectStatus::Planned, ProjectStatus::InProgress, ProjectStatus::Paused,
        ]);
    }

    public function scopeDelayed(Builder $query): Builder
    {
        return $query->open()
            ->whereNotNull('planned_end')
            ->whereDate('planned_end', '<', now());
    }

    // -------------------------------------------------------- indicadores --
    public function getContractedTotalAttribute(): float
    {
        return (float) $this->budget_total + (float) $this->extras_total;
    }

    public function getMarginAmountAttribute(): float
    {
        return round($this->contracted_total - (float) ($this->cost_real ?: $this->cost_estimated), 2);
    }

    public function getMarginPercentAttribute(): float
    {
        $total = $this->contracted_total;

        return $total > 0 ? round(($this->margin_amount / $total) * 100, 2) : 0.0;
    }

    public function isDelayed(): bool
    {
        return $this->planned_end !== null
            && $this->planned_end->isPast()
            && $this->status->is(ProjectStatus::Planned, ProjectStatus::InProgress, ProjectStatus::Paused);
    }

    public function getDaysRemainingAttribute(): ?int
    {
        return $this->planned_end?->diffInDays(now(), false) * -1;
    }

    public function openIncidentsCount(): int
    {
        return $this->incidents()
            ->whereIn('status', [IncidentStatus::Open, IncidentStatus::InProgress, IncidentStatus::WaitingClient])
            ->count();
    }

    public function pendingExtrasCount(): int
    {
        return $this->extras()->where('status', ExtraStatus::Sent)->count();
    }

    public function completedTasksCount(): int
    {
        return $this->tasks()->where('status', TaskStatus::Done)->count();
    }
}
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\QuoteStatus;
use App\Models\Concerns\HasDocuments;
use App\Models\Concerns\HasPortalTokens;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Presupuesto versionado.
 *
 * Reglas de negocio importantes:
 *  - Los importes NUNCA se escriben a mano: los calcula QuoteCalculator.
 *  - Una nueva versión se crea con QuoteVersionService, que clona el árbol
 *    completo (capítulos + partidas) y marca la anterior como Superseded.
 *  - El cliente decide a través de un PortalAccessToken de un solo propósito.
 */
class Quote extends Model
{
    use HasDocuments;
    use HasFactory;
    use HasPortalTokens;
    use RecordsActivity;
    use SoftDeletes;

    protected $fillable = [
        'number', 'client_id', 'property_id', 'project_id', 'parent_quote_id', 'version', 'status',
        'title', 'description', 'issue_date', 'valid_until', 'estimated_duration_days',
        'items_total', 'discount_type', 'discount_value', 'discount_amount',
        'taxable_base', 'tax_rate', 'tax_amount', 'total',
        'cost_total', 'margin_amount', 'margin_percent',
        'payment_terms', 'terms', 'exclusions', 'internal_notes',
        'sent_at', 'first_viewed_at', 'last_viewed_at', 'views_count',
        'decided_at', 'decision_ip', 'signer_name', 'signature_path', 'rejection_reason',
        'created_by',
    ];

    protected $attributes = [
        'status' => 'draft',
        'version' => 1,
        'discount_type' => 'none',
        'discount_value' => 0,
        'items_total' => 0,
        'discount_amount' => 0,
        'taxable_base' => 0,
        'tax_rate' => 21,
        'tax_amount' => 0,
        'total' => 0,
        'cost_total' => 0,
        'margin_amount' => 0,
        'margin_percent' => 0,
        'views_count' => 0,
    ];
    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'discount_type' => DiscountType::class,
            'issue_date' => 'date',
            'valid_until' => 'date',
            'items_total' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'taxable_base' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'cost_total' => 'decimal:2',
            'margin_amount' => 'decimal:2',
            'margin_percent' => 'decimal:2',
            'sent_at' => 'datetime',
            'first_viewed_at' => 'datetime',
            'last_viewed_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
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

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(QuoteSection::class)->orderBy('position');
    }

    public function items(): HasManyThrough
    {
        return $this->hasManyThrough(QuoteItem::class, QuoteSection::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_quote_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_quote_id')->orderBy('version');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Toda la familia de versiones, incluida esta. */
    public function versionChain(): Builder
    {
        $rootId = $this->parent_quote_id ?? $this->id;

        return static::query()
            ->where('id', $rootId)
            ->orWhere('parent_quote_id', $rootId)
            ->orderBy('version');
    }

    // ------------------------------------------------------------ scopes --
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(fn (Builder $q) => $q
            ->where('number', 'like', $like)
            ->orWhere('title', 'like', $like)
            ->orWhereHas('client', fn (Builder $c) => $c->where('name', 'like', $like)));
    }

    public function scopeStatus(Builder $query, QuoteStatus|string|null $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [QuoteStatus::Sent, QuoteStatus::Viewed]);
    }

    public function scopeExpiringSoon(Builder $query, int $days = 5): Builder
    {
        return $query->pending()
            ->whereNotNull('valid_until')
            ->whereBetween('valid_until', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }

    // -------------------------------------------------------- estado/UX --
    public function isEditable(): bool
    {
        return $this->status->is(QuoteStatus::Draft);
    }

    public function isDecidable(): bool
    {
        return $this->status->is(QuoteStatus::Sent, QuoteStatus::Viewed) && ! $this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->valid_until !== null && $this->valid_until->isPast();
    }

    public function isApproved(): bool
    {
        return $this->status->is(QuoteStatus::Approved);
    }

    public function getReferenceAttribute(): string
    {
        return $this->version > 1 ? "{$this->number} v{$this->version}" : $this->number;
    }
}
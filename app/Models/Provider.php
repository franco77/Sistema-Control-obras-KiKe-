<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentCategory;
use App\Enums\ProviderStatus;
use App\Enums\ProviderType;
use App\Models\Concerns\HasDocuments;
use App\Models\Concerns\HasPortalTokens;
use App\Models\Concerns\HasSequentialCode;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

/**
 * Proveedor de servicios: autónomo, empresa subcontratada o personal propio.
 */
class Provider extends Model
{
    use HasDocuments;
    use HasFactory;
    use HasPortalTokens;
    use HasSequentialCode;
    use Notifiable;
    use RecordsActivity;
    use SoftDeletes;

    /** Documentación legal obligatoria para poder asignar trabajos. */
    public const REQUIRED_DOCUMENTS = [
        DocumentCategory::Insurance->value,
        DocumentCategory::Prl->value,
        DocumentCategory::SocialSecurity->value,
    ];

    protected $fillable = [
        'code', 'type', 'status', 'name', 'legal_name', 'tax_id', 'email', 'phone',
        'address', 'postal_code', 'city', 'province', 'iban',
        'default_hourly_rate', 'irpf_rate', 'max_parallel_projects',
        'rating', 'jobs_count', 'radius_km', 'notes', 'created_by',
    ];

    protected $attributes = [
        'type' => 'freelancer',
        'status' => 'pending_docs',
        'max_parallel_projects' => 3,
        'jobs_count' => 0,
    ];
    protected function casts(): array
    {
        return [
            'type' => ProviderType::class,
            'status' => ProviderStatus::class,
            'default_hourly_rate' => 'decimal:2',
            'irpf_rate' => 'decimal:2',
            'rating' => 'decimal:2',
        ];
    }

    public static function codePrefix(): string
    {
        return 'PRV';
    }

    // -------------------------------------------------------- relaciones --
    public function trades(): BelongsToMany
    {
        return $this->belongsToMany(Trade::class)
            ->using(ProviderTrade::class)
            ->withPivot(['is_primary', 'hourly_rate', 'experience_years', 'notes'])
            ->withTimestamps();
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(ProviderAvailability::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
            ->withPivot(['trade_id', 'agreed_amount', 'starts_on', 'ends_on', 'notes'])
            ->withTimestamps();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProviderReview::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(ProjectIncident::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
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
            ->orWhere('legal_name', 'like', $like)
            ->orWhere('code', 'like', $like)
            ->orWhere('tax_id', 'like', $like)
            ->orWhere('email', 'like', $like)
            ->orWhere('phone', 'like', $like));
    }

    public function scopeWithTrade(Builder $query, int|string|null $trade): Builder
    {
        return $trade
            ? $query->whereHas('trades', fn (Builder $q) => $q->where('trades.id', $trade))
            : $query;
    }

    public function scopeAssignable(Builder $query): Builder
    {
        return $query->where('status', ProviderStatus::Active);
    }

    /** Proveedores sin solapes en el rango indicado. */
    public function scopeAvailableBetween(Builder $query, string $from, string $to): Builder
    {
        return $query->whereDoesntHave('availabilities', fn (Builder $q) => $q
            ->whereIn('type', ['unavailable', 'vacation'])
            ->where('starts_on', '<=', $to)
            ->where('ends_on', '>=', $from));
    }

    // ---------------------------------------------------------- accessors --
    /** Categorías de documentación obligatoria que faltan o están caducadas. */
    public function getMissingDocumentsAttribute(): array
    {
        $valid = $this->loadMissing('documents')->documents
            ->filter(fn (Document $doc) => ! $doc->isExpired())
            ->pluck('category')
            ->map(fn ($category) => $category instanceof DocumentCategory ? $category->value : $category)
            ->all();

        return array_values(array_diff(self::REQUIRED_DOCUMENTS, $valid));
    }

    public function hasValidDocumentation(): bool
    {
        return $this->missing_documents === [];
    }

    public function getPrimaryTradeAttribute(): ?Trade
    {
        $trades = $this->loadMissing('trades')->trades;

        return $trades->firstWhere('pivot.is_primary', true) ?? $trades->first();
    }
}
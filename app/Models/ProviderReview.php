<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Valoración interna del proveedor al cerrar una obra.
 * Al guardarse recalcula la media almacenada en providers.rating.
 */
class ProviderReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id', 'project_id', 'user_id',
        'quality', 'punctuality', 'tidiness', 'communication', 'score', 'comment',
    ];

    protected function casts(): array
    {
        return ['score' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $review): void {
            $review->score = round((
                $review->quality + $review->punctuality +
                $review->tidiness + $review->communication
            ) / 4, 2);
        });

        static::saved(fn (self $review) => $review->syncProviderRating());
        static::deleted(fn (self $review) => $review->syncProviderRating());
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function syncProviderRating(): void
    {
        $this->provider?->forceFill([
            'rating' => static::where('provider_id', $this->provider_id)->avg('score'),
        ])->saveQuietly();
    }
}
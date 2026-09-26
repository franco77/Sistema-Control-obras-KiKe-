<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Documento adjunto polimórfico. Los ficheros viven en un disco privado y
 * se sirven mediante rutas con control de acceso, nunca por URL directa.
 */
class Document extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'documentable_type', 'documentable_id', 'category', 'name', 'description',
        'disk', 'path', 'original_name', 'mime', 'size', 'hash',
        'issued_on', 'expires_on', 'is_required',
        'visible_to_client', 'visible_to_provider', 'expiry_notified_at', 'uploaded_by',
    ];

    protected $attributes = [
        'category' => 'other',
        'disk' => 'documents',
        'size' => 0,
        'is_required' => false,
        'visible_to_client' => false,
        'visible_to_provider' => false,
    ];
    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
            'issued_on' => 'date',
            'expires_on' => 'date',
            'is_required' => 'boolean',
            'visible_to_client' => 'boolean',
            'visible_to_provider' => 'boolean',
            'expiry_notified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::forceDeleted(function (self $document): void {
            Storage::disk($document->disk)->delete($document->path);
        });
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeCategory(Builder $query, DocumentCategory|string|null $category): Builder
    {
        return $category ? $query->where('category', $category) : $query;
    }

    public function scopeVisibleToClient(Builder $query): Builder
    {
        return $query->where('visible_to_client', true);
    }

    public function scopeExpiring(Builder $query, int $days = 30): Builder
    {
        return $query->whereNotNull('expires_on')
            ->whereDate('expires_on', '<=', now()->addDays($days));
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expires_on')->whereDate('expires_on', '<', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_on !== null && $this->expires_on->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expires_on !== null
            && ! $this->isExpired()
            && $this->expires_on->lte(now()->addDays($days));
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = (int) $this->size;

        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($bytes < 1024) {
                return round($bytes, 1).' '.$unit;
            }
            $bytes /= 1024;
        }

        return round($bytes, 1).' TB';
    }

    public function exists(): bool
    {
        return Storage::disk($this->disk)->exists($this->path);
    }
}
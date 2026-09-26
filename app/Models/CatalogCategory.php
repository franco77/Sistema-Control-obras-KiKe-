<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Capítulo del catálogo de partidas (banco de precios propio).
 */
class CatalogCategory extends Model
{
    use HasFactory;

    protected $fillable = ['parent_id', 'trade_id', 'code', 'name', 'description', 'position', 'is_active'];

    protected $attributes = [
        'position' => 0,
        'is_active' => true,
    ];
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }

    public function trade(): BelongsTo
    {
        return $this->belongsTo(Trade::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CatalogItem::class);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id')->orderBy('position');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
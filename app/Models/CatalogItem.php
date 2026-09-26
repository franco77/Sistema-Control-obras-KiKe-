<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MeasurementUnit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Partida tipo reutilizable: alimenta el constructor de presupuestos
 * con precio de venta y coste interno de referencia.
 */
class CatalogItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'catalog_category_id', 'trade_id', 'code', 'name', 'description', 'unit',
        'unit_cost', 'unit_price', 'default_quantity', 'yield_per_day', 'is_active',
    ];

    protected $attributes = [
        'unit' => 'ud',
        'unit_cost' => 0,
        'unit_price' => 0,
        'default_quantity' => 1,
        'is_active' => true,
    ];
    protected function casts(): array
    {
        return [
            'unit' => MeasurementUnit::class,
            'unit_cost' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'default_quantity' => 'decimal:3',
            'yield_per_day' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CatalogCategory::class, 'catalog_category_id');
    }

    public function trade(): BelongsTo
    {
        return $this->belongsTo(Trade::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(fn (Builder $q) => $q
            ->where('name', 'like', $like)
            ->orWhere('code', 'like', $like)
            ->orWhere('description', 'like', $like));
    }

    public function getMarginPercentAttribute(): float
    {
        if ((float) $this->unit_price <= 0) {
            return 0.0;
        }

        return round((((float) $this->unit_price - (float) $this->unit_cost) / (float) $this->unit_price) * 100, 2);
    }
}
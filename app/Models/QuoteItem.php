<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MeasurementUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Partida del presupuesto. Los totales se recalculan en el propio modelo
 * para que ninguna ruta de escritura pueda dejar importes incoherentes.
 */
class QuoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_section_id', 'catalog_item_id', 'trade_id', 'code', 'name', 'description',
        'unit', 'quantity', 'unit_price', 'unit_cost', 'discount_percent',
        'total', 'cost_total', 'is_optional', 'is_included', 'position', 'notes',
    ];

    protected $attributes = [
        'unit' => 'ud',
        'quantity' => 1,
        'unit_price' => 0,
        'unit_cost' => 0,
        'discount_percent' => 0,
        'total' => 0,
        'cost_total' => 0,
        'is_optional' => false,
        'is_included' => true,
        'position' => 0,
    ];
    protected function casts(): array
    {
        return [
            'unit' => MeasurementUnit::class,
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'discount_percent' => 'decimal:2',
            'total' => 'decimal:2',
            'cost_total' => 'decimal:2',
            'is_optional' => 'boolean',
            'is_included' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $item): void {
            $gross = (float) $item->quantity * (float) $item->unit_price;
            $item->total = round($gross * (1 - ((float) $item->discount_percent / 100)), 2);
            $item->cost_total = round((float) $item->quantity * (float) $item->unit_cost, 2);
        });
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(QuoteSection::class, 'quote_section_id');
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    public function trade(): BelongsTo
    {
        return $this->belongsTo(Trade::class);
    }

    /** Solo cuentan para el total las partidas no opcionales o las aceptadas. */
    public function countsTowardsTotal(): bool
    {
        return ! $this->is_optional || $this->is_included;
    }

    public function getMarginAmountAttribute(): float
    {
        return round((float) $this->total - (float) $this->cost_total, 2);
    }
}
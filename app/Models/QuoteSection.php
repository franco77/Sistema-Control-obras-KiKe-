<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Capítulo del presupuesto. Agrupa partidas y suele corresponderse
 * 1:1 con una fase de obra al convertir el presupuesto en proyecto.
 */
class QuoteSection extends Model
{
    use HasFactory;

    protected $fillable = ['quote_id', 'trade_id', 'name', 'description', 'position', 'subtotal', 'cost_subtotal'];

    protected $attributes = [
        'position' => 0,
        'subtotal' => 0,
        'cost_subtotal' => 0,
    ];
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'cost_subtotal' => 'decimal:2',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function trade(): BelongsTo
    {
        return $this->belongsTo(Trade::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('position');
    }
}
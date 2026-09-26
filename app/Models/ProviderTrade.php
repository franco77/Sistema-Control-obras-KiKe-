<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivote proveedor↔oficio con tarifa y experiencia específicas del oficio.
 */
class ProviderTrade extends Pivot
{
    protected $table = 'provider_trade';

    public $incrementing = true;

    protected $fillable = ['provider_id', 'trade_id', 'is_primary', 'hourly_rate', 'experience_years', 'notes'];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'hourly_rate' => 'decimal:2',
        ];
    }
}
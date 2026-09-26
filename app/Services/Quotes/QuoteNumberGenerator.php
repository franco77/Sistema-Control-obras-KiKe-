<?php

declare(strict_types=1);

namespace App\Services\Quotes;

use App\Models\Quote;
use Illuminate\Support\Facades\DB;

/**
 * Numeración correlativa anual de presupuestos: PRE-2026-0001.
 * Las versiones de un mismo presupuesto comparten número y cambian version.
 */
class QuoteNumberGenerator
{
    public function next(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');
        $prefix = (string) setting('quotes.prefix', 'PRE').'-'.$year.'-';

        return DB::transaction(function () use ($prefix) {
            $last = Quote::withTrashed()
                ->where('number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('number')
                ->value('number');

            $sequence = $last ? ((int) substr((string) $last, -4)) + 1 : 1;

            return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        });
    }
}
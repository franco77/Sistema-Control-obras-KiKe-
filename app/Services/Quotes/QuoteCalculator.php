<?php

declare(strict_types=1);

namespace App\Services\Quotes;

use App\Enums\DiscountType;
use App\Models\Quote;
use App\Models\QuoteSection;

/**
 * Única fuente de verdad de los importes de un presupuesto.
 *
 * Recalcula de abajo arriba: partida → capítulo → presupuesto, aplicando
 * descuento global e IVA, y deja también el coste interno y el margen.
 * Las partidas opcionales no aceptadas no suman.
 */
class QuoteCalculator
{
    public function recalculate(Quote $quote): Quote
    {
        // load() y no loadMissing(): quien llama suele haber modificado una
        // partida con una consulta directa, así que una colección ya cargada
        // estaría obsoleta y recalcularíamos sobre los valores antiguos.
        $quote->load('sections.items');

        $itemsTotal = 0.0;
        $costTotal = 0.0;

        $quote->sections->each(function (QuoteSection $section) use (&$itemsTotal, &$costTotal): void {
            $countable = $section->items->filter->countsTowardsTotal();

            $subtotal = round((float) $countable->sum('total'), 2);
            $costSubtotal = round((float) $countable->sum('cost_total'), 2);

            $section->forceFill([
                'subtotal' => $subtotal,
                'cost_subtotal' => $costSubtotal,
            ])->saveQuietly();

            $itemsTotal += $subtotal;
            $costTotal += $costSubtotal;
        });

        $itemsTotal = round($itemsTotal, 2);
        $discount = $this->discountAmount($quote, $itemsTotal);
        $taxableBase = round($itemsTotal - $discount, 2);
        $taxAmount = round($taxableBase * ((float) $quote->tax_rate / 100), 2);
        $total = round($taxableBase + $taxAmount, 2);

        $marginAmount = round($taxableBase - $costTotal, 2);
        $marginPercent = $taxableBase > 0 ? round(($marginAmount / $taxableBase) * 100, 2) : 0.0;

        $quote->forceFill([
            'items_total' => $itemsTotal,
            'discount_amount' => $discount,
            'taxable_base' => $taxableBase,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'cost_total' => round($costTotal, 2),
            'margin_amount' => $marginAmount,
            'margin_percent' => $marginPercent,
        ])->save();

        return $quote;
    }

    private function discountAmount(Quote $quote, float $itemsTotal): float
    {
        return match ($quote->discount_type) {
            DiscountType::Percent => round($itemsTotal * ((float) $quote->discount_value / 100), 2),
            DiscountType::Amount => min(round((float) $quote->discount_value, 2), $itemsTotal),
            default => 0.0,
        };
    }
}
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\DiscountType;
use App\Models\Client;
use App\Models\Quote;
use App\Services\Quotes\QuoteCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaseline();
    }

    private function quoteWithItems(array $items, array $attributes = []): Quote
    {
        $client = Client::create(['name' => 'Cliente Test']);

        $quote = Quote::create(array_merge([
            'number' => 'PRE-2026-0001',
            'client_id' => $client->id,
            'title' => 'Obra de prueba',
            'issue_date' => now()->toDateString(),
            'tax_rate' => 21,
        ], $attributes));

        $section = $quote->sections()->create(['name' => 'Capítulo 1']);

        foreach ($items as $item) {
            $section->items()->create($item + ['name' => 'Partida']);
        }

        return $quote;
    }

    public function test_suma_partidas_e_iva(): void
    {
        $quote = $this->quoteWithItems([
            ['quantity' => 10, 'unit_price' => 20, 'unit_cost' => 12],
            ['quantity' => 2, 'unit_price' => 100, 'unit_cost' => 55],
        ]);

        app(QuoteCalculator::class)->recalculate($quote);

        $this->assertEquals(400.00, (float) $quote->items_total);
        $this->assertEquals(400.00, (float) $quote->taxable_base);
        $this->assertEquals(84.00, (float) $quote->tax_amount);
        $this->assertEquals(484.00, (float) $quote->total);
        $this->assertEquals(230.00, (float) $quote->cost_total);
        $this->assertEquals(170.00, (float) $quote->margin_amount);
        $this->assertEquals(42.50, (float) $quote->margin_percent);
    }

    public function test_descuento_por_porcentaje(): void
    {
        $quote = $this->quoteWithItems(
            [['quantity' => 1, 'unit_price' => 1000, 'unit_cost' => 600]],
            ['discount_type' => DiscountType::Percent, 'discount_value' => 10],
        );

        app(QuoteCalculator::class)->recalculate($quote);

        $this->assertEquals(100.00, (float) $quote->discount_amount);
        $this->assertEquals(900.00, (float) $quote->taxable_base);
        $this->assertEquals(1089.00, (float) $quote->total);
    }

    public function test_descuento_fijo_no_supera_el_importe(): void
    {
        $quote = $this->quoteWithItems(
            [['quantity' => 1, 'unit_price' => 100, 'unit_cost' => 50]],
            ['discount_type' => DiscountType::Amount, 'discount_value' => 500],
        );

        app(QuoteCalculator::class)->recalculate($quote);

        $this->assertEquals(100.00, (float) $quote->discount_amount);
        $this->assertEquals(0.00, (float) $quote->taxable_base);
    }

    public function test_descuento_de_linea(): void
    {
        $quote = $this->quoteWithItems([
            ['quantity' => 10, 'unit_price' => 10, 'unit_cost' => 5, 'discount_percent' => 20],
        ]);

        app(QuoteCalculator::class)->recalculate($quote);

        $this->assertEquals(80.00, (float) $quote->items_total);
    }

    public function test_las_partidas_opcionales_no_aceptadas_no_suman(): void
    {
        $quote = $this->quoteWithItems([
            ['quantity' => 1, 'unit_price' => 500, 'unit_cost' => 300],
            ['quantity' => 1, 'unit_price' => 200, 'unit_cost' => 100, 'is_optional' => true, 'is_included' => false],
            ['quantity' => 1, 'unit_price' => 300, 'unit_cost' => 150, 'is_optional' => true, 'is_included' => true],
        ]);

        app(QuoteCalculator::class)->recalculate($quote);

        $this->assertEquals(800.00, (float) $quote->items_total);
    }
}
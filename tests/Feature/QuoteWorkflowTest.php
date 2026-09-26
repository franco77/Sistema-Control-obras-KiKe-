<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\QuoteStatus;
use App\Exceptions\InvalidTransitionException;
use App\Models\Client;
use App\Models\Quote;
use App\Services\Quotes\QuoteCalculator;
use App\Services\Quotes\QuoteConversionService;
use App\Services\Quotes\QuoteVersionService;
use App\Services\Quotes\QuoteWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class QuoteWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaseline();
        Notification::fake();
    }

    private function draft(): Quote
    {
        $client = Client::create(['name' => 'Carmen Ruiz', 'email' => 'carmen@test.local']);

        $quote = Quote::create([
            'number' => 'PRE-2026-0001',
            'client_id' => $client->id,
            'title' => 'Reforma de baño',
            'issue_date' => now()->toDateString(),
            'tax_rate' => 21,
            'estimated_duration_days' => 20,
        ]);

        $section = $quote->sections()->create(['name' => 'Fontanería']);
        $section->items()->create(['name' => 'Cambio de bajante', 'quantity' => 5, 'unit_price' => 60, 'unit_cost' => 32]);
        $section->items()->create(['name' => 'Plato de ducha', 'quantity' => 1, 'unit_price' => 180, 'unit_cost' => 95]);

        return app(QuoteCalculator::class)->recalculate($quote);
    }

    public function test_enviar_emite_un_token_de_portal_y_cambia_de_estado(): void
    {
        $quote = $this->draft();

        app(QuoteWorkflow::class)->send($quote);

        $this->assertSame(QuoteStatus::Sent, $quote->fresh()->status);
        $this->assertNotNull($quote->fresh()->sent_at);
        $this->assertSame(1, $quote->portalTokens()->count());

        $token = $quote->portalTokens()->first();
        $this->assertTrue($token->can('quote.decide'));
        $this->assertFalse($token->can('extra.decide'));
    }

    public function test_no_se_puede_aprobar_un_borrador(): void
    {
        $this->expectException(InvalidTransitionException::class);

        app(QuoteWorkflow::class)->approve($this->draft(), 'Carmen Ruiz');
    }

    public function test_aprobar_registra_firmante_e_ip(): void
    {
        $quote = $this->draft();
        $workflow = app(QuoteWorkflow::class);

        $workflow->send($quote);
        $workflow->approve($quote, 'Carmen Ruiz', '10.0.0.5');

        $quote->refresh();

        $this->assertSame(QuoteStatus::Approved, $quote->status);
        $this->assertSame('Carmen Ruiz', $quote->signer_name);
        $this->assertSame('10.0.0.5', $quote->decision_ip);
        $this->assertDatabaseHas('activities', ['event' => 'quote.approved']);
    }

    public function test_un_presupuesto_caducado_no_se_puede_aprobar(): void
    {
        $quote = $this->draft();
        app(QuoteWorkflow::class)->send($quote);

        $quote->forceFill(['valid_until' => now()->subDay()])->save();

        $this->expectException(InvalidTransitionException::class);

        app(QuoteWorkflow::class)->approve($quote->fresh(), 'Carmen Ruiz');
    }

    public function test_nueva_version_clona_el_arbol_y_sustituye_la_anterior(): void
    {
        $quote = $this->draft();
        app(QuoteWorkflow::class)->send($quote);

        $v2 = app(QuoteVersionService::class)->createNewVersion($quote->fresh());

        $this->assertSame(2, $v2->version);
        $this->assertSame(QuoteStatus::Draft, $v2->status);
        $this->assertSame(QuoteStatus::Superseded, $quote->fresh()->status);
        $this->assertSame($quote->id, $v2->parent_quote_id);
        $this->assertSame(2, $v2->items()->count());
        $this->assertEquals((float) $quote->total, (float) $v2->total);

        // Los enlaces de la versión anterior dejan de funcionar.
        $this->assertNull($quote->portalTokens()->active()->first());
    }

    public function test_conversion_a_obra_mapea_capitulos_y_partidas(): void
    {
        $quote = $this->draft();
        $workflow = app(QuoteWorkflow::class);

        $workflow->send($quote);
        $workflow->approve($quote, 'Carmen Ruiz');

        $project = app(QuoteConversionService::class)->convert($quote->fresh());

        $this->assertSame(1, $project->phases()->count());
        $this->assertSame(2, $project->tasks()->count());
        $this->assertEquals((float) $quote->fresh()->taxable_base, (float) $project->budget_total);
        $this->assertEquals((float) $quote->fresh()->cost_total, (float) $project->cost_estimated);
        $this->assertSame($project->id, $quote->fresh()->project_id);
    }

    public function test_no_se_convierte_dos_veces_el_mismo_presupuesto(): void
    {
        $quote = $this->draft();
        $workflow = app(QuoteWorkflow::class);
        $workflow->send($quote);
        $workflow->approve($quote, 'Carmen Ruiz');

        app(QuoteConversionService::class)->convert($quote->fresh());

        $this->expectException(InvalidTransitionException::class);

        app(QuoteConversionService::class)->convert($quote->fresh());
    }
}
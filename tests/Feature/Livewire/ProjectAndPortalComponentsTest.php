<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\Conversations\ConversationShow;
use App\Livewire\Admin\Projects\ExtraManager;
use App\Livewire\Admin\Projects\IncidentManager;
use App\Livewire\Admin\Projects\ProjectShow;
use App\Livewire\Admin\Projects\UpdateComposer;
use App\Livewire\Admin\Providers\ProviderForm;
use App\Livewire\Admin\Providers\ProviderShow;
use App\Livewire\Admin\Quotes\QuoteShow;
use App\Livewire\Admin\Shared\DocumentManager;
use App\Livewire\Portal\ExtraDecision;
use App\Livewire\Portal\ProjectExtras;
use App\Livewire\Portal\ProjectMessages;
use App\Livewire\Portal\QuoteReview;
use App\Models\Client;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\Provider;
use App\Models\Quote;
use App\Models\Trade;
use App\Services\Portal\PortalTokenService;
use App\Services\Quotes\QuoteCalculator;
use App\Services\Quotes\QuoteWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Acciones de los componentes de obra, proveedores y portal.
 */
class ProjectAndPortalComponentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaseline();
        Notification::fake();
    }

    private function project(): Project
    {
        $client = Client::create(['name' => 'Carmen Ruiz', 'email' => 'carmen@test.local']);

        return Project::create([
            'client_id' => $client->id,
            'name' => 'Reforma piso',
            'status' => 'in_progress',
            'budget_total' => 20000,
            'planned_end' => now()->addDays(30)->toDateString(),
        ]);
    }

    private function sentQuote(): Quote
    {
        $client = Client::create(['name' => 'Carmen Ruiz', 'email' => 'carmen@test.local']);

        $quote = Quote::create([
            'number' => 'PRE-2026-0001',
            'client_id' => $client->id,
            'title' => 'Reforma de baño',
            'issue_date' => now()->toDateString(),
        ]);

        $section = $quote->sections()->create(['name' => 'Fontanería']);
        $section->items()->create(['name' => 'Bajante', 'quantity' => 5, 'unit_price' => 60, 'unit_cost' => 32]);
        $section->items()->create([
            'name' => 'Mampara', 'quantity' => 1, 'unit_price' => 400, 'unit_cost' => 220,
            'is_optional' => true, 'is_included' => false,
        ]);

        app(QuoteCalculator::class)->recalculate($quote);
        app(QuoteWorkflow::class)->send($quote);

        return $quote->fresh();
    }

    /** Abre una sesión de portal como lo haría el enlace del email. */
    private function enterPortal($tokenable, array $abilities, string $audience = 'client'): void
    {
        app(PortalTokenService::class)->issue($tokenable, $abilities, $audience);

        Session::put('portal.token_id', $tokenable->portalTokens()->latest('id')->first()->id);
    }

    // ================================================================ PANEL
    public function test_registrar_y_resolver_una_incidencia(): void
    {
        $this->actingAs($this->admin());
        $project = $this->project();

        $component = Livewire::test(IncidentManager::class, ['project' => $project])
            ->call('newIncident')
            ->set('form.title', 'Fuga en bajante')
            ->set('form.description', 'Aparece una fisura al retirar el alicatado')
            ->set('form.cost_impact', 350)
            ->call('save')
            ->assertHasNoErrors();

        $incident = $project->incidents()->first();
        $this->assertNotNull($incident);
        $this->assertNotNull($incident->code);

        $component->call('changeStatus', $incident->id, 'resolved');

        $this->assertSame('resolved', $incident->fresh()->status->value);
        $this->assertNotNull($incident->fresh()->resolved_at);
    }

    public function test_crear_y_enviar_un_extra(): void
    {
        $this->actingAs($this->admin());
        $project = $this->project();

        $component = Livewire::test(ExtraManager::class, ['project' => $project])
            ->call('newExtra')
            ->set('form.title', 'Sustitución de bajante')
            ->set('form.description', 'Cambio de 9 ml')
            ->set('form.amount', 780)
            ->set('form.extra_days', 2)
            ->call('save')
            ->assertHasNoErrors();

        $extra = $project->extras()->first();
        $this->assertEquals(943.80, (float) $extra->total);

        $component->call('send', $extra->id);

        $this->assertSame('sent', $extra->fresh()->status->value);
        $this->assertSame(1, $extra->portalTokens()->count());
    }

    public function test_publicar_un_parte_de_obra(): void
    {
        $this->actingAs($this->admin());
        $project = $this->project();

        Livewire::test(UpdateComposer::class, ['project' => $project])
            ->set('form.title', 'Semana 3')
            ->set('form.body', 'Terminada la fontanería.')
            ->call('save')
            ->assertHasNoErrors();

        $update = $project->updates()->first();

        $this->assertNotNull($update);
        $this->assertNotNull($update->published_at);
        $this->assertTrue($update->visible_to_client);
    }

    public function test_generar_el_enlace_del_portal_desde_la_obra(): void
    {
        $this->actingAs($this->admin());
        $project = $this->project();

        $component = Livewire::test(ProjectShow::class, ['project' => $project])
            ->call('generatePortalLink');

        $link = $component->get('portalLink');

        $this->assertNotNull($link);
        $this->assertStringContainsString('/portal/t/', $link);
        $this->assertSame(1, $project->portalTokens()->count());
    }

    public function test_cambiar_el_estado_de_la_obra(): void
    {
        $this->actingAs($this->admin());
        $project = $this->project();

        Livewire::test(ProjectShow::class, ['project' => $project])
            ->call('changeStatus', 'paused');

        $this->assertSame('paused', $project->fresh()->status->value);
        $this->assertDatabaseHas('activities', ['event' => 'project.status_changed']);
    }

    public function test_registrar_una_decision_manual_del_presupuesto(): void
    {
        $this->actingAs($this->admin());
        $quote = $this->sentQuote();

        Livewire::test(QuoteShow::class, ['quote' => $quote])
            ->call('openDecision', 'approve')
            ->assertSet('decisionType', 'approve')
            ->set('decisionSigner', 'Carmen Ruiz')
            ->call('recordDecision')
            ->assertHasNoErrors();

        $this->assertSame('approved', $quote->fresh()->status->value);
    }

    public function test_el_formulario_de_proveedor_carga_y_guarda_oficios(): void
    {
        $this->actingAs($this->admin());

        $trade = Trade::first();

        Livewire::test(ProviderForm::class)
            ->set('form.name', 'Fontanería Ríos')
            ->set('form.status', 'active')
            ->set("trades.{$trade->id}.selected", true)
            ->set("trades.{$trade->id}.is_primary", true)
            ->set("trades.{$trade->id}.hourly_rate", 28)
            ->call('save');

        $provider = Provider::first();

        $this->assertNotNull($provider);
        $this->assertTrue($provider->trades->contains('id', $trade->id));

        // Reabrir en modo edición debe recuperar los oficios marcados.
        Livewire::test(ProviderForm::class, ['provider' => $provider])
            ->assertSet('form.name', 'Fontanería Ríos')
            ->assertSet("trades.{$trade->id}.selected", true);
    }

    public function test_registrar_disponibilidad_del_proveedor(): void
    {
        $this->actingAs($this->admin());

        $provider = Provider::create(['name' => 'Pinturas Duna', 'status' => 'active']);

        Livewire::test(ProviderShow::class, ['provider' => $provider])
            ->set('availability.type', 'vacation')
            ->set('availability.starts_on', now()->addWeek()->toDateString())
            ->set('availability.ends_on', now()->addWeeks(2)->toDateString())
            ->call('addAvailability')
            ->assertHasNoErrors();

        $this->assertSame(1, $provider->availabilities()->count());
    }

    public function test_subir_un_documento_desde_el_gestor_reutilizable(): void
    {
        $this->actingAs($this->admin());
        Storage::fake('documents');

        $provider = Provider::create(['name' => 'Electricidad Vega', 'status' => 'active']);

        Livewire::test(DocumentManager::class, ['model' => $provider, 'categories' => ['insurance', 'prl']])
            ->set('category', 'insurance')
            ->set('file', UploadedFile::fake()->create('seguro.pdf', 120, 'application/pdf'))
            ->set('expires_on', now()->addYear()->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, $provider->documents()->count());
        Storage::disk('documents')->assertExists($provider->documents()->first()->path);
    }

    public function test_responder_a_un_cliente_desde_el_panel(): void
    {
        $this->actingAs($this->admin());
        $project = $this->project();

        $conversation = Conversation::create([
            'client_id' => $project->client_id,
            'project_id' => $project->id,
            'subject' => '¿Cuándo entran los pintores?',
            'unread_for_staff' => 1,
        ]);

        Livewire::test(ConversationShow::class, ['conversation' => $conversation])
            ->set('reply', 'La semana que viene, el martes.')
            ->call('send')
            ->assertHasNoErrors();

        $this->assertSame(1, $conversation->messages()->count());
        $this->assertSame('answered', $conversation->fresh()->status->value);
        $this->assertSame(1, $conversation->fresh()->unread_for_client);
    }

    // =============================================================== PORTAL
    public function test_el_cliente_aprueba_su_presupuesto_desde_el_portal(): void
    {
        $quote = $this->sentQuote();
        $this->enterPortal($quote, ['quote.view', 'quote.decide', 'quote.download']);

        Livewire::test(QuoteReview::class)
            ->set('signerName', 'Carmen Ruiz')
            ->set('acceptedTerms', true)
            ->call('approve')
            ->assertHasNoErrors();

        $this->assertSame('approved', $quote->fresh()->status->value);
        $this->assertSame('Carmen Ruiz', $quote->fresh()->signer_name);
    }

    public function test_aprobar_sin_aceptar_las_condiciones_falla(): void
    {
        $quote = $this->sentQuote();
        $this->enterPortal($quote, ['quote.view', 'quote.decide']);

        Livewire::test(QuoteReview::class)
            ->set('signerName', 'Carmen Ruiz')
            ->call('approve')
            ->assertHasErrors('acceptedTerms');

        $this->assertSame('viewed', $quote->fresh()->status->value);
    }

    public function test_el_cliente_activa_una_partida_opcional_y_sube_el_total(): void
    {
        $quote = $this->sentQuote();
        $this->enterPortal($quote, ['quote.view', 'quote.decide']);

        $optional = $quote->items()->where('is_optional', true)->first();
        $totalAntes = (float) $quote->taxable_base;

        Livewire::test(QuoteReview::class)->call('toggleOptional', $optional->id);

        $this->assertTrue($optional->fresh()->is_included);
        $this->assertEquals($totalAntes + 400, (float) $quote->fresh()->taxable_base);
    }

    public function test_el_cliente_rechaza_indicando_el_motivo(): void
    {
        $quote = $this->sentQuote();
        $this->enterPortal($quote, ['quote.view', 'quote.decide']);

        Livewire::test(QuoteReview::class)
            ->call('reject')
            ->assertHasErrors('rejectionReason');

        Livewire::test(QuoteReview::class)
            ->set('rejectionReason', 'Nos hemos decidido por otra empresa')
            ->call('reject')
            ->assertHasNoErrors();

        $this->assertSame('rejected', $quote->fresh()->status->value);
    }

    public function test_el_cliente_aprueba_un_extra_desde_el_portal_de_obra(): void
    {
        $this->actingAs($this->admin());
        $project = $this->project();

        $extra = $project->extras()->create([
            'title' => 'Bajante',
            'description' => 'Sustitución',
            'amount' => 1000,
            'extra_days' => 3,
        ]);

        app(\App\Services\Projects\ExtraWorkflow::class)->send($extra);

        auth()->logout();
        $this->enterPortal($project, ['project.view', 'extra.view', 'extra.decide']);

        Livewire::test(ProjectExtras::class)
            ->call('startDecision', $extra->id, 'approve')
            ->set('signerName', 'Carmen Ruiz')
            ->call('confirm')
            ->assertHasNoErrors();

        $this->assertSame('approved', $extra->fresh()->status->value);
        $this->assertEquals(1000.0, (float) $project->fresh()->extras_total);
    }

    public function test_el_extra_con_su_propio_enlace_se_decide_igual(): void
    {
        $this->actingAs($this->admin());
        $project = $this->project();

        $extra = $project->extras()->create([
            'title' => 'Bajante', 'description' => 'Sustitución', 'amount' => 500,
        ]);
        app(\App\Services\Projects\ExtraWorkflow::class)->send($extra);

        auth()->logout();
        $this->enterPortal($extra, ['extra.view', 'extra.decide']);

        Livewire::test(ExtraDecision::class)
            ->set('rejectionReason', 'Prefiero dejarlo para más adelante')
            ->call('reject')
            ->assertHasNoErrors();

        $this->assertSame('rejected', $extra->fresh()->status->value);
    }

    public function test_el_cliente_abre_un_hilo_de_mensajes(): void
    {
        $project = $this->project();
        $this->enterPortal($project, ['project.view', 'message.send']);

        Livewire::test(ProjectMessages::class)
            ->set('subject', '¿Podemos cambiar el color?')
            ->set('body', 'Estábamos pensando en un gris más claro.')
            ->call('send')
            ->assertHasNoErrors();

        $conversation = $project->conversations()->first();

        $this->assertNotNull($conversation);
        $this->assertSame(1, $conversation->messages()->count());
        $this->assertSame(1, $conversation->unread_for_staff);
    }

    public function test_sin_permiso_de_mensajes_no_se_puede_escribir(): void
    {
        $project = $this->project();
        $this->enterPortal($project, ['project.view']); // sin message.send

        Livewire::test(ProjectMessages::class)
            ->set('subject', 'Hola')
            ->set('body', 'Mensaje')
            ->call('send')
            ->assertForbidden();

        $this->assertSame(0, $project->conversations()->count());
    }
}

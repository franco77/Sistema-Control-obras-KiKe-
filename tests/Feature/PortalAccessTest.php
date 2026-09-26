<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\Quote;
use App\Services\Portal\PortalTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El portal sin login es la superficie más expuesta del sistema:
 * estas pruebas cubren caducidad, revocación y permisos del token.
 */
class PortalAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaseline();
    }

    private function project(): Project
    {
        $client = Client::create(['name' => 'Carmen Ruiz', 'email' => 'carmen@test.local']);

        return Project::create([
            'client_id' => $client->id,
            'name' => 'Reforma piso',
            'status' => 'in_progress',
        ]);
    }

    public function test_el_token_se_guarda_hasheado_nunca_en_claro(): void
    {
        $project = $this->project();
        $plain = app(PortalTokenService::class)->issue($project, ['project.view']);

        $this->assertDatabaseMissing('portal_access_tokens', ['token_hash' => $plain]);
        $this->assertDatabaseHas('portal_access_tokens', ['token_hash' => hash('sha256', $plain)]);
    }

    public function test_el_enlace_canjea_el_token_por_una_sesion_y_limpia_la_url(): void
    {
        $project = $this->project();
        $plain = app(PortalTokenService::class)->issue($project, ['project.view']);

        $this->get(route('portal.enter', ['token' => $plain]))
            ->assertRedirect(route('portal.project'));

        $this->assertNotNull(session('portal.token_id'));

        $this->get(route('portal.project'))->assertOk();
    }

    public function test_un_token_invalido_lleva_a_la_pantalla_de_enlace_caducado(): void
    {
        $this->get(route('portal.enter', ['token' => str_repeat('a', 48)]))
            ->assertRedirect(route('portal.expired'));
    }

    public function test_un_token_caducado_no_da_acceso(): void
    {
        $project = $this->project();
        $plain = app(PortalTokenService::class)->issue($project, ['project.view'], ttlDays: 1);

        $this->travel(2)->days();

        $this->get(route('portal.enter', ['token' => $plain]))
            ->assertRedirect(route('portal.expired'));
    }

    public function test_un_token_revocado_expulsa_aunque_haya_sesion_abierta(): void
    {
        $project = $this->project();
        $plain = app(PortalTokenService::class)->issue($project, ['project.view']);

        $this->get(route('portal.enter', ['token' => $plain]));
        $this->get(route('portal.project'))->assertOk();

        $project->portalTokens()->first()->revoke();

        $this->get(route('portal.project'))->assertRedirect(route('portal.expired'));
    }

    public function test_sin_sesion_de_portal_no_hay_acceso(): void
    {
        $this->get(route('portal.project'))->assertRedirect(route('portal.expired'));
    }

    public function test_el_token_de_presupuesto_no_abre_la_obra(): void
    {
        $client = Client::create(['name' => 'Carmen Ruiz']);

        $quote = Quote::create([
            'number' => 'PRE-2026-0001',
            'client_id' => $client->id,
            'title' => 'Reforma',
            'issue_date' => now()->toDateString(),
        ]);

        $plain = app(PortalTokenService::class)->issue($quote, ['quote.view', 'quote.decide']);

        $this->get(route('portal.enter', ['token' => $plain]))
            ->assertRedirect(route('portal.quote'));

        // La ruta de obra exige project.view, que este token no tiene.
        $this->get(route('portal.project'))->assertForbidden();
    }

    public function test_se_registra_el_uso_del_enlace(): void
    {
        $project = $this->project();
        $plain = app(PortalTokenService::class)->issue($project, ['project.view']);

        $this->get(route('portal.enter', ['token' => $plain]));

        $token = $project->portalTokens()->first();

        $this->assertSame(1, $token->uses);
        $this->assertNotNull($token->last_used_at);
    }
}
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ExtraStatus;
use App\Models\Client;
use App\Models\Project;
use App\Services\Projects\ExtraWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ExtraApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaseline();
        Notification::fake();
    }

    private function extra(): \App\Models\ProjectExtra
    {
        $client = Client::create(['name' => 'Carmen', 'email' => 'carmen@test.local']);

        $project = Project::create([
            'client_id' => $client->id,
            'name' => 'Reforma',
            'status' => 'in_progress',
            'budget_total' => 20000,
            'planned_end' => now()->addDays(30)->toDateString(),
        ]);

        return $project->extras()->create([
            'title' => 'Sustitución de bajante',
            'description' => 'Cambio de 9 ml de bajante',
            'amount' => 1000,
            'tax_rate' => 21,
            'cost_estimated' => 600,
            'extra_days' => 3,
        ]);
    }

    public function test_el_total_del_extra_se_calcula_solo(): void
    {
        $extra = $this->extra();

        $this->assertEquals(210.00, (float) $extra->tax_amount);
        $this->assertEquals(1210.00, (float) $extra->total);
    }

    public function test_enviar_emite_un_token_acotado_a_ese_extra(): void
    {
        $extra = $this->extra();

        app(ExtraWorkflow::class)->send($extra);

        $this->assertSame(ExtraStatus::Sent, $extra->fresh()->status);

        $token = $extra->portalTokens()->first();
        $this->assertTrue($token->can('extra.decide'));
        $this->assertFalse($token->can('project.view'));
    }

    public function test_aprobar_suma_al_contratado_y_amplia_el_plazo(): void
    {
        $extra = $this->extra();
        $workflow = app(ExtraWorkflow::class);

        $originalEnd = $extra->project->planned_end->copy();

        $workflow->send($extra);
        $workflow->approve($extra->fresh(), 'Carmen Ruiz', '10.0.0.9');

        $project = $extra->project->fresh();

        $this->assertEquals(1000.00, (float) $project->extras_total);
        $this->assertEquals(21000.00, $project->contracted_total);
        $this->assertSame($originalEnd->addDays(3)->toDateString(), $project->planned_end->toDateString());
    }

    public function test_un_extra_ya_decidido_no_admite_otra_decision(): void
    {
        $extra = $this->extra();
        $workflow = app(ExtraWorkflow::class);

        $workflow->send($extra);
        $workflow->approve($extra->fresh(), 'Carmen');

        $this->expectException(\App\Exceptions\InvalidTransitionException::class);

        $workflow->reject($extra->fresh(), 'Me lo he pensado mejor');
    }
}
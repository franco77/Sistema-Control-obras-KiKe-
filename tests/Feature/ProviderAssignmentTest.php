<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DocumentCategory;
use App\Enums\ProviderStatus;
use App\Enums\TaskStatus;
use App\Exceptions\ProviderNotAssignableException;
use App\Models\Client;
use App\Models\Project;
use App\Models\Provider;
use App\Services\Projects\TaskAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProviderAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaseline();
        Notification::fake();
    }

    private function task(): \App\Models\ProjectTask
    {
        $client = Client::create(['name' => 'Cliente']);

        $project = Project::create(['client_id' => $client->id, 'name' => 'Reforma', 'status' => 'in_progress']);
        $phase = $project->phases()->create(['name' => 'Fontanería']);

        return $project->tasks()->create([
            'project_phase_id' => $phase->id,
            'name' => 'Cambio de bajante',
            'planned_start' => now()->toDateString(),
            'planned_end' => now()->addDays(3)->toDateString(),
        ]);
    }

    private function provider(bool $withDocuments = true): Provider
    {
        $provider = Provider::create([
            'name' => 'Fontanería Ríos',
            'status' => ProviderStatus::Active,
            'email' => 'rios@test.local',
        ]);

        if ($withDocuments) {
            foreach (Provider::REQUIRED_DOCUMENTS as $category) {
                $provider->documents()->create([
                    'category' => $category,
                    'name' => DocumentCategory::from($category)->label(),
                    'path' => 'demo.pdf',
                    'expires_on' => now()->addYear(),
                ]);
            }
        }

        return $provider->fresh('documents');
    }

    public function test_asignar_marca_la_tarea_y_vincula_el_proveedor_a_la_obra(): void
    {
        $task = $this->task();
        $provider = $this->provider();

        app(TaskAssignmentService::class)->assign($task, $provider, notify: false);

        $task->refresh();

        $this->assertSame($provider->id, $task->provider_id);
        $this->assertSame(TaskStatus::Assigned, $task->status);
        $this->assertTrue($task->project->providers()->where('providers.id', $provider->id)->exists());
    }

    public function test_asignar_bloquea_el_calendario_del_proveedor(): void
    {
        $task = $this->task();
        $provider = $this->provider();

        app(TaskAssignmentService::class)->assign($task, $provider, notify: false);

        $block = $provider->availabilities()->where('project_id', $task->project_id)->first();

        $this->assertNotNull($block);
        $this->assertSame('booked', $block->type->value);
        $this->assertSame($task->planned_start->toDateString(), $block->starts_on->toDateString());
    }

    public function test_desasignar_libera_el_calendario(): void
    {
        $task = $this->task();
        $provider = $this->provider();
        $service = app(TaskAssignmentService::class);

        $service->assign($task, $provider, notify: false);
        $service->unassign($task->fresh());

        $this->assertNull($task->fresh()->provider_id);
        $this->assertSame(0, $provider->availabilities()->count());
    }

    public function test_no_se_asigna_un_proveedor_sin_documentacion_en_vigor(): void
    {
        $this->expectException(ProviderNotAssignableException::class);

        app(TaskAssignmentService::class)->assign($this->task(), $this->provider(withDocuments: false), notify: false);
    }

    public function test_no_se_asigna_un_proveedor_inactivo(): void
    {
        $provider = $this->provider();
        $provider->update(['status' => ProviderStatus::Inactive]);

        $this->expectException(ProviderNotAssignableException::class);

        app(TaskAssignmentService::class)->assign($this->task(), $provider->fresh('documents'), notify: false);
    }

    public function test_documentacion_caducada_cuenta_como_faltante(): void
    {
        $provider = $this->provider();
        $provider->documents()->update(['expires_on' => now()->subDay()]);

        $this->assertFalse($provider->fresh('documents')->hasValidDocumentation());
    }
}
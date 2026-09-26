<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ClientStatus;
use App\Enums\ClientType;
use App\Enums\DocumentCategory;
use App\Enums\EventType;
use App\Enums\IncidentSeverity;
use App\Enums\PropertyType;
use App\Enums\ProviderStatus;
use App\Enums\ProviderType;
use App\Enums\TaskStatus;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\Provider;
use App\Models\Quote;
use App\Models\Trade;
use App\Models\User;
use App\Services\Projects\ProjectProgressService;
use App\Services\Quotes\QuoteCalculator;
use App\Services\Quotes\QuoteConversionService;
use App\Services\Quotes\QuoteNumberGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

/**
 * Datos de demostración para desarrollo: dos clientes con inmuebles, cuatro
 * proveedores con documentación, un presupuesto aprobado convertido en obra
 * con avance real, y otro presupuesto a la espera de respuesta.
 *
 * Solo se ejecuta en entorno local.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // El seeder debe poder ejecutarse suelto (db:seed --class=DemoDataSeeder)
        // sin depender de que DatabaseSeeder haya creado antes los usuarios.
        $admin = $this->ensureUser('admin@crm.test', 'Administrador', 'admin', 'Dirección');
        $manager = $this->ensureUser('obra@crm.test', 'Laura Jefa de Obra', 'jefe de obra', 'Jefatura de obra');

        Auth::login($admin);

        $providers = $this->seedProviders();
        [$client, $property] = $this->seedClient();
        $quote = $this->seedApprovedQuote($client, $property);

        $project = app(QuoteConversionService::class)->convert($quote, [
            'planned_start' => now()->subWeeks(3)->toDateString(),
            'manager_id' => $manager?->id,
        ]);

        $this->advanceProject($project, $providers);
        $this->seedPendingQuote();

        Auth::logout();
    }

    private function ensureUser(string $email, string $name, string $role, string $position): User
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => 'password', 'position' => $position, 'email_verified_at' => now()],
        );

        if (\Spatie\Permission\Models\Role::where('name', $role)->exists()) {
            $user->syncRoles([$role]);
        }

        return $user;
    }

    /** @return \Illuminate\Support\Collection<int, Provider> */
    private function seedProviders()
    {
        $data = [
            ['Fontanería Ríos', 'Fontanería', '600111222', 28.00],
            ['Electricidad Vega', 'Electricidad', '600333444', 30.00],
            ['Reformas Peña (albañilería)', 'Albañilería', '600555666', 26.00],
            ['Pinturas Duna', 'Pintura', '600777888', 24.00],
        ];

        return collect($data)->map(function (array $row) {
            [$name, $tradeName, $phone, $rate] = $row;

            $provider = Provider::firstOrCreate(
                ['name' => $name],
                [
                    'type' => ProviderType::Freelancer,
                    'status' => ProviderStatus::Active,
                    'tax_id' => '0000000'.random_int(1, 9).'X',
                    'email' => str($name)->slug()->value().'@proveedor.test',
                    'phone' => $phone,
                    'city' => 'Madrid',
                    'province' => 'Madrid',
                    'default_hourly_rate' => $rate,
                    'irpf_rate' => 15,
                ],
            );

            if ($trade = Trade::where('name', $tradeName)->first()) {
                $provider->trades()->syncWithoutDetaching([
                    $trade->id => ['is_primary' => true, 'hourly_rate' => $rate, 'experience_years' => random_int(3, 20)],
                ]);
            }

            // Documentación legal vigente, para que sea asignable.
            foreach (Provider::REQUIRED_DOCUMENTS as $category) {
                $provider->documents()->firstOrCreate(
                    ['category' => $category],
                    [
                        'name' => DocumentCategory::from($category)->label(),
                        'disk' => 'documents',
                        'path' => 'demo/placeholder.pdf',
                        'mime' => 'application/pdf',
                        'size' => 0,
                        'issued_on' => now()->subMonths(3),
                        'expires_on' => now()->addMonths(9),
                        'is_required' => true,
                    ],
                );
            }

            return $provider;
        });
    }

    /** @return array{0: Client, 1: \App\Models\Property} */
    private function seedClient(): array
    {
        $client = Client::firstOrCreate(
            ['email' => 'carmen.ruiz@example.test'],
            [
                'type' => ClientType::Individual,
                'status' => ClientStatus::Active,
                'name' => 'Carmen Ruiz',
                'tax_id' => '12345678Z',
                'phone' => '655 123 456',
                'address' => 'C/ Bravo Murillo 120',
                'postal_code' => '28020',
                'city' => 'Madrid',
                'province' => 'Madrid',
                'source' => 'Recomendación',
            ],
        );

        $property = $client->properties()->firstOrCreate(
            ['alias' => 'Piso Chamberí'],
            [
                'type' => PropertyType::Apartment,
                'address' => 'C/ Eloy Gonzalo 27',
                'floor' => '3',
                'door' => 'B',
                'postal_code' => '28010',
                'city' => 'Madrid',
                'province' => 'Madrid',
                'built_area' => 92,
                'usable_area' => 84,
                'rooms' => 3,
                'bathrooms' => 2,
                'year_built' => 1968,
                'has_elevator' => true,
                'is_occupied' => false,
                'access_notes' => 'Portero de 8:00 a 15:00. Obras permitidas de 9:00 a 19:00 de lunes a viernes.',
            ],
        );

        return [$client, $property];
    }

    private function seedApprovedQuote(Client $client, $property): Quote
    {
        if ($existing = Quote::where('client_id', $client->id)->where('status', 'approved')->first()) {
            return $existing;
        }

        $quote = Quote::create([
            'number' => app(QuoteNumberGenerator::class)->next(),
            'client_id' => $client->id,
            'property_id' => $property->id,
            'title' => 'Reforma integral vivienda Chamberí',
            'description' => 'Reforma completa de 92 m²: distribución, instalaciones, baños, cocina y acabados.',
            'issue_date' => now()->subWeeks(7)->toDateString(),
            'valid_until' => now()->subWeeks(3)->toDateString(),
            'estimated_duration_days' => 75,
            'tax_rate' => 21,
            'payment_terms' => setting('quotes.payment_terms'),
            'terms' => setting('quotes.terms'),
            'exclusions' => 'No se incluyen electrodomésticos, mobiliario ni proyecto de arquitectura.',
            'status' => 'draft',
            'created_by' => Auth::id(),
        ]);

        $plan = [
            'Demolición y trabajos previos' => ['DEM-001' => 34, 'DEM-002' => 46, 'DEM-003' => 84, 'DEM-004' => 5, 'DEM-005' => 3],
            'Albañilería' => ['ALB-001' => 28, 'ALB-002' => 62, 'ALB-003' => 6, 'ALB-004' => 2],
            'Fontanería' => ['FON-001' => 14, 'FON-002' => 9, 'FON-003' => 2, 'FON-004' => 2],
            'Electricidad' => ['ELE-001' => 22, 'ELE-002' => 31, 'ELE-003' => 1, 'ELE-004' => 1],
            'Pladur y falsos techos' => ['PLA-001' => 84, 'PLA-002' => 36, 'PLA-003' => 12],
            'Revestimientos' => ['REV-001' => 46, 'REV-002' => 84, 'REV-003' => 58],
            'Carpintería' => ['CAR-001' => 6, 'CAR-002' => 4.2],
            'Pintura' => ['PIN-003' => 210, 'PIN-001' => 210, 'PIN-002' => 18],
            'Varios' => ['VAR-002' => 92, 'VAR-001' => 92],
        ];

        $position = 0;

        foreach ($plan as $sectionName => $items) {
            $section = $quote->sections()->create(['name' => $sectionName, 'position' => $position++]);

            $itemPosition = 0;

            foreach ($items as $code => $quantity) {
                $catalogItem = CatalogItem::where('code', $code)->first();

                if (! $catalogItem) {
                    continue;
                }

                $section->items()->create([
                    'catalog_item_id' => $catalogItem->id,
                    'trade_id' => $catalogItem->trade_id,
                    'code' => $catalogItem->code,
                    'name' => $catalogItem->name,
                    'description' => $catalogItem->description,
                    'unit' => $catalogItem->unit,
                    'quantity' => $quantity,
                    'unit_price' => $catalogItem->unit_price,
                    'unit_cost' => $catalogItem->unit_cost,
                    'position' => $itemPosition++,
                ]);
            }

            // El oficio del capitulo se hereda del de sus partidas: asi la
            // conversion a obra crea fases con oficio y se pueden asignar
            // proveedores automaticamente.
            $section->update([
                'trade_id' => $section->items()->whereNotNull('trade_id')->value('trade_id'),
            ]);
        }

        app(QuoteCalculator::class)->recalculate($quote);

        $quote->forceFill([
            'status' => 'approved',
            'sent_at' => now()->subWeeks(6),
            'first_viewed_at' => now()->subWeeks(6)->addHours(4),
            'last_viewed_at' => now()->subWeeks(5),
            'views_count' => 7,
            'decided_at' => now()->subWeeks(5),
            'decision_ip' => '85.60.12.44',
            'signer_name' => 'Carmen Ruiz',
        ])->save();

        return $quote;
    }

    private function advanceProject($project, $providers): void
    {
        $project->forceFill([
            'status' => 'in_progress',
            'actual_start' => now()->subWeeks(3)->toDateString(),
        ])->save();

        $byTrade = $providers->keyBy(fn (Provider $p) => $p->trades->first()?->name);

        // Reparte fechas y asigna proveedor por oficio de la fase.
        $cursor = now()->subWeeks(3)->copy();

        foreach ($project->phases()->with('tasks', 'trade')->orderBy('position')->get() as $index => $phase) {
            $phaseStart = $cursor->copy();
            $phaseEnd = $cursor->copy()->addDays(max(4, $phase->tasks->count() * 2));

            $phase->update(['planned_start' => $phaseStart, 'planned_end' => $phaseEnd]);

            $provider = $byTrade[$phase->trade?->name] ?? null;

            foreach ($phase->tasks as $taskIndex => $task) {
                $task->forceFill([
                    'provider_id' => $provider?->id,
                    'planned_start' => $phaseStart->copy()->addDays($taskIndex),
                    'planned_end' => $phaseStart->copy()->addDays($taskIndex + 1),
                    'status' => $index < 3 ? TaskStatus::Done : ($index === 3 ? TaskStatus::InProgress : TaskStatus::Pending),
                    'completed_at' => $index < 3 ? $phaseStart->copy()->addDays($taskIndex + 1) : null,
                    'cost_real' => $index < 3 ? $task->cost_estimated : 0,
                ])->save();

                if ($provider) {
                    $project->providers()->syncWithoutDetaching([$provider->id => ['trade_id' => $phase->trade_id]]);
                }
            }

            $cursor = $phaseEnd->copy()->addDay();
        }

        $project->update(['planned_end' => $cursor->toDateString()]);

        app(ProjectProgressService::class)->recalculate($project);

        // Diario de obra
        foreach ([
            ['Arrancamos', 'Hoy hemos empezado con el desmontaje y la retirada de escombros. La vivienda queda protegida con lonas y cartón en zonas comunes.', 21],
            ['Demolición terminada', 'Demolición completa y contenedores retirados. Mañana entra el equipo de albañilería para levantar la nueva distribución.', 14],
            ['Instalaciones en marcha', 'Fontanería y electricidad avanzan en paralelo. La semana que viene pasamos a falsos techos.', 5],
        ] as [$title, $body, $daysAgo]) {
            $project->updates()->firstOrCreate(
                ['title' => $title],
                [
                    'body' => $body,
                    'user_id' => $project->manager_id,
                    'progress_snapshot' => $project->progress,
                    'visible_to_client' => true,
                    'published_at' => now()->subDays($daysAgo),
                ],
            );
        }

        // Incidencia comunicada al cliente
        $project->incidents()->firstOrCreate(
            ['title' => 'Bajante general en mal estado'],
            [
                'description' => 'Al retirar el alicatado del baño principal aparece una bajante de fibrocemento con fisuras. Se recomienda sustituirla antes de cerrar el trasdosado.',
                'severity' => IncidentSeverity::High,
                'status' => 'in_progress',
                'opened_at' => now()->subDays(9),
                'cost_impact' => 0,
                'days_impact' => 2,
                'visible_to_client' => true,
                'reported_by' => $project->manager_id,
                'assigned_to' => $project->manager_id,
            ],
        );

        // Extra pendiente de aprobación del cliente
        $project->extras()->firstOrCreate(
            ['title' => 'Sustitución de bajante de fibrocemento'],
            [
                'description' => 'Sustitución de 9 ml de bajante por PVC Ø110 insonorizado, incluso apertura y cierre de trasdosado y pruebas de estanqueidad.',
                'justification' => 'La bajante existente presenta fisuras. Cerrarla sin sustituirla implicaría tener que volver a abrir el trasdosado ante la primera fuga.',
                'status' => 'sent',
                'amount' => 780,
                'tax_rate' => 21,
                'cost_estimated' => 430,
                'extra_days' => 2,
                'sent_at' => now()->subDays(6),
                'created_by' => $project->manager_id,
            ],
        );

        // Agenda
        $project->events()->firstOrCreate(
            ['title' => 'Visita de obra con la propiedad'],
            [
                'type' => EventType::Visit,
                'starts_at' => now()->addDays(3)->setTime(10, 0),
                'ends_at' => now()->addDays(3)->setTime(11, 0),
                'client_id' => $project->client_id,
                'property_id' => $project->property_id,
                'owner_id' => $project->manager_id,
                'location' => $project->property?->full_address,
                'visible_to_client' => true,
                'reminder_at' => now()->addDays(2)->setTime(18, 0),
            ],
        );
    }

    private function seedPendingQuote(): void
    {
        $client = Client::firstOrCreate(
            ['email' => 'comunidad.olmo@example.test'],
            [
                'type' => ClientType::Community,
                'status' => ClientStatus::Lead,
                'name' => 'C.P. Calle del Olmo 14',
                'legal_name' => 'Comunidad de Propietarios Olmo 14',
                'tax_id' => 'H86543210',
                'phone' => '913 445 566',
                'address' => 'C/ del Olmo 14',
                'postal_code' => '28012',
                'city' => 'Madrid',
                'province' => 'Madrid',
                'source' => 'Web',
            ],
        );

        $client->contacts()->firstOrCreate(
            ['name' => 'Julián Nieto'],
            ['role' => 'Presidente', 'email' => 'presidente.olmo@example.test', 'phone' => '699 112 233', 'is_primary' => true],
        );

        $property = $client->properties()->firstOrCreate(
            ['alias' => 'Portal y escalera'],
            [
                'type' => PropertyType::CommonArea,
                'address' => 'C/ del Olmo 14',
                'postal_code' => '28012',
                'city' => 'Madrid',
                'province' => 'Madrid',
                'built_area' => 140,
                'has_elevator' => false,
            ],
        );

        if (Quote::where('client_id', $client->id)->exists()) {
            return;
        }

        $quote = Quote::create([
            'number' => app(QuoteNumberGenerator::class)->next(),
            'client_id' => $client->id,
            'property_id' => $property->id,
            'title' => 'Rehabilitación de portal y caja de escalera',
            'description' => 'Renovación de acabados del portal, pintura de escalera e iluminación LED con detectores de presencia.',
            'issue_date' => now()->subDays(9)->toDateString(),
            'valid_until' => now()->addDays(21)->toDateString(),
            'estimated_duration_days' => 25,
            'tax_rate' => 21,
            'payment_terms' => setting('quotes.payment_terms'),
            'terms' => setting('quotes.terms'),
            'status' => 'sent',
            'sent_at' => now()->subDays(9),
            'first_viewed_at' => now()->subDays(8),
            'last_viewed_at' => now()->subDays(2),
            'views_count' => 4,
            'created_by' => Auth::id(),
        ]);

        $plan = [
            'Trabajos previos' => ['DEM-002' => 18, 'VAR-002' => 140],
            'Albañilería y revestimientos' => ['ALB-002' => 96, 'REV-001' => 28, 'REV-003' => 42],
            'Electricidad' => ['ELE-001' => 14, 'ELE-002' => 4],
            'Pintura' => ['PIN-003' => 310, 'PIN-001' => 310],
            'Varios' => ['VAR-001' => 140],
        ];

        $position = 0;

        foreach ($plan as $sectionName => $items) {
            $section = $quote->sections()->create(['name' => $sectionName, 'position' => $position++]);

            foreach ($items as $code => $quantity) {
                $catalogItem = CatalogItem::where('code', $code)->first();

                if (! $catalogItem) {
                    continue;
                }

                $section->items()->create([
                    'catalog_item_id' => $catalogItem->id,
                    'trade_id' => $catalogItem->trade_id,
                    'code' => $catalogItem->code,
                    'name' => $catalogItem->name,
                    'unit' => $catalogItem->unit,
                    'quantity' => $quantity,
                    'unit_price' => $catalogItem->unit_price,
                    'unit_cost' => $catalogItem->unit_cost,
                ]);
            }
        }

        app(QuoteCalculator::class)->recalculate($quote);
    }
}

<div>
    <x-page-header :title="$client->name"
                   :subtitle="$client->code.' · '.$client->type->label()"
                   :back="route('admin.clients.index')">
        <x-slot:actions>
            <x-badge :color="$client->status->color()" size="md" dot>{{ $client->status->label() }}</x-badge>
            <x-btn variant="secondary" :href="route('admin.clients.edit', $client)">Editar</x-btn>
            <x-btn :href="route('admin.quotes.create', ['cliente' => $client->id])" icon="plus">Presupuestar</x-btn>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-4">
        <x-stat label="Inmuebles" :value="$client->properties_count" />
        <x-stat label="Presupuestos" :value="$client->quotes_count" :hint="money($totals['approved']).' aprobado'" />
        <x-stat label="Obras" :value="$client->projects_count" color="indigo" />
        <x-stat label="Contratado" :value="money($totals['contracted'])" color="green" />
    </div>

    {{-- Pestañas --}}
    <div class="mt-6 border-b border-slate-200 dark:border-slate-700">
        <nav class="-mb-px flex gap-1 overflow-x-auto">
            @foreach ([
                'resumen' => 'Resumen',
                'inmuebles' => 'Inmuebles',
                'contactos' => 'Contactos',
                'presupuestos' => 'Presupuestos',
                'obras' => 'Obras',
                'documentos' => 'Documentos',
                'actividad' => 'Actividad',
            ] as $key => $label)
                <button wire:click="setTab('{{ $key }}')"
                        @class([
                            'whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-medium transition',
                            'border-indigo-600 text-indigo-600' => $tab === $key,
                            'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' => $tab !== $key,
                        ])>
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    <div class="mt-6">
        @if ($tab === 'resumen')
            <div class="grid gap-6 lg:grid-cols-2">
                <x-card title="Datos de contacto">
                    <dl class="grid grid-cols-3 gap-y-3 text-sm">
                        @foreach ([
                            'Razón social' => $client->legal_name,
                            'NIF / CIF' => $client->tax_id,
                            'Email' => $client->email,
                            'Teléfono' => $client->phone,
                            'Teléfono alt.' => $client->phone_alt,
                            'Dirección' => $client->full_address,
                            'Canal preferido' => ucfirst($client->preferred_channel),
                            'Origen' => $client->source,
                            'Responsable' => $client->owner?->name,
                        ] as $label => $value)
                            <dt class="col-span-1 text-slate-500">{{ $label }}</dt>
                            <dd class="col-span-2 text-slate-900 dark:text-slate-100">{{ $value ?: '—' }}</dd>
                        @endforeach
                    </dl>
                </x-card>

                <x-card title="Notas internas">
                    <p class="whitespace-pre-line text-sm text-slate-600 dark:text-slate-300">
                        {{ $client->notes ?: 'Sin notas.' }}
                    </p>
                </x-card>
            </div>

        @elseif ($tab === 'inmuebles')
            @livewire('admin.clients.property-manager', ['client' => $client], key('properties-'.$client->id))

        @elseif ($tab === 'contactos')
            @livewire('admin.clients.contact-manager', ['client' => $client], key('contacts-'.$client->id))

        @elseif ($tab === 'presupuestos')
            <x-card :padding="false">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500 dark:bg-slate-900/40">
                        <tr>
                            <th class="px-4 py-3">Referencia</th>
                            <th class="px-4 py-3">Título</th>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3 text-right">Importe</th>
                            <th class="px-4 py-3">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @forelse ($quotes as $quote)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.quotes.show', $quote) }}"
                                       class="text-sm font-medium text-indigo-600 hover:underline">{{ $quote->reference }}</a>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">{{ $quote->title }}</td>
                                <td class="px-4 py-3 text-sm text-slate-500">{{ $quote->issue_date->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-right text-sm font-medium tabular-nums">{{ money($quote->total) }}</td>
                                <td class="px-4 py-3"><x-badge :color="$quote->status->color()">{{ $quote->status->label() }}</x-badge></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-6"><x-empty-state title="Sin presupuestos" icon="document" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if ($quotes?->hasPages())
                    <div class="border-t border-slate-100 p-4 dark:border-slate-700">{{ $quotes->links() }}</div>
                @endif
            </x-card>

        @elseif ($tab === 'obras')
            <div class="grid gap-4 sm:grid-cols-2">
                @forelse ($projects as $project)
                    <a href="{{ route('admin.projects.show', $project) }}"
                       class="block rounded-xl border border-slate-200 bg-white p-4 transition hover:border-indigo-300 hover:shadow dark:border-slate-700 dark:bg-slate-800">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{ $project->name }}</p>
                                <p class="text-xs text-slate-500">{{ $project->code }} · {{ $project->property?->alias }}</p>
                            </div>
                            <x-badge :color="$project->status->color()" size="xs">{{ $project->status->label() }}</x-badge>
                        </div>
                        <x-progress :value="$project->progress" class="mt-3" size="sm" />
                        <p class="mt-2 text-xs text-slate-500">
                            {{ money($project->budget_total) }}
                            @if ($project->planned_end) · fin previsto {{ $project->planned_end->format('d/m/Y') }} @endif
                        </p>
                    </a>
                @empty
                    <div class="sm:col-span-2"><x-empty-state title="Sin obras todavía" icon="building" /></div>
                @endforelse
            </div>

        @elseif ($tab === 'documentos')
            <x-card title="Documentación del cliente">
                @livewire('admin.shared.document-manager', [
                    'model' => $client,
                    'categories' => $documentCategories,
                ], key('docs-client-'.$client->id))
            </x-card>

        @elseif ($tab === 'actividad')
            <x-card title="Traza de actividad" :padding="false">
                @forelse ($activities as $activity)
                    <div class="flex gap-3 border-b border-slate-100 px-5 py-3 last:border-0 dark:border-slate-700">
                        <div class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-indigo-400"></div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-slate-900 dark:text-slate-100">{{ $activity->description ?: $activity->event }}</p>
                            <p class="text-xs text-slate-500">
                                {{ $activity->causer_name }} · {{ $activity->created_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-slate-500">Sin actividad registrada.</p>
                @endforelse
            </x-card>
        @endif
    </div>
</div>
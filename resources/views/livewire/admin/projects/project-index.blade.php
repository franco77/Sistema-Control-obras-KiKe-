<div>
    <x-page-header title="Obras" subtitle="Ejecución, avance y rentabilidad">
        <x-slot:actions>
            <x-btn variant="secondary" :href="route('admin.projects.board')">Vista tablero</x-btn>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-4">
        <x-stat label="Obras abiertas" :value="$summary['open']" />
        <x-stat label="Retrasadas" :value="$summary['delayed']" :color="$summary['delayed'] ? 'red' : 'slate'" />
        <x-stat label="Contratado en curso" :value="money($summary['contracted'])" color="indigo" />
        <x-stat label="Extras aprobados" :value="money($summary['extras'])" color="green" />
    </div>

    <x-card :padding="false">
        <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-4 dark:border-slate-700">
            <div class="min-w-64 flex-1">
                <x-input wire:model.live.debounce.400ms="search" placeholder="Buscar por obra, código o cliente…" />
            </div>
            <x-select wire:model.live="status" class="w-44">
                <option value="">Todos los estados</option>
                @foreach ($statuses as $case)<option value="{{ $case->value }}">{{ $case->label() }}</option>@endforeach
            </x-select>
            <x-select wire:model.live="manager" class="w-44">
                <option value="">Cualquier jefe de obra</option>
                @foreach ($managers as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
            </x-select>
            <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                <input type="checkbox" wire:model.live="onlyDelayed" class="rounded border-slate-300 text-indigo-600">
                Solo retrasadas
            </label>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500 dark:bg-slate-900/40">
                    <tr>
                        <th class="px-4 py-3"><button wire:click="sortBy('code')">Obra</button></th>
                        <th class="px-4 py-3">Cliente</th>
                        <th class="px-4 py-3"><button wire:click="sortBy('planned_end')">Plazo</button></th>
                        <th class="px-4 py-3 w-40"><button wire:click="sortBy('progress')">Avance</button></th>
                        <th class="px-4 py-3 text-right"><button wire:click="sortBy('budget_total')">Contratado</button></th>
                        <th class="px-4 py-3 text-center">Incid.</th>
                        <th class="px-4 py-3">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse ($projects as $project)
                        <tr wire:key="proj-{{ $project->id }}" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.projects.show', $project) }}"
                                   class="text-sm font-medium text-slate-900 hover:text-indigo-600 dark:text-slate-100">{{ $project->name }}</a>
                                <p class="text-xs text-slate-500">
                                    {{ $project->code }}
                                    @if ($project->manager) · {{ $project->manager->name }} @endif
                                </p>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                {{ $project->client->name }}
                                <p class="truncate text-xs text-slate-500">{{ $project->property?->alias }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <p class="text-slate-600 dark:text-slate-300">
                                    {{ $project->planned_start?->format('d/m/y') ?? '—' }} → {{ $project->planned_end?->format('d/m/y') ?? '—' }}
                                </p>
                                @if ($project->isDelayed())
                                    <span class="text-red-600">retrasada</span>
                                @endif
                            </td>
                            <td class="px-4 py-3"><x-progress :value="$project->progress" size="sm" /></td>
                            <td class="px-4 py-3 text-right text-sm tabular-nums">
                                {{ money($project->contracted_total) }}
                                @if ($project->extras_total > 0)
                                    <p class="text-xs text-emerald-600">+{{ money($project->extras_total) }} extras</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($project->open_incidents_count > 0)
                                    <x-badge color="red" size="xs">{{ $project->open_incidents_count }}</x-badge>
                                @else
                                    <span class="text-xs text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3"><x-badge :color="$project->status->color()" dot>{{ $project->status->label() }}</x-badge></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-6"><x-empty-state title="Sin obras"
                            description="Las obras se crean al aprobar y convertir un presupuesto." icon="building" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($projects->hasPages())
            <div class="border-t border-slate-100 p-4 dark:border-slate-700">{{ $projects->links() }}</div>
        @endif
    </x-card>
</div>
<div>
    <x-page-header :title="$project->name"
                   :subtitle="$project->code.' · '.$project->client->name.($project->property ? ' · '.$project->property->alias : '')"
                   :back="route('admin.projects.index')">
        <x-slot:actions>
            <x-badge :color="$project->status->color()" size="md" dot>{{ $project->status->label() }}</x-badge>

            @can('update', $project)
                <x-select wire:change="changeStatus($event.target.value)" class="w-40 text-xs">
                    @foreach ($statuses as $case)
                        <option value="{{ $case->value }}" @selected($case === $project->status)>{{ $case->label() }}</option>
                    @endforeach
                </x-select>

                <x-btn variant="secondary" wire:click="generatePortalLink">Enlace del cliente</x-btn>

                @if ($project->status->value !== 'finished')
                    <x-btn variant="success" wire:click="finishProject"
                           wire:confirm="Se marcará la obra como entregada y se avisará al cliente. ¿Continuar?">
                        Finalizar obra
                    </x-btn>
                @endif
            @endcan
        </x-slot:actions>
    </x-page-header>

    @if ($portalLink)
        <div class="mb-6 rounded-lg border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-500/30 dark:bg-indigo-500/10" x-data>
            <p class="text-xs font-medium text-indigo-900 dark:text-indigo-200">Enlace del portal del cliente:</p>
            <div class="mt-2 flex gap-2">
                <input type="text" readonly value="{{ $portalLink }}" x-ref="link" onclick="this.select()"
                       class="w-full rounded-lg border-indigo-200 bg-white px-3 py-1.5 font-mono text-xs dark:bg-slate-900">
                <x-btn size="sm" x-on:click="navigator.clipboard.writeText($refs.link.value)">Copiar</x-btn>
            </div>
        </div>
    @endif

    {{-- Indicadores --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-card class="sm:col-span-2 xl:col-span-1">
            <div class="flex flex-col items-center">
                <x-portal.ring :value="$project->progress" :size="110" />
            </div>
        </x-card>

        <x-stat label="Plazo"
                :value="$project->planned_end?->format('d/m/Y') ?? '—'"
                :hint="$project->isDelayed() ? 'Retrasada' : ($project->planned_end ? 'quedan '.$project->planned_end->diffInDays(now()).' días' : null)"
                :color="$project->isDelayed() ? 'red' : 'slate'" />

        <x-stat label="Contratado" :value="money($costSummary['contracted'])"
                :hint="$project->extras_total > 0 ? money($project->extras_total).' en extras' : 'sin extras'" />

        @can('manageFinancials', $project)
            <x-stat label="Coste real" :value="money($costSummary['cost'])"
                    :hint="'desviación '.money($costSummary['deviation'])"
                    :color="$costSummary['deviation'] > 0 ? 'amber' : 'slate'" />

            <x-stat label="Margen" :value="money($costSummary['margin'])"
                    :hint="percent($costSummary['margin_percent'])"
                    :color="$costSummary['margin_percent'] >= 15 ? 'green' : 'red'" />
        @endcan
    </div>

    {{-- Pestañas --}}
    <div class="mt-6 border-b border-slate-200 dark:border-slate-700">
        <nav class="-mb-px flex gap-1 overflow-x-auto">
            @php
                $tabs = [
                    'fases' => ['Fases y tareas', null],
                    'incidencias' => ['Incidencias', $counters['incidents'] ?: null],
                    'fotos' => ['Fotos', $counters['photos'] ?: null],
                    'extras' => ['Extras', $counters['extras'] ?: null],
                    'diario' => ['Diario de obra', null],
                    'documentos' => ['Documentos', null],
                    'actividad' => ['Actividad', null],
                ];
            @endphp

            @foreach ($tabs as $key => [$label, $badge])
                <button wire:click="setTab('{{ $key }}')"
                        @class([
                            'flex items-center gap-2 whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-medium transition',
                            'border-indigo-600 text-indigo-600' => $tab === $key,
                            'border-transparent text-slate-500 hover:text-slate-700' => $tab !== $key,
                        ])>
                    {{ $label }}
                    @if ($badge)
                        <span class="rounded-full bg-red-100 px-1.5 text-[11px] font-semibold text-red-700">{{ $badge }}</span>
                    @endif
                </button>
            @endforeach
        </nav>
    </div>

    <div class="mt-6">
        @if ($tab === 'fases')
            @livewire('admin.projects.phase-manager', ['project' => $project], key('phases-'.$project->id))

        @elseif ($tab === 'incidencias')
            @livewire('admin.projects.incident-manager', ['project' => $project], key('incidents-'.$project->id))

        @elseif ($tab === 'fotos')
            @livewire('admin.projects.photo-manager', ['project' => $project], key('photos-'.$project->id))

        @elseif ($tab === 'extras')
            @livewire('admin.projects.extra-manager', ['project' => $project], key('extras-'.$project->id))

        @elseif ($tab === 'diario')
            @livewire('admin.projects.update-composer', ['project' => $project], key('updates-'.$project->id))

        @elseif ($tab === 'documentos')
            <x-card title="Documentación de la obra"
                    subtitle="Marca como visibles los documentos que el cliente puede descargar desde su portal.">
                @livewire('admin.shared.document-manager', [
                    'model' => $project,
                    'categories' => $documentCategories,
                ], key('docs-project-'.$project->id))
            </x-card>

        @elseif ($tab === 'actividad')
            <x-card title="Traza de la obra" :padding="false">
                @forelse ($activities as $activity)
                    <div class="flex gap-3 border-b border-slate-100 px-5 py-3 last:border-0 dark:border-slate-700">
                        <div class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-indigo-400"></div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-slate-900 dark:text-slate-100">{{ $activity->description ?: $activity->event }}</p>
                            <p class="text-xs text-slate-500">{{ $activity->causer_name }} · {{ $activity->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-slate-500">Sin actividad registrada.</p>
                @endforelse
            </x-card>
        @endif
    </div>
</div>
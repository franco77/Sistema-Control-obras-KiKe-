<div class="space-y-6">
    <x-page-header title="Panel de control"
                   :subtitle="'Hoy es '.now()->translatedFormat('l, d \d\e F \d\e Y')">
        <x-slot:actions>
            <x-btn :href="route('admin.quotes.create')" icon="plus">Nuevo presupuesto</x-btn>
        </x-slot:actions>
    </x-page-header>

    {{-- Indicadores --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Obras activas" :value="$stats['active_projects']"
                :hint="$stats['delayed_projects'].' retrasadas'"
                :color="$stats['delayed_projects'] > 0 ? 'amber' : 'slate'"
                :href="route('admin.projects.index')" />

        <x-stat label="Presupuestos pendientes" :value="$stats['pending_quotes']"
                :hint="money($stats['pending_quotes_amount']).' en juego'"
                color="indigo" :href="route('admin.quotes.index')" />

        <x-stat label="Aprobado este mes" :value="money($stats['approved_month'])"
                hint="Presupuestos firmados" color="green" />

        <x-stat label="Extras por aprobar" :value="$stats['pending_extras']"
                :hint="$stats['unread_messages'].' mensajes sin leer'"
                :color="$stats['pending_extras'] > 0 ? 'amber' : 'slate'" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Presupuestos a la espera --}}
        <x-card title="Esperando respuesta del cliente" class="lg:col-span-2" :padding="false">
            <x-slot:actions>
                <x-btn variant="ghost" size="xs" :href="route('admin.quotes.index')">Ver todos</x-btn>
            </x-slot:actions>

            @forelse ($pendingQuotes as $quote)
                <a href="{{ route('admin.quotes.show', $quote) }}"
                   class="flex items-center gap-4 border-b border-slate-100 px-5 py-3 transition last:border-0 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700/40">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{ $quote->title }}</p>
                        <p class="text-xs text-slate-500">{{ $quote->client->name }} · {{ $quote->reference }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold tabular-nums text-slate-900 dark:text-slate-100">{{ money($quote->total) }}</p>
                        @if ($quote->valid_until)
                            <p @class([
                                'text-xs',
                                'text-red-600' => $quote->valid_until->isPast(),
                                'text-amber-600' => ! $quote->valid_until->isPast() && $quote->valid_until->diffInDays() <= 5,
                                'text-slate-500' => ! $quote->valid_until->isPast() && $quote->valid_until->diffInDays() > 5,
                            ])>
                                vence {{ $quote->valid_until->diffForHumans() }}
                            </p>
                        @endif
                    </div>
                    <x-badge :color="$quote->status->color()" dot>{{ $quote->status->label() }}</x-badge>
                </a>
            @empty
                <div class="p-5">
                    <x-empty-state title="Sin presupuestos pendientes"
                                   description="Todo lo enviado tiene respuesta." icon="check" />
                </div>
            @endforelse
        </x-card>

        {{-- Agenda --}}
        <x-card title="Próximos 7 días" :padding="false">
            <x-slot:actions>
                <x-btn variant="ghost" size="xs" :href="route('admin.calendar.index')">Agenda</x-btn>
            </x-slot:actions>

            @forelse ($upcomingEvents as $event)
                <div class="flex gap-3 border-b border-slate-100 px-5 py-3 last:border-0 dark:border-slate-700">
                    <div class="w-11 shrink-0 text-center">
                        <p class="text-[11px] uppercase text-slate-400">{{ $event->starts_at->translatedFormat('M') }}</p>
                        <p class="text-lg font-semibold leading-none text-slate-900 dark:text-slate-100">{{ $event->starts_at->format('d') }}</p>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{ $event->title }}</p>
                        <p class="truncate text-xs text-slate-500">
                            {{ $event->all_day ? 'Todo el día' : $event->starts_at->format('H:i') }}
                            @if ($event->client) · {{ $event->client->name }} @endif
                        </p>
                    </div>
                </div>
            @empty
                <div class="p-5"><x-empty-state title="Agenda despejada" icon="calendar" /></div>
            @endforelse
        </x-card>

        {{-- Obras en curso --}}
        <x-card title="Obras en curso" class="lg:col-span-2" :padding="false">
            @forelse ($activeProjects as $project)
                <a href="{{ route('admin.projects.show', $project) }}"
                   class="block border-b border-slate-100 px-5 py-3 transition last:border-0 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700/40">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{ $project->name }}</p>
                            <p class="text-xs text-slate-500">{{ $project->client->name }} · {{ $project->code }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            @if ($project->isDelayed())
                                <x-badge color="red" size="xs">Retrasada</x-badge>
                            @endif
                            <x-badge :color="$project->status->color()" size="xs">{{ $project->status->label() }}</x-badge>
                        </div>
                    </div>
                    <x-progress :value="$project->progress" class="mt-2" size="sm" />
                </a>
            @empty
                <div class="p-5"><x-empty-state title="No hay obras en ejecución" icon="building" /></div>
            @endforelse
        </x-card>

        {{-- Alertas --}}
        <div class="space-y-6">
            <x-card title="Incidencias graves abiertas" :padding="false">
                @forelse ($openIncidents as $incident)
                    <a href="{{ route('admin.projects.show', $incident->project) }}"
                       class="flex items-start gap-2 border-b border-slate-100 px-5 py-3 last:border-0 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700/40">
                        <x-badge :color="$incident->severity->color()" size="xs">{{ $incident->severity->label() }}</x-badge>
                        <div class="min-w-0">
                            <p class="truncate text-sm text-slate-900 dark:text-slate-100">{{ $incident->title }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $incident->project->code }}</p>
                        </div>
                    </a>
                @empty
                    <p class="px-5 py-4 text-sm text-slate-500">Ninguna. Buena señal.</p>
                @endforelse
            </x-card>

            <x-card title="Documentación por caducar" :padding="false">
                @forelse ($expiringDocuments as $document)
                    <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-5 py-3 last:border-0 dark:border-slate-700">
                        <div class="min-w-0">
                            <p class="truncate text-sm text-slate-900 dark:text-slate-100">
                                {{ $document->documentable?->name ?? '—' }}
                            </p>
                            <p class="truncate text-xs text-slate-500">{{ $document->category->label() }}</p>
                        </div>
                        <x-badge :color="$document->isExpired() ? 'red' : 'amber'" size="xs">
                            {{ $document->expires_on->format('d/m/y') }}
                        </x-badge>
                    </div>
                @empty
                    <p class="px-5 py-4 text-sm text-slate-500">Todo en regla.</p>
                @endforelse
            </x-card>
        </div>
    </div>
</div>
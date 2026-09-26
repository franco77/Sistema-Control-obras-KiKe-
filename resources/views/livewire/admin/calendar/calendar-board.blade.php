<div>
    <x-page-header title="Agenda" :subtitle="ucfirst($cursor->translatedFormat('F Y'))">
        <x-slot:actions>
            <div class="flex items-center gap-1 rounded-lg border border-slate-200 bg-white p-0.5 dark:border-slate-700 dark:bg-slate-800">
                <button wire:click="shiftMonth(-1)" class="rounded px-2 py-1 text-sm text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700">‹</button>
                <button wire:click="today" class="rounded px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700">Hoy</button>
                <button wire:click="shiftMonth(1)" class="rounded px-2 py-1 text-sm text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700">›</button>
            </div>

            <x-select wire:model.live="typeFilter" class="w-44 text-xs">
                <option value="">Todos los tipos</option>
                @foreach ($types as $case)<option value="{{ $case->value }}">{{ $case->label() }}</option>@endforeach
            </x-select>

            <x-select wire:model.live="view" class="w-32 text-xs">
                <option value="month">Mes</option>
                <option value="list">Lista</option>
            </x-select>

            <x-btn icon="plus" wire:click="newEvent()">Nuevo evento</x-btn>
        </x-slot:actions>
    </x-page-header>

    @if ($view === 'month')
        <x-card :padding="false">
            <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50 text-center text-xs font-medium uppercase text-slate-500 dark:border-slate-700 dark:bg-slate-900/40">
                @foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $dayName)
                    <div class="py-2">{{ $dayName }}</div>
                @endforeach
            </div>

            <div class="grid grid-cols-7">
                @foreach ($days as $day)
                    @php
                        $key = $day->toDateString();
                        $dayEvents = $eventsByDay[$key] ?? collect();
                        $dayAbsences = $absences->filter(fn ($a) => $day->betweenIncluded($a->starts_on, $a->ends_on));
                    @endphp

                    <div @class([
                        'min-h-28 border-b border-r border-slate-100 p-1.5 dark:border-slate-700',
                        'bg-slate-50/60 dark:bg-slate-900/30' => $day->month !== $cursor->month,
                        'bg-indigo-50/40 dark:bg-indigo-500/5' => $day->isToday(),
                    ])>
                        <div class="mb-1 flex items-center justify-between">
                            <span @class([
                                'text-xs',
                                'font-semibold text-indigo-600' => $day->isToday(),
                                'text-slate-400' => $day->month !== $cursor->month,
                                'text-slate-600 dark:text-slate-300' => $day->month === $cursor->month && ! $day->isToday(),
                            ])>{{ $day->day }}</span>

                            <button wire:click="newEvent('{{ $key }}')"
                                    class="text-slate-300 opacity-0 transition hover:text-indigo-600 group-hover:opacity-100">+</button>
                        </div>

                        @foreach ($dayAbsences as $absence)
                            <div class="mb-0.5 truncate rounded bg-red-50 px-1 py-0.5 text-[10px] text-red-700 dark:bg-red-500/10 dark:text-red-300"
                                 title="{{ $absence->provider->name }} — {{ $absence->type->label() }}">
                                {{ $absence->provider->name }}
                            </div>
                        @endforeach

                        @foreach ($dayEvents->take(3) as $event)
                            <button wire:click="edit({{ $event->id }})"
                                    class="mb-0.5 block w-full truncate rounded px-1 py-0.5 text-left text-[11px] transition hover:opacity-80
                                           {{ match ($event->type->color()) {
                                               'blue' => 'bg-blue-100 text-blue-800 dark:bg-blue-500/15 dark:text-blue-300',
                                               'indigo' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-500/15 dark:text-indigo-300',
                                               'purple' => 'bg-purple-100 text-purple-800 dark:bg-purple-500/15 dark:text-purple-300',
                                               'green' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
                                               'amber' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
                                               'teal' => 'bg-teal-100 text-teal-800 dark:bg-teal-500/15 dark:text-teal-300',
                                               'orange' => 'bg-orange-100 text-orange-800 dark:bg-orange-500/15 dark:text-orange-300',
                                               'pink' => 'bg-pink-100 text-pink-800 dark:bg-pink-500/15 dark:text-pink-300',
                                               default => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
                                           } }}">
                                @unless ($event->all_day)<span class="tabular-nums">{{ $event->starts_at->format('H:i') }}</span>@endunless
                                {{ $event->title }}
                            </button>
                        @endforeach

                        @if ($dayEvents->count() > 3)
                            <p class="px-1 text-[10px] text-slate-400">+{{ $dayEvents->count() - 3 }} más</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-card>
    @else
        <x-card :padding="false">
            @forelse ($events as $event)
                <div wire:key="ev-{{ $event->id }}"
                     class="flex flex-wrap items-center gap-4 border-b border-slate-100 px-5 py-3 last:border-0 dark:border-slate-700">
                    <div class="w-14 shrink-0 text-center">
                        <p class="text-[11px] uppercase text-slate-400">{{ $event->starts_at->translatedFormat('D') }}</p>
                        <p class="text-lg font-semibold leading-none text-slate-900 dark:text-slate-100">{{ $event->starts_at->format('d') }}</p>
                        <p class="text-[11px] text-slate-400">{{ $event->starts_at->translatedFormat('M') }}</p>
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="flex items-center gap-2 text-sm font-medium text-slate-900 dark:text-slate-100">
                            {{ $event->title }}
                            <x-badge :color="$event->type->color()" size="xs">{{ $event->type->label() }}</x-badge>
                        </p>
                        <p class="text-xs text-slate-500">
                            {{ $event->all_day ? 'Todo el día' : $event->starts_at->format('H:i').'–'.$event->ends_at->format('H:i') }}
                            @if ($event->location) · {{ $event->location }} @endif
                            @if ($event->client) · {{ $event->client->name }} @endif
                            @if ($event->provider) · {{ $event->provider->name }} @endif
                        </p>
                    </div>

                    <x-badge :color="$event->status->color()" size="xs">{{ $event->status->label() }}</x-badge>

                    <div class="flex gap-2">
                        @if ($event->status->value !== 'done')
                            <button wire:click="markDone({{ $event->id }})" class="text-xs font-medium text-emerald-600 hover:underline">Hecho</button>
                        @endif
                        <button wire:click="edit({{ $event->id }})" class="text-xs font-medium text-indigo-600 hover:underline">Editar</button>
                        <button wire:click="delete({{ $event->id }})" wire:confirm="¿Eliminar evento?"
                                class="text-xs font-medium text-red-600 hover:underline">Borrar</button>
                    </div>
                </div>
            @empty
                <div class="p-5"><x-empty-state title="Nada en este periodo" icon="calendar" /></div>
            @endforelse
        </x-card>
    @endif

    {{-- Formulario --}}
    @if ($showForm)
        <div class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-slate-900/50 p-4 backdrop-blur-sm">
            <div class="mt-12 w-full max-w-2xl rounded-xl bg-white p-5 shadow-xl dark:bg-slate-800">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                    {{ $editingId ? 'Editar evento' : 'Nuevo evento' }}
                </h3>

                <form wire:submit="save" class="mt-4 grid gap-4 sm:grid-cols-6">
                    <x-field label="Título" required class="sm:col-span-4" :error="$errors->first('form.title')">
                        <x-input wire:model="form.title" />
                    </x-field>

                    <x-field label="Tipo" required class="sm:col-span-2">
                        <x-select wire:model="form.type">
                            @foreach ($types as $case)<option value="{{ $case->value }}">{{ $case->label() }}</option>@endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Inicio" required class="sm:col-span-2" :error="$errors->first('form.starts_at')">
                        <x-input type="datetime-local" wire:model="form.starts_at" />
                    </x-field>

                    <x-field label="Fin" required class="sm:col-span-2" :error="$errors->first('form.ends_at')">
                        <x-input type="datetime-local" wire:model="form.ends_at" />
                    </x-field>

                    <x-field label="Estado" class="sm:col-span-2">
                        <x-select wire:model="form.status">
                            @foreach ($statuses as $case)<option value="{{ $case->value }}">{{ $case->label() }}</option>@endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Lugar" class="sm:col-span-3">
                        <x-input wire:model="form.location" />
                    </x-field>

                    <x-field label="Recordatorio" class="sm:col-span-3" hint="Se avisa a los asistentes">
                        <x-input type="datetime-local" wire:model="form.reminder_at" />
                    </x-field>

                    <x-field label="Cliente" class="sm:col-span-2">
                        <x-select wire:model="form.client_id">
                            <option value="">—</option>
                            @foreach ($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Obra" class="sm:col-span-2">
                        <x-select wire:model="form.project_id">
                            <option value="">—</option>
                            @foreach ($projects as $project)<option value="{{ $project->id }}">{{ $project->code }} — {{ $project->name }}</option>@endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Proveedor" class="sm:col-span-2">
                        <x-select wire:model="form.provider_id">
                            <option value="">—</option>
                            @foreach ($providers as $provider)<option value="{{ $provider->id }}">{{ $provider->name }}</option>@endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Responsable" class="sm:col-span-3">
                        <x-select wire:model="form.owner_id">
                            <option value="">—</option>
                            @foreach ($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach
                        </x-select>
                    </x-field>

                    <div class="flex items-end gap-5 pb-2 sm:col-span-3">
                        <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                            <input type="checkbox" wire:model="form.all_day" class="rounded border-slate-300 text-indigo-600">
                            Todo el día
                        </label>
                        <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                            <input type="checkbox" wire:model="form.visible_to_client" class="rounded border-slate-300 text-indigo-600">
                            Visible al cliente
                        </label>
                    </div>

                    <x-field label="Descripción" class="sm:col-span-6">
                        <x-textarea wire:model="form.description" rows="2" />
                    </x-field>

                    <div class="flex justify-end gap-2 sm:col-span-6">
                        <x-btn variant="secondary" wire:click="$set('showForm', false)">Cancelar</x-btn>
                        <x-btn type="submit">Guardar</x-btn>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
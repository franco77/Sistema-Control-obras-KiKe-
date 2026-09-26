<div class="space-y-4">
    <div class="flex items-center justify-between">
        <x-select wire:model.live="filter" class="w-44 text-xs">
            <option value="open">Solo abiertas</option>
            <option value="all">Todas</option>
        </x-select>
        <x-btn size="sm" icon="plus" wire:click="newIncident">Registrar incidencia</x-btn>
    </div>

    @if ($showForm)
        <x-card :title="$editingId ? 'Editar incidencia' : 'Nueva incidencia'">
            <form wire:submit="save" class="grid gap-4 sm:grid-cols-6">
                <x-field label="Título" required class="sm:col-span-4" :error="$errors->first('form.title')">
                    <x-input wire:model="form.title" placeholder="Fuga en bajante del baño principal" />
                </x-field>

                <x-field label="Gravedad" required class="sm:col-span-1">
                    <x-select wire:model="form.severity">
                        @foreach ($severities as $case)<option value="{{ $case->value }}">{{ $case->label() }}</option>@endforeach
                    </x-select>
                </x-field>

                <x-field label="Estado" required class="sm:col-span-1">
                    <x-select wire:model="form.status">
                        @foreach ($statuses as $case)<option value="{{ $case->value }}">{{ $case->label() }}</option>@endforeach
                    </x-select>
                </x-field>

                <x-field label="Descripción" required class="sm:col-span-6" :error="$errors->first('form.description')">
                    <x-textarea wire:model="form.description" rows="3" />
                </x-field>

                <x-field label="Fase" class="sm:col-span-2">
                    <x-select wire:model="form.project_phase_id">
                        <option value="">—</option>
                        @foreach ($phases as $phase)<option value="{{ $phase->id }}">{{ $phase->name }}</option>@endforeach
                    </x-select>
                </x-field>

                <x-field label="Tarea" class="sm:col-span-2">
                    <x-select wire:model="form.project_task_id">
                        <option value="">—</option>
                        @foreach ($tasks as $task)<option value="{{ $task->id }}">{{ $task->name }}</option>@endforeach
                    </x-select>
                </x-field>

                <x-field label="Proveedor implicado" class="sm:col-span-2">
                    <x-select wire:model="form.provider_id">
                        <option value="">—</option>
                        @foreach ($providers as $provider)<option value="{{ $provider->id }}">{{ $provider->name }}</option>@endforeach
                    </x-select>
                </x-field>

                <x-field label="Responsable" class="sm:col-span-2">
                    <x-select wire:model="form.assigned_to">
                        <option value="">—</option>
                        @foreach ($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach
                    </x-select>
                </x-field>

                <x-field label="Fecha límite" class="sm:col-span-1"><x-input type="date" wire:model="form.due_date" /></x-field>
                <x-field label="Sobrecoste (€)" class="sm:col-span-1"><x-input type="number" step="0.01" wire:model="form.cost_impact" /></x-field>
                <x-field label="Días de retraso" class="sm:col-span-1"><x-input type="number" wire:model="form.days_impact" /></x-field>

                <label class="flex items-end gap-2 pb-2 text-xs text-slate-600 sm:col-span-1 dark:text-slate-300">
                    <input type="checkbox" wire:model="form.visible_to_client" class="rounded border-slate-300 text-indigo-600">
                    Visible al cliente
                </label>

                <x-field label="Resolución" class="sm:col-span-6" hint="Qué se hizo para resolverla">
                    <x-textarea wire:model="form.resolution" rows="2" />
                </x-field>

                <div class="flex gap-2 sm:col-span-6">
                    <x-btn type="submit">Guardar</x-btn>
                    <x-btn variant="secondary" wire:click="$set('showForm', false)">Cancelar</x-btn>
                </div>
            </form>
        </x-card>
    @endif

    @forelse ($incidents as $incident)
        <x-card wire:key="inc-{{ $incident->id }}">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-badge :color="$incident->severity->color()">{{ $incident->severity->label() }}</x-badge>
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $incident->title }}</p>
                        <span class="text-xs text-slate-400">{{ $incident->code }}</span>
                        @if ($incident->visible_to_client)<x-badge color="blue" size="xs">Visible al cliente</x-badge>@endif
                        @if ($incident->reported_by_client)<x-badge color="purple" size="xs">Reportada por el cliente</x-badge>@endif
                    </div>

                    <p class="mt-2 whitespace-pre-line text-sm text-slate-600 dark:text-slate-300">{{ $incident->description }}</p>

                    @if ($incident->resolution)
                        <p class="mt-2 rounded-lg bg-emerald-50 px-3 py-2 text-xs text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300">
                            <strong>Resolución:</strong> {{ $incident->resolution }}
                        </p>
                    @endif

                    <p class="mt-2 flex flex-wrap gap-x-3 text-xs text-slate-500">
                        <span>Abierta {{ $incident->opened_at->format('d/m/Y') }}</span>
                        @if ($incident->phase)<span>Fase: {{ $incident->phase->name }}</span>@endif
                        @if ($incident->provider)<span>Proveedor: {{ $incident->provider->name }}</span>@endif
                        @if ($incident->assignee)<span>Resp.: {{ $incident->assignee->name }}</span>@endif
                        @if ($incident->cost_impact > 0)<span class="text-red-600">Sobrecoste {{ money($incident->cost_impact) }}</span>@endif
                        @if ($incident->days_impact > 0)<span class="text-amber-600">+{{ $incident->days_impact }} días</span>@endif
                    </p>
                </div>

                <div class="flex shrink-0 flex-col items-end gap-2">
                    <select wire:change="changeStatus({{ $incident->id }}, $event.target.value)"
                            class="w-40 rounded-md border-slate-200 py-1 text-xs dark:border-slate-600 dark:bg-slate-900">
                        @foreach ($statuses as $case)
                            <option value="{{ $case->value }}" @selected($case === $incident->status)>{{ $case->label() }}</option>
                        @endforeach
                    </select>

                    <div class="flex gap-2">
                        <button wire:click="edit({{ $incident->id }})" class="text-xs font-medium text-indigo-600 hover:underline">Editar</button>
                        @unless ($incident->visible_to_client)
                            <button wire:click="notifyClient({{ $incident->id }})"
                                    wire:confirm="Se enviará un email al cliente con esta incidencia. ¿Continuar?"
                                    class="text-xs font-medium text-amber-600 hover:underline">Informar al cliente</button>
                        @endunless
                    </div>
                </div>
            </div>
        </x-card>
    @empty
        <x-empty-state title="Sin incidencias" description="Registra aquí los imprevistos para tener trazabilidad de coste y plazo." icon="check" />
    @endforelse
</div>
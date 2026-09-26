<div class="space-y-4">
    <div class="flex justify-end">
        <x-btn size="sm" icon="plus" wire:click="newPhase">Añadir fase</x-btn>
    </div>

    @if ($showPhaseForm)
        <x-card :title="$editingPhaseId ? 'Editar fase' : 'Nueva fase'">
            <form wire:submit="savePhase" class="grid gap-4 sm:grid-cols-6">
                <x-field label="Nombre" required class="sm:col-span-3" :error="$errors->first('phaseForm.name')">
                    <x-input wire:model="phaseForm.name" placeholder="Fontanería" />
                </x-field>

                <x-field label="Oficio" class="sm:col-span-2">
                    <x-select wire:model="phaseForm.trade_id">
                        <option value="">Sin oficio</option>
                        @foreach ($trades as $trade)<option value="{{ $trade->id }}">{{ $trade->name }}</option>@endforeach
                    </x-select>
                </x-field>

                <x-field label="Peso" required class="sm:col-span-1"
                         hint="Ponderación en el % global">
                    <x-input type="number" wire:model="phaseForm.weight" min="1" max="100" />
                </x-field>

                <x-field label="Inicio previsto" class="sm:col-span-2">
                    <x-input type="date" wire:model="phaseForm.planned_start" />
                </x-field>

                <x-field label="Fin previsto" class="sm:col-span-2" :error="$errors->first('phaseForm.planned_end')">
                    <x-input type="date" wire:model="phaseForm.planned_end" />
                </x-field>

                <x-field label="Importe (€)" class="sm:col-span-2">
                    <x-input type="number" step="0.01" wire:model="phaseForm.budget_amount" />
                </x-field>

                <x-field label="Descripción" class="sm:col-span-6">
                    <x-textarea wire:model="phaseForm.description" rows="2" />
                </x-field>

                <div class="flex items-center gap-4 sm:col-span-6">
                    <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                        <input type="checkbox" wire:model="phaseForm.visible_to_client" class="rounded border-slate-300 text-indigo-600">
                        Visible en el portal del cliente
                    </label>
                </div>

                <div class="flex gap-2 sm:col-span-6">
                    <x-btn type="submit">Guardar fase</x-btn>
                    <x-btn variant="secondary" wire:click="$set('showPhaseForm', false)">Cancelar</x-btn>
                </div>
            </form>
        </x-card>
    @endif

    @forelse ($phases as $phase)
        <x-card wire:key="phase-{{ $phase->id }}" :padding="false">
            {{-- Cabecera de fase --}}
            <div class="flex flex-wrap items-center gap-3 p-4">
                <div class="flex flex-col">
                    <button wire:click="movePhase({{ $phase->id }}, -1)" class="text-slate-300 hover:text-indigo-600">&#9650;</button>
                    <button wire:click="movePhase({{ $phase->id }}, 1)" class="text-slate-300 hover:text-indigo-600">&#9660;</button>
                </div>

                <button wire:click="toggle({{ $phase->id }})" class="flex min-w-0 flex-1 items-center gap-3 text-left">
                    <span class="text-slate-400">{{ in_array($phase->id, $expanded) ? '▾' : '▸' }}</span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
                            {{ $phase->name }}
                            @unless ($phase->visible_to_client)
                                <span class="text-xs font-normal text-slate-400">(oculta al cliente)</span>
                            @endunless
                        </p>
                        <p class="text-xs text-slate-500">
                            {{ $phase->tasks->count() }} tareas
                            @if ($phase->trade) · {{ $phase->trade->name }} @endif
                            @if ($phase->planned_end) · fin {{ $phase->planned_end->format('d/m/y') }} @endif
                            · peso {{ $phase->weight }}
                        </p>
                    </div>
                </button>

                <div class="w-32"><x-progress :value="$phase->progress" size="sm" /></div>

                <x-badge :color="$phase->status->color()" size="xs">{{ $phase->status->label() }}</x-badge>

                <span class="text-sm tabular-nums text-slate-600 dark:text-slate-300">{{ money($phase->budget_amount) }}</span>

                <div class="flex gap-2">
                    <button wire:click="editPhase({{ $phase->id }})" class="text-xs font-medium text-indigo-600 hover:underline">Editar</button>
                    <button wire:click="deletePhase({{ $phase->id }})" wire:confirm="Se eliminarán sus tareas. ¿Seguro?"
                            class="text-xs font-medium text-red-600 hover:underline">Eliminar</button>
                </div>
            </div>

            {{-- Tareas --}}
            @if (in_array($phase->id, $expanded))
                <div class="divide-y divide-slate-100 border-t border-slate-100 dark:divide-slate-700 dark:border-slate-700">
                    @forelse ($phase->tasks as $task)
                        <div wire:key="task-{{ $task->id }}"
                             class="flex flex-wrap items-center gap-3 px-4 py-2.5 hover:bg-slate-50/60 dark:hover:bg-slate-700/20">
                            <select wire:change="changeTaskStatus({{ $task->id }}, $event.target.value)"
                                    class="w-32 rounded-md border-slate-200 py-1 text-xs dark:border-slate-600 dark:bg-slate-900">
                                @foreach ($taskStatuses as $case)
                                    <option value="{{ $case->value }}" @selected($case === $task->status)>{{ $case->label() }}</option>
                                @endforeach
                            </select>

                            <div class="min-w-0 flex-1">
                                <p @class([
                                    'truncate text-sm text-slate-900 dark:text-slate-100',
                                    'line-through text-slate-400' => $task->isDone(),
                                ])>{{ $task->name }}</p>
                                <p class="truncate text-xs text-slate-500">
                                    @if ($task->trade) {{ $task->trade->name }} @endif
                                    @if ($task->planned_start)
                                        · {{ $task->planned_start->format('d/m') }}–{{ $task->planned_end?->format('d/m') }}
                                    @endif
                                    @if ($task->estimated_hours) · {{ $task->estimated_hours }} h @endif
                                </p>
                            </div>

                            @if ($task->priority->value !== 'normal')
                                <x-badge :color="$task->priority->color()" size="xs">{{ $task->priority->label() }}</x-badge>
                            @endif

                            @if ($task->isOverdue())
                                <x-badge color="red" size="xs">Fuera de plazo</x-badge>
                            @endif

                            <div class="w-40 truncate text-xs">
                                @if ($task->provider)
                                    <a href="{{ route('admin.providers.show', $task->provider) }}"
                                       class="font-medium text-indigo-600 hover:underline">{{ $task->provider->name }}</a>
                                @else
                                    <span class="text-slate-400">sin asignar</span>
                                @endif
                            </div>

                            <span class="w-24 text-right text-xs tabular-nums text-slate-500">{{ money($task->cost_estimated) }}</span>

                            <div class="flex gap-2">
                                <button wire:click="editTask({{ $task->id }})" class="text-xs font-medium text-indigo-600 hover:underline">Editar</button>
                                <button wire:click="deleteTask({{ $task->id }})" wire:confirm="¿Eliminar tarea?"
                                        class="text-xs font-medium text-red-600 hover:underline">&times;</button>
                            </div>
                        </div>
                    @empty
                        <p class="px-4 py-4 text-center text-sm text-slate-400">Esta fase no tiene tareas.</p>
                    @endforelse
                </div>

                {{-- Formulario de tarea --}}
                @if ($taskPhaseId === $phase->id)
                    <div class="border-t border-slate-100 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/40">
                        <form wire:submit="saveTask" class="grid gap-3 sm:grid-cols-6">
                            <x-field label="Tarea" required class="sm:col-span-3" :error="$errors->first('taskForm.name')">
                                <x-input wire:model="taskForm.name" />
                            </x-field>

                            <x-field label="Oficio" class="sm:col-span-1">
                                <x-select wire:model.live="taskForm.trade_id">
                                    <option value="">—</option>
                                    @foreach ($trades as $trade)<option value="{{ $trade->id }}">{{ $trade->name }}</option>@endforeach
                                </x-select>
                            </x-field>

                            <x-field label="Proveedor" class="sm:col-span-2"
                                     hint="Solo activos y con documentación en vigor">
                                <x-select wire:model="taskForm.provider_id">
                                    <option value="">Sin asignar</option>
                                    @foreach ($providers as $provider)
                                        @continue($taskForm['trade_id'] && ! $provider->trades->contains('id', (int) $taskForm['trade_id']))
                                        <option value="{{ $provider->id }}">
                                            {{ $provider->name }}{{ $provider->hasValidDocumentation() ? '' : ' (docs pendientes)' }}
                                        </option>
                                    @endforeach
                                </x-select>
                            </x-field>

                            <x-field label="Responsable interno" class="sm:col-span-2">
                                <x-select wire:model="taskForm.assigned_user_id">
                                    <option value="">—</option>
                                    @foreach ($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach
                                </x-select>
                            </x-field>

                            <x-field label="Prioridad" class="sm:col-span-1">
                                <x-select wire:model="taskForm.priority">
                                    @foreach ($priorities as $case)<option value="{{ $case->value }}">{{ $case->label() }}</option>@endforeach
                                </x-select>
                            </x-field>

                            <x-field label="Inicio" class="sm:col-span-1"><x-input type="date" wire:model="taskForm.planned_start" /></x-field>
                            <x-field label="Fin" class="sm:col-span-1" :error="$errors->first('taskForm.planned_end')">
                                <x-input type="date" wire:model="taskForm.planned_end" />
                            </x-field>
                            <x-field label="Horas est." class="sm:col-span-1"><x-input type="number" step="0.5" wire:model="taskForm.estimated_hours" /></x-field>

                            <x-field label="Coste previsto (€)" class="sm:col-span-2">
                                <x-input type="number" step="0.01" wire:model="taskForm.cost_estimated" />
                            </x-field>

                            <x-field label="Descripción" class="sm:col-span-4">
                                <x-input wire:model="taskForm.description" />
                            </x-field>

                            <label class="flex items-end gap-2 pb-2 text-xs text-slate-600 sm:col-span-2 dark:text-slate-300">
                                <input type="checkbox" wire:model="taskForm.visible_to_client" class="rounded border-slate-300 text-indigo-600">
                                Visible al cliente
                            </label>

                            <div class="flex gap-2 sm:col-span-6">
                                <x-btn type="submit" size="sm">Guardar tarea</x-btn>
                                <x-btn variant="secondary" size="sm" wire:click="$set('taskPhaseId', null)">Cancelar</x-btn>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="border-t border-slate-100 p-3 dark:border-slate-700">
                        <x-btn size="xs" variant="secondary" icon="plus" wire:click="newTask({{ $phase->id }})">Añadir tarea</x-btn>
                    </div>
                @endif
            @endif
        </x-card>
    @empty
        <x-empty-state title="La obra no tiene fases"
                       description="Si vienes de un presupuesto, las fases se crean solas. Si no, añádelas aquí." icon="building" />
    @endforelse
</div>
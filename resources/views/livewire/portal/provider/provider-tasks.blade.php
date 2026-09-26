<div class="space-y-6">
    <header>
        <h1 class="text-2xl font-semibold text-stone-900">Hola, {{ $provider->name }}</h1>
        <p class="mt-1 text-sm text-stone-600">Estas son tus tareas en las obras activas.</p>
    </header>

    @if ($missingDocuments)
        <div class="rounded-2xl border border-amber-300 bg-amber-50 p-5">
            <p class="font-semibold text-amber-900">Tienes documentación pendiente</p>
            <p class="mt-1 text-sm text-amber-800">
                Falta o está caducado:
                {{ collect($missingDocuments)->map(fn ($c) => App\Enums\DocumentCategory::from($c)->label())->implode(', ') }}.
                Envíanoslo para poder seguir asignándote trabajos.
            </p>
        </div>
    @endif

    @forelse ($grouped as $projectId => $tasks)
        @php $project = $tasks->first()->project; @endphp

        <section class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
            <div class="border-b border-stone-100 bg-stone-50 px-6 py-4">
                <h2 class="text-sm font-semibold text-stone-900">{{ $project->name }}</h2>
                <p class="text-xs text-stone-500">
                    {{ $project->code }}
                    @if ($project->property) · {{ $project->property->full_address }} @endif
                </p>
            </div>

            <ul class="divide-y divide-stone-100">
                @foreach ($tasks as $task)
                    <li wire:key="ptask-{{ $task->id }}" class="px-6 py-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-stone-900">{{ $task->name }}</p>
                                @if ($task->description)
                                    <p class="mt-0.5 text-sm text-stone-500">{{ $task->description }}</p>
                                @endif
                                <p class="mt-1 text-xs text-stone-400">
                                    {{ $task->phase?->name }}
                                    @if ($task->planned_start)
                                        · {{ $task->planned_start->format('d/m') }}
                                        @if ($task->planned_end) — {{ $task->planned_end->format('d/m') }} @endif
                                    @endif
                                    @if ($task->estimated_hours) · {{ $task->estimated_hours }} h estimadas @endif
                                </p>
                            </div>

                            <span class="shrink-0 rounded-full px-2.5 py-0.5 text-[11px] font-medium
                                         {{ match ($task->status->value) {
                                             'done' => 'bg-emerald-50 text-emerald-700',
                                             'in_progress' => 'bg-blue-50 text-blue-700',
                                             'blocked' => 'bg-red-50 text-red-700',
                                             default => 'bg-stone-100 text-stone-600',
                                         } }}">{{ $task->status->label() }}</span>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach (['in_progress' => 'Empezar', 'review' => 'Listo para revisar', 'done' => 'Completada', 'blocked' => 'Bloqueada'] as $value => $label)
                                @continue($task->status->value === $value)
                                <button wire:click="updateStatus({{ $task->id }}, '{{ $value }}')"
                                        class="rounded-lg border border-stone-300 px-3 py-1.5 text-xs font-medium text-stone-700 transition hover:bg-stone-50">
                                    {{ $label }}
                                </button>
                            @endforeach

                            <button wire:click="$set('uploadingTaskId', {{ $task->id }})"
                                    class="rounded-lg bg-stone-900 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-stone-700">
                                Subir fotos
                            </button>
                        </div>

                        @if ($uploadingTaskId === $task->id)
                            <form wire:submit="uploadPhotos" class="mt-3 rounded-xl border border-stone-200 p-4">
                                <input type="file" wire:model="photos" multiple accept="image/*"
                                       class="block w-full text-sm text-stone-600 file:mr-3 file:rounded-lg file:border-0 file:bg-stone-100 file:px-3 file:py-1.5 file:text-xs file:font-medium">
                                @error('photos.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                @error('photos') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                                <div class="mt-3 flex gap-2">
                                    <button type="submit" wire:loading.attr="disabled" wire:target="photos,uploadPhotos"
                                            class="rounded-lg bg-stone-900 px-4 py-2 text-xs font-semibold text-white">
                                        <span wire:loading.remove wire:target="photos,uploadPhotos">Enviar fotos</span>
                                        <span wire:loading wire:target="photos,uploadPhotos">Subiendo…</span>
                                    </button>
                                    <button type="button" wire:click="$set('uploadingTaskId', null)"
                                            class="rounded-lg border border-stone-300 px-4 py-2 text-xs font-medium text-stone-700">
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @empty
        <div class="rounded-2xl border border-dashed border-stone-300 px-6 py-16 text-center">
            <p class="text-sm font-medium text-stone-900">No tienes tareas asignadas ahora mismo</p>
            <p class="mt-1 text-sm text-stone-500">Te avisaremos por email en cuanto haya trabajo para ti.</p>
        </div>
    @endforelse
</div>
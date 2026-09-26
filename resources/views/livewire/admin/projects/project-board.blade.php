<div>
    <x-page-header title="Tablero de obras" subtitle="Arrastra el estado con los botones de cada tarjeta">
        <x-slot:actions>
            <x-btn variant="secondary" :href="route('admin.projects.index')">Vista lista</x-btn>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 lg:grid-cols-4">
        @foreach ($statuses as $status)
            @php $items = $grouped[$status->value] ?? collect(); @endphp

            <div class="rounded-xl bg-slate-100/70 p-3 dark:bg-slate-800/50">
                <div class="mb-3 flex items-center justify-between px-1">
                    <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $status->label() }}</h2>
                    <x-badge :color="$status->color()" size="xs">{{ $items->count() }}</x-badge>
                </div>

                <div class="space-y-2">
                    @forelse ($items as $project)
                        <div wire:key="board-{{ $project->id }}"
                             class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                            <a href="{{ route('admin.projects.show', $project) }}"
                               class="block text-sm font-medium text-slate-900 hover:text-indigo-600 dark:text-slate-100">
                                {{ $project->name }}
                            </a>
                            <p class="text-xs text-slate-500">{{ $project->client->name }}</p>

                            <x-progress :value="$project->progress" size="sm" class="mt-2" />

                            <div class="mt-2 flex items-center justify-between text-[11px] text-slate-500">
                                <span>{{ $project->code }}</span>
                                @if ($project->planned_end)
                                    <span class="{{ $project->isDelayed() ? 'font-medium text-red-600' : '' }}">
                                        {{ $project->planned_end->format('d/m/y') }}
                                    </span>
                                @endif
                            </div>

                            <div class="mt-2 flex flex-wrap gap-1 border-t border-slate-100 pt-2 dark:border-slate-700">
                                @foreach ($statuses as $target)
                                    @continue($target->value === $status->value)
                                    <button wire:click="moveTo({{ $project->id }}, '{{ $target->value }}')"
                                            class="rounded px-1.5 py-0.5 text-[10px] font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-slate-700">
                                        → {{ $target->label() }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="px-1 py-6 text-center text-xs text-slate-400">Vacío</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>
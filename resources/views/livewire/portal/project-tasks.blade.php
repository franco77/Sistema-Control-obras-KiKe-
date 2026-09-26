<div class="space-y-6">
    <header>
        <h1 class="text-2xl font-semibold text-stone-900">Trabajos de la obra</h1>
        <p class="mt-1 text-sm text-stone-600">
            El detalle de lo que ya está hecho y lo que queda por hacer.
        </p>
    </header>

    <div class="flex gap-2">
        @foreach (['all' => 'Todo', 'pending' => 'Pendiente', 'done' => 'Completado'] as $key => $label)
            <button wire:click="$set('filter', '{{ $key }}')"
                    @class([
                        'rounded-full px-4 py-1.5 text-sm font-medium transition',
                        'bg-stone-900 text-white' => $filter === $key,
                        'bg-white text-stone-600 border border-stone-200 hover:bg-stone-50' => $filter !== $key,
                    ])>{{ $label }}</button>
        @endforeach
    </div>

    @foreach ($phases as $phase)
        @php
            $tasks = $phase->tasks
                ->when($filter === 'done', fn ($c) => $c->where('status.value', 'done'))
                ->when($filter === 'pending', fn ($c) => $c->where('status.value', '!=', 'done'));
        @endphp

        @continue($tasks->isEmpty())

        <section class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-stone-100 bg-stone-50 px-6 py-3">
                <h2 class="text-sm font-semibold text-stone-900">{{ $phase->name }}</h2>
                <span class="text-xs font-medium text-stone-500">{{ $phase->progress }} % completado</span>
            </div>

            <ul class="divide-y divide-stone-100">
                @foreach ($tasks as $task)
                    <li class="flex items-start gap-4 px-6 py-4">
                        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[11px] font-bold
                                     {{ $task->isDone() ? 'bg-emerald-100 text-emerald-700' : 'border border-stone-300 text-transparent' }}">
                            ✓
                        </span>

                        <div class="min-w-0 flex-1">
                            <p @class([
                                'text-sm font-medium',
                                'text-stone-400 line-through' => $task->isDone(),
                                'text-stone-900' => ! $task->isDone(),
                            ])>{{ $task->name }}</p>

                            @if ($task->description)
                                <p class="mt-0.5 text-sm text-stone-500">{{ $task->description }}</p>
                            @endif

                            <p class="mt-1 text-xs text-stone-400">
                                @if ($task->isDone() && $task->completed_at)
                                    Completado el {{ $task->completed_at->format('d/m/Y') }}
                                @elseif ($task->planned_start)
                                    Previsto {{ $task->planned_start->format('d/m') }}
                                    @if ($task->planned_end) — {{ $task->planned_end->format('d/m') }} @endif
                                @else
                                    Sin fecha asignada
                                @endif
                            </p>
                        </div>

                        <span class="shrink-0 rounded-full px-2.5 py-0.5 text-[11px] font-medium
                                     {{ match ($task->status->value) {
                                         'done' => 'bg-emerald-50 text-emerald-700',
                                         'in_progress' => 'bg-blue-50 text-blue-700',
                                         'blocked' => 'bg-red-50 text-red-700',
                                         default => 'bg-stone-100 text-stone-600',
                                     } }}">
                            {{ $task->status->label() }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endforeach
</div>
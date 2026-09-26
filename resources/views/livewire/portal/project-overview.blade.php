<div class="space-y-6">
    {{-- Cabecera --}}
    <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-col items-start gap-6 sm:flex-row sm:items-center">
            <x-portal.ring :value="$project->progress" :size="132" />

            <div class="min-w-0 flex-1">
                <p class="text-xs font-medium uppercase tracking-wide text-stone-400">Obra {{ $project->code }}</p>
                <h1 class="mt-1 text-2xl font-semibold text-stone-900">{{ $project->name }}</h1>
                <p class="mt-1 text-sm text-stone-500">{{ $project->property?->full_address }}</p>

                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-medium text-stone-700">
                        {{ $project->status->label() }}
                    </span>
                    @if ($project->planned_end)
                        <span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-medium text-stone-700">
                            Fin previsto: {{ $project->planned_end->format('d/m/Y') }}
                        </span>
                    @endif
                    @if ($project->manager)
                        <span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-medium text-stone-700">
                            Jefe de obra: {{ $project->manager->name }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Avisos accionables --}}
    @if ($pendingExtras > 0)
        <a href="{{ route('portal.project.extras') }}"
           class="flex items-center justify-between gap-4 rounded-2xl border border-amber-300 bg-amber-50 p-5 transition hover:bg-amber-100">
            <div>
                <p class="font-semibold text-amber-900">
                    Tienes {{ $pendingExtras }} {{ $pendingExtras === 1 ? 'trabajo adicional pendiente' : 'trabajos adicionales pendientes' }} de tu aprobación
                </p>
                <p class="mt-0.5 text-sm text-amber-700">No los ejecutamos hasta que nos des el visto bueno.</p>
            </div>
            <span class="shrink-0 text-sm font-semibold text-amber-900">Revisar →</span>
        </a>
    @endif

    {{-- Fases --}}
    <div class="rounded-2xl border border-stone-200 bg-white shadow-sm">
        <div class="border-b border-stone-100 px-6 py-4">
            <h2 class="text-sm font-semibold text-stone-900">Cómo va cada fase</h2>
        </div>

        <div class="divide-y divide-stone-100">
            @forelse ($phases as $phase)
                @php
                    $done = $phase->tasks->where('status.value', 'done')->count();
                    $total = $phase->tasks->count();
                @endphp

                <div class="flex flex-wrap items-center gap-4 px-6 py-4">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold
                                {{ $phase->progress >= 100 ? 'bg-emerald-100 text-emerald-700' : ($phase->progress > 0 ? 'bg-stone-900 text-white' : 'bg-stone-100 text-stone-400') }}">
                        {{ $phase->progress >= 100 ? '✓' : $loop->iteration }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-stone-900">{{ $phase->name }}</p>
                        <p class="text-xs text-stone-500">
                            {{ $done }} de {{ $total }} trabajos completados
                            @if ($phase->planned_end) · previsto {{ $phase->planned_end->format('d/m/Y') }} @endif
                        </p>
                    </div>

                    <div class="w-28">
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-stone-200">
                            <div class="h-1.5 rounded-full transition-all duration-700
                                        {{ $phase->progress >= 100 ? 'bg-emerald-500' : 'bg-stone-900' }}"
                                 style="width: {{ $phase->progress }}%"></div>
                        </div>
                    </div>

                    <span class="w-10 text-right text-xs font-medium tabular-nums text-stone-500">{{ $phase->progress }} %</span>
                </div>
            @empty
                <p class="px-6 py-8 text-center text-sm text-stone-500">Aún estamos preparando la planificación.</p>
            @endforelse
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Novedades --}}
        <div class="rounded-2xl border border-stone-200 bg-white shadow-sm lg:col-span-2">
            <div class="border-b border-stone-100 px-6 py-4">
                <h2 class="text-sm font-semibold text-stone-900">Últimas novedades</h2>
            </div>

            <div class="divide-y divide-stone-100">
                @forelse ($updates as $update)
                    <article class="px-6 py-5">
                        <div class="flex items-baseline justify-between gap-3">
                            <h3 class="text-sm font-semibold text-stone-900">{{ $update->title ?: 'Parte de obra' }}</h3>
                            <time class="shrink-0 text-xs text-stone-400">{{ $update->published_at?->format('d/m/Y') }}</time>
                        </div>
                        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-stone-600">{{ $update->body }}</p>
                        @if ($update->author)
                            <p class="mt-2 text-xs text-stone-400">{{ $update->author->name }}</p>
                        @endif
                    </article>
                @empty
                    <p class="px-6 py-8 text-center text-sm text-stone-500">
                        Todavía no hemos publicado novedades. En cuanto empecemos, las verás aquí.
                    </p>
                @endforelse
            </div>
        </div>

        {{-- Lateral --}}
        <div class="space-y-6">
            @if ($recentPhotos->isNotEmpty())
                <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-stone-900">Fotos recientes</h2>
                        <a href="{{ route('portal.project.gallery') }}" class="text-xs font-medium text-stone-500 hover:text-stone-900">Ver todas</a>
                    </div>
                    <div class="grid grid-cols-3 gap-1.5">
                        @foreach ($recentPhotos as $photo)
                            <a href="{{ route('portal.photos.show', $photo) }}" target="_blank">
                                <img src="{{ route('portal.photos.show', ['photo' => $photo, 'thumb' => 1]) }}"
                                     alt="{{ $photo->caption }}" loading="lazy"
                                     class="aspect-square w-full rounded-lg object-cover transition hover:opacity-80">
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($nextEvents->isNotEmpty())
                <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-3 text-sm font-semibold text-stone-900">Próximas citas</h2>
                    <div class="space-y-3">
                        @foreach ($nextEvents as $event)
                            <div class="flex gap-3">
                                <div class="w-10 shrink-0 text-center">
                                    <p class="text-[11px] uppercase text-stone-400">{{ $event->starts_at->translatedFormat('M') }}</p>
                                    <p class="text-base font-semibold leading-none text-stone-900">{{ $event->starts_at->format('d') }}</p>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm text-stone-900">{{ $event->title }}</p>
                                    <p class="text-xs text-stone-500">
                                        {{ $event->all_day ? 'Todo el día' : $event->starts_at->format('H:i') }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-stone-900">¿Alguna duda?</h2>
                <p class="mt-1 text-sm text-stone-600">
                    Escríbenos por aquí y te respondemos sin perder el hilo de la obra.
                </p>
                <a href="{{ route('portal.project.messages') }}"
                   class="mt-3 inline-flex rounded-lg bg-stone-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-stone-700">
                    Enviar un mensaje
                </a>
            </div>
        </div>
    </div>
</div>
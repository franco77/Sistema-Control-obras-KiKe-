<div class="space-y-6">
    <header>
        <h1 class="text-2xl font-semibold text-stone-900">Incidencias</h1>
        <p class="mt-1 text-sm text-stone-600">
            Los imprevistos que han surgido y cómo los estamos resolviendo.
        </p>
    </header>

    @forelse ($incidents as $incident)
        <article class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-stone-900">{{ $incident->title }}</h2>
                    <p class="mt-0.5 text-xs text-stone-400">
                        {{ $incident->code }} · abierta el {{ $incident->opened_at->format('d/m/Y') }}
                        @if ($incident->phase) · {{ $incident->phase->name }} @endif
                    </p>
                </div>

                <div class="flex shrink-0 gap-2">
                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-medium
                                 {{ match ($incident->severity->value) {
                                     'critical', 'high' => 'bg-red-50 text-red-700',
                                     'medium' => 'bg-amber-50 text-amber-700',
                                     default => 'bg-stone-100 text-stone-600',
                                 } }}">{{ $incident->severity->label() }}</span>

                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-medium
                                 {{ in_array($incident->status->value, ['resolved', 'closed'])
                                    ? 'bg-emerald-50 text-emerald-700'
                                    : 'bg-stone-100 text-stone-600' }}">
                        {{ $incident->status->label() }}
                    </span>
                </div>
            </div>

            <p class="mt-4 whitespace-pre-line text-sm leading-relaxed text-stone-600">{{ $incident->description }}</p>

            @if ($incident->resolution)
                <div class="mt-4 rounded-xl bg-emerald-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Cómo se resolvió</p>
                    <p class="mt-1 text-sm leading-relaxed text-emerald-900">{{ $incident->resolution }}</p>
                    @if ($incident->resolved_at)
                        <p class="mt-1 text-xs text-emerald-600">{{ $incident->resolved_at->format('d/m/Y') }}</p>
                    @endif
                </div>
            @endif
        </article>
    @empty
        <div class="rounded-2xl border border-dashed border-stone-300 px-6 py-16 text-center">
            <p class="text-sm font-medium text-stone-900">Ninguna incidencia que comunicar</p>
            <p class="mt-1 text-sm text-stone-500">La obra avanza según lo previsto.</p>
        </div>
    @endforelse
</div>
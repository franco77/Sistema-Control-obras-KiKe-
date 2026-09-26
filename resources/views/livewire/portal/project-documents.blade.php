<div class="space-y-6">
    <header>
        <h1 class="text-2xl font-semibold text-stone-900">Documentos</h1>
        <p class="mt-1 text-sm text-stone-600">
            Presupuesto, contrato, facturas y certificados de tu obra, siempre disponibles.
        </p>
    </header>

    @if ($project->quote)
        <a href="{{ route('portal.quote.pdf', $project->quote) }}" target="_blank"
           class="flex items-center gap-4 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm transition hover:border-stone-400">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-stone-100">
                <x-icon name="document" class="h-5 w-5 text-stone-600" />
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-stone-900">Presupuesto {{ $project->quote->reference }}</p>
                <p class="text-xs text-stone-500">{{ money($project->quote->total) }} · aprobado el {{ $project->quote->decided_at?->format('d/m/Y') }}</p>
            </div>
            <span class="shrink-0 text-xs font-medium text-stone-500">Descargar PDF</span>
        </a>
    @endif

    @forelse ($documents as $category => $group)
        <section>
            <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-stone-400">{{ $category }}</h2>

            <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
                @foreach ($group as $document)
                    <a href="{{ route('portal.documents.download', $document) }}"
                       class="flex items-center gap-4 border-b border-stone-100 px-6 py-4 transition last:border-0 hover:bg-stone-50">
                        <x-icon name="document" class="h-5 w-5 shrink-0 text-stone-400" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-stone-900">{{ $document->name }}</p>
                            <p class="text-xs text-stone-500">
                                {{ $document->human_size }}
                                @if ($document->issued_on) · {{ $document->issued_on->format('d/m/Y') }} @endif
                            </p>
                        </div>
                        <span class="shrink-0 text-xs font-medium text-stone-500">Descargar</span>
                    </a>
                @endforeach
            </div>
        </section>
    @empty
        @if (! $project->quote)
            <div class="rounded-2xl border border-dashed border-stone-300 px-6 py-16 text-center">
                <p class="text-sm font-medium text-stone-900">Todavía no hay documentos</p>
                <p class="mt-1 text-sm text-stone-500">Aquí aparecerán el contrato, las facturas y los certificados.</p>
            </div>
        @endif
    @endforelse
</div>
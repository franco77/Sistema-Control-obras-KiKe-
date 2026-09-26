<div class="space-y-6">
    <header>
        <h1 class="text-2xl font-semibold text-stone-900">Trabajos adicionales</h1>
        <p class="mt-1 text-sm text-stone-600">
            Nada de esto se ejecuta sin tu aprobación. Tómate el tiempo que necesites.
        </p>
    </header>

    @forelse ($pending as $extra)
        <article class="overflow-hidden rounded-2xl border-2 border-amber-300 bg-white shadow-sm">
            <div class="bg-amber-50 px-6 py-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-800">Pendiente de tu aprobación</p>
            </div>

            <div class="p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <h2 class="text-lg font-semibold text-stone-900">{{ $extra->title }}</h2>
                        <p class="mt-0.5 text-xs text-stone-400">
                            {{ $extra->code }}
                            @if ($extra->phase) · {{ $extra->phase->name }} @endif
                            @if ($extra->sent_at) · enviado el {{ $extra->sent_at->format('d/m/Y') }} @endif
                        </p>
                    </div>

                    <div class="shrink-0 text-right">
                        <p class="text-2xl font-bold tabular-nums text-stone-900">{{ money($extra->total) }}</p>
                        <p class="text-xs text-stone-500">IVA incluido</p>
                    </div>
                </div>

                <p class="mt-4 whitespace-pre-line text-sm leading-relaxed text-stone-600">{{ $extra->description }}</p>

                @if ($extra->justification)
                    <div class="mt-4 rounded-xl bg-stone-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-stone-500">Por qué es necesario</p>
                        <p class="mt-1 text-sm leading-relaxed text-stone-700">{{ $extra->justification }}</p>
                    </div>
                @endif

                @if ($extra->extra_days > 0)
                    <p class="mt-3 text-sm text-amber-700">
                        Este trabajo añadiría <strong>{{ $extra->extra_days }} días</strong> al plazo de entrega.
                    </p>
                @endif

                {{-- Acciones --}}
                @if ($decidingId === $extra->id)
                    <div class="mt-5 rounded-xl border border-stone-200 p-4">
                        @if ($decision === 'approve')
                            <p class="text-sm font-medium text-stone-900">
                                Confirmar aprobación de {{ money($extra->total) }}
                            </p>
                            <label class="mt-3 block text-xs font-medium text-stone-700">Tu nombre y apellidos</label>
                            <input type="text" wire:model="signerName"
                                   class="mt-1 block w-full rounded-lg border-stone-300 text-sm focus:border-stone-900 focus:ring-stone-900">
                            @error('signerName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                            <div class="mt-4 flex gap-2">
                                <button wire:click="confirm"
                                        class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-500">
                                    Confirmar aprobación
                                </button>
                                <button wire:click="cancelDecision"
                                        class="rounded-lg border border-stone-300 px-5 py-2.5 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                                    Volver
                                </button>
                            </div>
                        @else
                            <p class="text-sm font-medium text-stone-900">¿Por qué prefieres no hacerlo?</p>
                            <textarea wire:model="rejectionReason" rows="3"
                                      class="mt-2 block w-full rounded-lg border-stone-300 text-sm focus:border-stone-900 focus:ring-stone-900"></textarea>
                            @error('rejectionReason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                            <div class="mt-4 flex gap-2">
                                <button wire:click="confirm"
                                        class="rounded-lg bg-stone-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-stone-700">
                                    Enviar respuesta
                                </button>
                                <button wire:click="cancelDecision"
                                        class="rounded-lg border border-stone-300 px-5 py-2.5 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                                    Volver
                                </button>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="mt-5 flex flex-wrap gap-3">
                        <button wire:click="startDecision({{ $extra->id }}, 'approve')"
                                class="rounded-lg bg-stone-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-stone-700">
                            Aprobar este trabajo
                        </button>
                        <button wire:click="startDecision({{ $extra->id }}, 'reject')"
                                class="rounded-lg border border-stone-300 px-5 py-2.5 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                            No, gracias
                        </button>
                    </div>
                @endif
            </div>
        </article>
    @empty
        <div class="rounded-2xl border border-dashed border-stone-300 px-6 py-16 text-center">
            <p class="text-sm font-medium text-stone-900">No hay nada pendiente de aprobar</p>
            <p class="mt-1 text-sm text-stone-500">Te avisaremos por email si surge algún trabajo adicional.</p>
        </div>
    @endforelse

    @if ($decided->isNotEmpty())
        <section>
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-stone-400">Historial</h2>

            <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
                @foreach ($decided as $extra)
                    <div class="flex flex-wrap items-center gap-4 border-b border-stone-100 px-6 py-4 last:border-0">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-stone-900">{{ $extra->title }}</p>
                            <p class="text-xs text-stone-500">
                                {{ $extra->status->value === 'approved' ? 'Aprobado' : 'Rechazado' }}
                                el {{ $extra->decided_at?->format('d/m/Y') }}
                                @if ($extra->signer_name) por {{ $extra->signer_name }} @endif
                            </p>
                        </div>

                        <p class="shrink-0 text-sm font-semibold tabular-nums
                                  {{ $extra->status->value === 'approved' ? 'text-stone-900' : 'text-stone-300 line-through' }}">
                            {{ money($extra->total) }}
                        </p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
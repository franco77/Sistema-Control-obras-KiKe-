<div class="space-y-6">
    {{-- Cabecera --}}
    <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-stone-400">Presupuesto {{ $quote->reference }}</p>
                <h1 class="mt-1 text-2xl font-semibold text-stone-900">{{ $quote->title }}</h1>
                <p class="mt-1 text-sm text-stone-500">
                    {{ $quote->property?->full_address ?? $quote->client->full_address }}
                </p>
            </div>

            <div class="text-right">
                <p class="text-3xl font-bold tabular-nums text-stone-900">{{ money($quote->total) }}</p>
                <p class="text-xs text-stone-500">IVA incluido</p>
                @if ($quote->valid_until)
                    <p class="mt-1 text-xs {{ $quote->isExpired() ? 'font-medium text-red-600' : 'text-stone-500' }}">
                        {{ $quote->isExpired() ? 'Caducado el' : 'Válido hasta el' }} {{ $quote->valid_until->format('d/m/Y') }}
                    </p>
                @endif
            </div>
        </div>

        @if ($quote->description)
            <p class="mt-5 whitespace-pre-line border-t border-stone-100 pt-5 text-sm leading-relaxed text-stone-600">
                {{ $quote->description }}
            </p>
        @endif

        <div class="mt-5 flex flex-wrap gap-3 border-t border-stone-100 pt-5">
            <a href="{{ route('portal.quote.pdf', $quote) }}" target="_blank"
               class="inline-flex items-center gap-2 rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                Descargar en PDF
            </a>

            @if ($quote->estimated_duration_days)
                <span class="inline-flex items-center gap-2 rounded-lg bg-stone-100 px-4 py-2 text-sm text-stone-600">
                    Duración estimada: {{ $quote->estimated_duration_days }} días
                </span>
            @endif
        </div>
    </div>

    {{-- Estado ya decidido --}}
    @if ($quote->status->value === 'approved')
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 text-center">
            <p class="text-lg font-semibold text-emerald-900">Presupuesto aprobado</p>
            <p class="mt-1 text-sm text-emerald-700">
                Aceptado por {{ $quote->signer_name }} el {{ $quote->decided_at?->format('d/m/Y \a \l\a\s H:i') }}.
                Nos pondremos en contacto contigo para concretar el inicio de los trabajos.
            </p>
        </div>
    @elseif ($quote->status->value === 'rejected')
        <div class="rounded-2xl border border-stone-200 bg-stone-50 p-6 text-center">
            <p class="text-lg font-semibold text-stone-900">Presupuesto rechazado</p>
            <p class="mt-1 text-sm text-stone-600">
                Registrado el {{ $quote->decided_at?->format('d/m/Y') }}.
                Si cambias de opinión o quieres otra propuesta, escríbenos.
            </p>
        </div>
    @elseif ($quote->isExpired())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-center">
            <p class="text-lg font-semibold text-amber-900">Este presupuesto ha caducado</p>
            <p class="mt-1 text-sm text-amber-700">Contáctanos y te preparamos una propuesta actualizada.</p>
        </div>
    @endif

    {{-- Detalle --}}
    <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
        @foreach ($quote->sections as $section)
            <div class="border-b border-stone-100 last:border-0">
                <div class="flex items-center justify-between bg-stone-50 px-6 py-3">
                    <h2 class="text-sm font-semibold text-stone-900">{{ $section->name }}</h2>
                    <p class="text-sm font-semibold tabular-nums text-stone-900">{{ money($section->subtotal) }}</p>
                </div>

                @if ($section->description)
                    <p class="px-6 pt-3 text-sm text-stone-500">{{ $section->description }}</p>
                @endif

                <div class="divide-y divide-stone-50">
                    @foreach ($section->items as $item)
                        <div class="flex flex-wrap items-start gap-4 px-6 py-4">
                            @if ($item->is_optional && $quote->isDecidable())
                                <label class="mt-0.5 flex shrink-0 items-center">
                                    <input type="checkbox"
                                           wire:click="toggleOptional({{ $item->id }})"
                                           @checked($item->is_included)
                                           class="h-4 w-4 rounded border-stone-300 text-stone-900 focus:ring-stone-500">
                                </label>
                            @endif

                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-stone-900">
                                    {{ $item->name }}
                                    @if ($item->is_optional)
                                        <span class="ml-1 rounded bg-stone-100 px-1.5 py-0.5 text-[11px] font-normal text-stone-600">
                                            opcional
                                        </span>
                                    @endif
                                </p>
                                @if ($item->description)
                                    <p class="mt-1 text-sm leading-relaxed text-stone-500">{{ $item->description }}</p>
                                @endif
                                <p class="mt-1 text-xs tabular-nums text-stone-400">
                                    {{ rtrim(rtrim(number_format((float) $item->quantity, 2, ',', '.'), '0'), ',') }}
                                    {{ $item->unit->label() }} × {{ money($item->unit_price) }}
                                </p>
                            </div>

                            <p @class([
                                'shrink-0 text-sm font-semibold tabular-nums',
                                'text-stone-900' => ! $item->is_optional || $item->is_included,
                                'text-stone-300 line-through' => $item->is_optional && ! $item->is_included,
                            ])>{{ money($item->total) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        {{-- Totales --}}
        <div class="bg-stone-50 px-6 py-5">
            <dl class="ml-auto max-w-xs space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-stone-500">Subtotal</dt>
                    <dd class="tabular-nums text-stone-900">{{ money($quote->items_total) }}</dd>
                </div>
                @if ($quote->discount_amount > 0)
                    <div class="flex justify-between">
                        <dt class="text-stone-500">Descuento</dt>
                        <dd class="tabular-nums text-emerald-700">-{{ money($quote->discount_amount) }}</dd>
                    </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-stone-500">Base imponible</dt>
                    <dd class="tabular-nums text-stone-900">{{ money($quote->taxable_base) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-stone-500">IVA ({{ (float) $quote->tax_rate }} %)</dt>
                    <dd class="tabular-nums text-stone-900">{{ money($quote->tax_amount) }}</dd>
                </div>
                <div class="flex justify-between border-t-2 border-stone-900 pt-2">
                    <dt class="font-semibold text-stone-900">Total</dt>
                    <dd class="text-xl font-bold tabular-nums text-stone-900">{{ money($quote->total) }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Condiciones --}}
    @if ($quote->payment_terms || $quote->terms || $quote->exclusions)
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach (['Forma de pago' => $quote->payment_terms, 'Condiciones' => $quote->terms, 'No incluye' => $quote->exclusions] as $label => $value)
                @if ($value)
                    <div class="rounded-xl border border-stone-200 bg-white p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-stone-400">{{ $label }}</p>
                        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-stone-600">{{ $value }}</p>
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    {{-- Decisión --}}
    @if ($quote->isDecidable())
        <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
            @if ($decision === '')
                <h2 class="text-lg font-semibold text-stone-900">¿Seguimos adelante?</h2>
                <p class="mt-1 text-sm text-stone-600">
                    Tu respuesta queda registrada con fecha y hora. Si tienes dudas antes de decidir, escríbenos
                    @if (setting('company.phone')) o llámanos al {{ setting('company.phone') }}@endif.
                </p>

                <div class="mt-5 flex flex-wrap gap-3">
                    <button wire:click="$set('decision', 'approve')"
                            class="inline-flex items-center gap-2 rounded-lg bg-stone-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-stone-700">
                        Aprobar presupuesto
                    </button>
                    <button wire:click="$set('decision', 'reject')"
                            class="inline-flex items-center gap-2 rounded-lg border border-stone-300 px-6 py-3 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                        No me interesa
                    </button>
                </div>

            @elseif ($decision === 'approve')
                <h2 class="text-lg font-semibold text-stone-900">Confirmar aprobación</h2>
                <p class="mt-1 text-sm text-stone-600">
                    Al aprobar aceptas el alcance y el importe de {{ money($quote->total) }} (IVA incluido).
                </p>

                <div class="mt-5 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-stone-700">Nombre y apellidos de quien aprueba</label>
                        <input type="text" wire:model="signerName"
                               class="mt-1 block w-full rounded-lg border-stone-300 text-sm focus:border-stone-900 focus:ring-stone-900">
                        @error('signerName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex items-start gap-3 text-sm text-stone-600">
                        <input type="checkbox" wire:model="acceptedTerms"
                               class="mt-0.5 h-4 w-4 rounded border-stone-300 text-stone-900 focus:ring-stone-500">
                        <span>He leído y acepto el alcance, el importe y las condiciones de este presupuesto.</span>
                    </label>
                    @error('acceptedTerms') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                    <div class="flex flex-wrap gap-3 pt-2">
                        <button wire:click="approve"
                                class="rounded-lg bg-emerald-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-emerald-500">
                            Confirmar aprobación
                        </button>
                        <button wire:click="$set('decision', '')"
                                class="rounded-lg border border-stone-300 px-6 py-3 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                            Volver
                        </button>
                    </div>
                </div>

            @else
                <h2 class="text-lg font-semibold text-stone-900">Cuéntanos por qué</h2>
                <p class="mt-1 text-sm text-stone-600">
                    Si es cuestión de precio, plazo o alcance, quizá podamos ajustarlo.
                </p>

                <div class="mt-5 space-y-4">
                    <textarea wire:model="rejectionReason" rows="4"
                              placeholder="Motivo del rechazo…"
                              class="block w-full rounded-lg border-stone-300 text-sm focus:border-stone-900 focus:ring-stone-900"></textarea>
                    @error('rejectionReason') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                    <div class="flex flex-wrap gap-3">
                        <button wire:click="reject"
                                class="rounded-lg bg-stone-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-stone-700">
                            Enviar respuesta
                        </button>
                        <button wire:click="$set('decision', '')"
                                class="rounded-lg border border-stone-300 px-6 py-3 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                            Volver
                        </button>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
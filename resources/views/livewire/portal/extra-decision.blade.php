<div class="mx-auto max-w-2xl space-y-6">
    <article class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
        <div class="border-b border-stone-100 bg-stone-50 px-6 py-4">
            <p class="text-xs font-medium uppercase tracking-wide text-stone-400">
                Obra {{ $extra->project->code }} · {{ $extra->project->name }}
            </p>
            <h1 class="mt-1 text-xl font-semibold text-stone-900">{{ $extra->title }}</h1>
        </div>

        <div class="p-6">
            <p class="whitespace-pre-line text-sm leading-relaxed text-stone-600">{{ $extra->description }}</p>

            @if ($extra->justification)
                <div class="mt-4 rounded-xl bg-stone-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-stone-500">Por qué es necesario</p>
                    <p class="mt-1 text-sm leading-relaxed text-stone-700">{{ $extra->justification }}</p>
                </div>
            @endif

            <dl class="mt-5 space-y-2 border-t border-stone-100 pt-5 text-sm">
                <div class="flex justify-between">
                    <dt class="text-stone-500">Importe</dt>
                    <dd class="tabular-nums text-stone-900">{{ money($extra->amount) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-stone-500">IVA ({{ (float) $extra->tax_rate }} %)</dt>
                    <dd class="tabular-nums text-stone-900">{{ money($extra->tax_amount) }}</dd>
                </div>
                <div class="flex justify-between border-t border-stone-900 pt-2">
                    <dt class="font-semibold text-stone-900">Total</dt>
                    <dd class="text-xl font-bold tabular-nums text-stone-900">{{ money($extra->total) }}</dd>
                </div>
                @if ($extra->extra_days > 0)
                    <p class="pt-2 text-xs text-amber-700">Añadiría {{ $extra->extra_days }} días al plazo.</p>
                @endif
            </dl>
        </div>
    </article>

    @if ($extra->isDecidable())
        <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            @if ($decision === '')
                <p class="text-sm text-stone-600">
                    No ejecutaremos este trabajo hasta que nos confirmes.
                </p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <button wire:click="$set('decision', 'approve')"
                            class="rounded-lg bg-stone-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-stone-700">
                        Aprobar
                    </button>
                    <button wire:click="$set('decision', 'reject')"
                            class="rounded-lg border border-stone-300 px-6 py-3 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                        No, gracias
                    </button>
                </div>

            @elseif ($decision === 'approve')
                <label class="block text-xs font-medium text-stone-700">Tu nombre y apellidos</label>
                <input type="text" wire:model="signerName"
                       class="mt-1 block w-full rounded-lg border-stone-300 text-sm focus:border-stone-900 focus:ring-stone-900">
                @error('signerName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                <div class="mt-4 flex gap-2">
                    <button wire:click="approve" class="rounded-lg bg-emerald-600 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-500">
                        Confirmar aprobación
                    </button>
                    <button wire:click="$set('decision', '')" class="rounded-lg border border-stone-300 px-6 py-3 text-sm font-medium text-stone-700">
                        Volver
                    </button>
                </div>

            @else
                <textarea wire:model="rejectionReason" rows="3" placeholder="Motivo…"
                          class="block w-full rounded-lg border-stone-300 text-sm focus:border-stone-900 focus:ring-stone-900"></textarea>
                @error('rejectionReason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                <div class="mt-4 flex gap-2">
                    <button wire:click="reject" class="rounded-lg bg-stone-900 px-6 py-3 text-sm font-semibold text-white hover:bg-stone-700">
                        Enviar respuesta
                    </button>
                    <button wire:click="$set('decision', '')" class="rounded-lg border border-stone-300 px-6 py-3 text-sm font-medium text-stone-700">
                        Volver
                    </button>
                </div>
            @endif
        </div>
    @else
        <div class="rounded-2xl border p-6 text-center
                    {{ $extra->status->value === 'approved' ? 'border-emerald-200 bg-emerald-50' : 'border-stone-200 bg-stone-50' }}">
            <p class="font-semibold {{ $extra->status->value === 'approved' ? 'text-emerald-900' : 'text-stone-900' }}">
                {{ $extra->status->label() }}
            </p>
            @if ($extra->decided_at)
                <p class="mt-1 text-sm text-stone-600">
                    Registrado el {{ $extra->decided_at->format('d/m/Y \a \l\a\s H:i') }}
                    @if ($extra->signer_name) por {{ $extra->signer_name }} @endif
                </p>
            @endif
        </div>
    @endif
</div>
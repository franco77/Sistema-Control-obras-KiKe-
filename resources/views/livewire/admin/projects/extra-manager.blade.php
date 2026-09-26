<div class="space-y-4">
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat label="Extras aprobados" :value="money($totals['approved'])" color="green" />
        <x-stat label="Pendientes de respuesta" :value="money($totals['pending'])" color="amber" />
        <div class="flex items-center justify-end">
            <x-btn icon="plus" wire:click="newExtra">Nuevo extra</x-btn>
        </div>
    </div>

    @if ($showForm)
        <x-card :title="$editingId ? 'Editar extra' : 'Nuevo extra'"
                subtitle="Describe con detalle qué se hará y por qué: es lo que verá el cliente al decidir.">
            <form wire:submit="save" class="grid gap-4 sm:grid-cols-6">
                <x-field label="Título" required class="sm:col-span-4" :error="$errors->first('form.title')">
                    <x-input wire:model="form.title" placeholder="Sustitución de bajante de fibrocemento" />
                </x-field>

                <x-field label="Fase" class="sm:col-span-2">
                    <x-select wire:model="form.project_phase_id">
                        <option value="">General</option>
                        @foreach ($phases as $phase)<option value="{{ $phase->id }}">{{ $phase->name }}</option>@endforeach
                    </x-select>
                </x-field>

                <x-field label="Descripción del trabajo" required class="sm:col-span-6" :error="$errors->first('form.description')">
                    <x-textarea wire:model="form.description" rows="3" />
                </x-field>

                <x-field label="Justificación" class="sm:col-span-4"
                         hint="Por qué es necesario; reduce muchísimo los rechazos">
                    <x-textarea wire:model="form.justification" rows="2" />
                </x-field>

                <x-field label="Incidencia origen" class="sm:col-span-2">
                    <x-select wire:model="form.project_incident_id">
                        <option value="">Ninguna</option>
                        @foreach ($incidents as $incident)
                            <option value="{{ $incident->id }}">{{ $incident->code }} — {{ $incident->title }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="Importe sin IVA (€)" required class="sm:col-span-2" :error="$errors->first('form.amount')">
                    <x-input type="number" step="0.01" wire:model.live="form.amount" />
                </x-field>

                <x-field label="IVA (%)" required class="sm:col-span-1">
                    <x-input type="number" step="0.01" wire:model.live="form.tax_rate" />
                </x-field>

                <div class="sm:col-span-1">
                    <p class="text-xs font-medium text-slate-700 dark:text-slate-300">Total</p>
                    <p class="mt-2 text-lg font-semibold tabular-nums text-slate-900 dark:text-slate-100">
                        {{ money((float) $form['amount'] * (1 + (float) $form['tax_rate'] / 100)) }}
                    </p>
                </div>

                <x-field label="Coste estimado (€)" class="sm:col-span-1">
                    <x-input type="number" step="0.01" wire:model="form.cost_estimated" />
                </x-field>

                <x-field label="Días adicionales" class="sm:col-span-1"
                         hint="Se suman al plazo al aprobarse">
                    <x-input type="number" wire:model="form.extra_days" />
                </x-field>

                <div class="flex gap-2 sm:col-span-6">
                    <x-btn type="submit">Guardar borrador</x-btn>
                    <x-btn variant="secondary" wire:click="$set('showForm', false)">Cancelar</x-btn>
                </div>
            </form>
        </x-card>
    @endif

    @forelse ($extras as $extra)
        <x-card wire:key="extra-{{ $extra->id }}">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-badge :color="$extra->status->color()" dot>{{ $extra->status->label() }}</x-badge>
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $extra->title }}</p>
                        <span class="text-xs text-slate-400">{{ $extra->code }}</span>
                    </div>

                    <p class="mt-2 whitespace-pre-line text-sm text-slate-600 dark:text-slate-300">{{ $extra->description }}</p>

                    @if ($extra->justification)
                        <p class="mt-2 text-xs italic text-slate-500">{{ $extra->justification }}</p>
                    @endif

                    <p class="mt-2 flex flex-wrap gap-x-3 text-xs text-slate-500">
                        @if ($extra->phase)<span>{{ $extra->phase->name }}</span>@endif
                        @if ($extra->incident)<span>de {{ $extra->incident->code }}</span>@endif
                        @if ($extra->extra_days > 0)<span class="text-amber-600">+{{ $extra->extra_days }} días de plazo</span>@endif
                        @if ($extra->sent_at)<span>enviado {{ $extra->sent_at->format('d/m/Y') }}</span>@endif
                        @if ($extra->decided_at)<span>decidido {{ $extra->decided_at->format('d/m/Y') }} por {{ $extra->signer_name }}</span>@endif
                    </p>

                    @if ($extra->rejection_reason)
                        <p class="mt-2 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700 dark:bg-red-500/10 dark:text-red-300">
                            <strong>Motivo del rechazo:</strong> {{ $extra->rejection_reason }}
                        </p>
                    @endif
                </div>

                <div class="shrink-0 text-right">
                    <p class="text-lg font-semibold tabular-nums text-slate-900 dark:text-slate-100">{{ money($extra->total) }}</p>
                    <p class="text-xs text-slate-500">{{ money($extra->amount) }} + IVA</p>

                    @can('manageFinancials', $project)
                        <p class="mt-1 text-xs text-slate-400">margen {{ money($extra->margin_amount) }}</p>
                    @endcan

                    <div class="mt-3 flex flex-col gap-1">
                        @if ($extra->status->is(App\Enums\ExtraStatus::Draft, App\Enums\ExtraStatus::Rejected))
                            <x-btn size="xs" wire:click="send({{ $extra->id }})"
                                   wire:confirm="Se enviará al cliente por email para su aprobación. ¿Continuar?">
                                Enviar al cliente
                            </x-btn>
                            <button wire:click="edit({{ $extra->id }})" class="text-xs font-medium text-indigo-600 hover:underline">Editar</button>
                        @endif

                        @if ($extra->status->value === 'sent')
                            <button wire:click="cancel({{ $extra->id }})" wire:confirm="¿Anular este extra?"
                                    class="text-xs font-medium text-red-600 hover:underline">Anular</button>
                        @endif
                    </div>
                </div>
            </div>
        </x-card>
    @empty
        <x-empty-state title="Sin extras"
                       description="Cuando surja trabajo fuera de presupuesto, créalo aquí y deja que el cliente lo apruebe por escrito." icon="euro" />
    @endforelse
</div>
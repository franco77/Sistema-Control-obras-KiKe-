<div>
    <x-page-header :title="$quote->title"
                   :subtitle="$quote->reference.' · '.$quote->client->name"
                   :back="route('admin.quotes.index')">
        <x-slot:actions>
            <x-badge :color="$quote->status->color()" size="md" dot>{{ $quote->status->label() }}</x-badge>

            <x-btn variant="secondary" :href="route('admin.quotes.pdf', $quote)" target="_blank">PDF</x-btn>

            @can('update', $quote)
                <x-btn variant="secondary" :href="route('admin.quotes.edit', $quote)">Editar</x-btn>
            @endcan

            @can('send', $quote)
                <x-btn wire:click="sendToClient" wire:confirm="¿Enviar el presupuesto al cliente por email?">
                    Enviar al cliente
                </x-btn>
            @endcan

            @can('createVersion', $quote)
                <x-btn variant="secondary" wire:click="createVersion"
                       wire:confirm="Se creará una versión nueva en borrador y esta quedará como sustituida. ¿Continuar?">
                    Nueva versión
                </x-btn>
            @endcan

            @can('convert', $quote)
                <x-btn variant="success" wire:click="openConvert">Convertir en obra</x-btn>
            @endcan
        </x-slot:actions>
    </x-page-header>

    @if ($quote->project)
        <div class="mb-6 flex items-center justify-between rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-500/30 dark:bg-emerald-500/10">
            <p class="text-sm text-emerald-800 dark:text-emerald-300">
                Este presupuesto ya está en ejecución como obra <strong>{{ $quote->project->code }}</strong>.
            </p>
            <x-btn size="sm" variant="secondary" :href="route('admin.projects.show', $quote->project)">Ir a la obra</x-btn>
        </div>
    @endif

    @if ($portalLink)
        <div class="mb-6 rounded-lg border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-500/30 dark:bg-indigo-500/10">
            <p class="text-xs font-medium text-indigo-900 dark:text-indigo-200">Enlace seguro del cliente (solo se muestra una vez):</p>
            <div class="mt-2 flex gap-2" x-data>
                <input type="text" readonly value="{{ $portalLink }}" x-ref="link" onclick="this.select()"
                       class="w-full rounded-lg border-indigo-200 bg-white px-3 py-1.5 font-mono text-xs dark:bg-slate-900">
                <x-btn size="sm" x-on:click="navigator.clipboard.writeText($refs.link.value)">Copiar</x-btn>
            </div>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Documento --}}
        <div class="space-y-6 lg:col-span-2">
            <x-card :padding="false">
                <div class="border-b border-slate-100 p-5 dark:border-slate-700">
                    <div class="flex flex-wrap justify-between gap-4 text-sm">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-slate-400">Cliente</p>
                            <p class="font-medium text-slate-900 dark:text-slate-100">{{ $quote->client->display_name }}</p>
                            @if ($quote->client->tax_id)<p class="text-xs text-slate-500">{{ $quote->client->tax_id }}</p>@endif
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-slate-400">Emplazamiento</p>
                            <p class="text-slate-900 dark:text-slate-100">{{ $quote->property?->full_address ?? '—' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs uppercase tracking-wide text-slate-400">Emitido</p>
                            <p class="text-slate-900 dark:text-slate-100">{{ $quote->issue_date->format('d/m/Y') }}</p>
                            @if ($quote->valid_until)
                                <p class="text-xs {{ $quote->isExpired() ? 'text-red-600' : 'text-slate-500' }}">
                                    válido hasta {{ $quote->valid_until->format('d/m/Y') }}
                                </p>
                            @endif
                        </div>
                    </div>

                    @if ($quote->description)
                        <p class="mt-4 whitespace-pre-line text-sm text-slate-600 dark:text-slate-300">{{ $quote->description }}</p>
                    @endif
                </div>

                @foreach ($quote->sections as $section)
                    <div class="border-b border-slate-100 last:border-0 dark:border-slate-700">
                        <div class="flex items-center justify-between bg-slate-50 px-5 py-2.5 dark:bg-slate-900/40">
                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $section->name }}</p>
                            <p class="text-sm font-semibold tabular-nums text-slate-900 dark:text-slate-100">{{ money($section->subtotal) }}</p>
                        </div>

                        <table class="min-w-full text-sm">
                            <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                                @foreach ($section->items as $item)
                                    <tr @class(['opacity-60' => $item->is_optional && ! $item->is_included])>
                                        <td class="px-5 py-2.5">
                                            <p class="text-slate-900 dark:text-slate-100">
                                                {{ $item->name }}
                                                @if ($item->is_optional)
                                                    <x-badge size="xs" color="purple">opcional</x-badge>
                                                @endif
                                            </p>
                                            @if ($item->description)
                                                <p class="mt-0.5 text-xs text-slate-500">{{ $item->description }}</p>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-2 py-2.5 text-right text-xs tabular-nums text-slate-500">
                                            {{ rtrim(rtrim(number_format((float) $item->quantity, 3, ',', '.'), '0'), ',') }} {{ $item->unit->label() }}
                                        </td>
                                        <td class="whitespace-nowrap px-2 py-2.5 text-right text-xs tabular-nums text-slate-500">
                                            {{ money($item->unit_price) }}
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-2.5 text-right font-medium tabular-nums text-slate-900 dark:text-slate-100">
                                            {{ money($item->total) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach

                <div class="border-t-2 border-slate-100 p-5 dark:border-slate-700">
                    <dl class="ml-auto max-w-xs space-y-1.5 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">Suma partidas</dt>
                            <dd class="tabular-nums">{{ money($quote->items_total) }}</dd></div>
                        @if ($quote->discount_amount > 0)
                            <div class="flex justify-between"><dt class="text-slate-500">Descuento</dt>
                                <dd class="tabular-nums text-red-600">-{{ money($quote->discount_amount) }}</dd></div>
                        @endif
                        <div class="flex justify-between"><dt class="text-slate-500">Base imponible</dt>
                            <dd class="tabular-nums">{{ money($quote->taxable_base) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">IVA {{ (float) $quote->tax_rate }} %</dt>
                            <dd class="tabular-nums">{{ money($quote->tax_amount) }}</dd></div>
                        <div class="flex justify-between border-t border-slate-900 pt-1.5 dark:border-slate-100">
                            <dt class="font-semibold">Total</dt>
                            <dd class="text-lg font-bold tabular-nums">{{ money($quote->total) }}</dd></div>
                    </dl>
                </div>

                @if ($quote->payment_terms || $quote->terms || $quote->exclusions)
                    <div class="grid gap-4 border-t border-slate-100 p-5 text-xs sm:grid-cols-3 dark:border-slate-700">
                        @foreach (['Forma de pago' => $quote->payment_terms, 'Condiciones' => $quote->terms, 'No incluye' => $quote->exclusions] as $label => $value)
                            @if ($value)
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-slate-100">{{ $label }}</p>
                                    <p class="mt-1 whitespace-pre-line text-slate-500">{{ $value }}</p>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>

        {{-- Lateral --}}
        <div class="space-y-6">
            <x-card title="Seguimiento">
                <dl class="space-y-2.5 text-sm">
                    @foreach ([
                        'Enviado' => $quote->sent_at?->format('d/m/Y H:i'),
                        'Primera apertura' => $quote->first_viewed_at?->format('d/m/Y H:i'),
                        'Última apertura' => $quote->last_viewed_at?->format('d/m/Y H:i'),
                        'Aperturas' => $quote->views_count ?: null,
                        'Decidido' => $quote->decided_at?->format('d/m/Y H:i'),
                        'Firmante' => $quote->signer_name,
                        'IP de decisión' => $quote->decision_ip,
                        'Creado por' => $quote->author?->name,
                    ] as $label => $value)
                        <div class="flex justify-between gap-2">
                            <dt class="text-slate-500">{{ $label }}</dt>
                            <dd class="text-right text-slate-900 dark:text-slate-100">{{ $value ?: '—' }}</dd>
                        </div>
                    @endforeach
                </dl>

                @if ($quote->rejection_reason)
                    <div class="mt-4 rounded-lg bg-red-50 p-3 dark:bg-red-500/10">
                        <p class="text-xs font-medium text-red-800 dark:text-red-300">Motivo del rechazo</p>
                        <p class="mt-1 text-sm text-red-700 dark:text-red-400">{{ $quote->rejection_reason }}</p>
                    </div>
                @endif

                <div class="mt-4 flex flex-wrap gap-2 border-t border-slate-100 pt-4 dark:border-slate-700">
                    @if ($quote->isDecidable())
                        <x-btn size="xs" variant="success" wire:click="openDecision('approve')">Marcar aprobado</x-btn>
                        <x-btn size="xs" variant="danger" wire:click="openDecision('reject')">Marcar rechazado</x-btn>
                    @endif
                    @can('update', $quote)
                        <x-btn size="xs" variant="secondary" wire:click="regeneratePortalLink">Regenerar enlace</x-btn>
                    @endcan
                    <x-btn size="xs" variant="ghost" wire:click="duplicate">Duplicar</x-btn>
                </div>
            </x-card>

            @can('manageFinancials', App\Models\Project::class)
                <x-card title="Rentabilidad prevista">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">Coste estimado</dt>
                            <dd class="tabular-nums">{{ money($quote->cost_total) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Margen</dt>
                            <dd class="font-medium tabular-nums">{{ money($quote->margin_amount) }}</dd></div>
                    </dl>
                    <x-progress class="mt-3" :value="min(100, (float) $quote->margin_percent)"
                                :label="'Margen '.number_format((float) $quote->margin_percent, 1).' %'" />
                </x-card>
            @endcan

            <x-card title="Versiones" :padding="false">
                @foreach ($versions as $version)
                    <a href="{{ route('admin.quotes.show', $version) }}"
                       @class([
                           'flex items-center justify-between border-b border-slate-100 px-5 py-3 text-sm transition last:border-0 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700/30',
                           'bg-indigo-50/60 dark:bg-indigo-500/5' => $version->id === $quote->id,
                       ])>
                        <div>
                            <p class="font-medium text-slate-900 dark:text-slate-100">Versión {{ $version->version }}</p>
                            <p class="text-xs text-slate-500">{{ $version->issue_date->format('d/m/Y') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="tabular-nums text-slate-900 dark:text-slate-100">{{ money($version->total) }}</p>
                            <x-badge :color="$version->status->color()" size="xs">{{ $version->status->label() }}</x-badge>
                        </div>
                    </a>
                @endforeach
            </x-card>

            <x-card title="Actividad" :padding="false">
                @forelse ($activities as $activity)
                    <div class="flex gap-3 border-b border-slate-100 px-5 py-2.5 last:border-0 dark:border-slate-700">
                        <div class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-400"></div>
                        <div class="min-w-0">
                            <p class="text-sm text-slate-900 dark:text-slate-100">{{ $activity->description ?: $activity->event }}</p>
                            <p class="text-xs text-slate-500">{{ $activity->causer_name }} · {{ $activity->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-4 text-sm text-slate-500">Sin actividad.</p>
                @endforelse
            </x-card>
        </div>
    </div>

    {{-- Modal: registrar decisión --}}
    @if ($showDecisionModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl dark:bg-slate-800">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                    {{ $decisionType === 'approve' ? 'Registrar aprobación' : 'Registrar rechazo' }}
                </h3>
                <p class="mt-1 text-xs text-slate-500">
                    Úsalo cuando el cliente te lo comunica por teléfono o en persona.
                </p>

                <div class="mt-4 space-y-3">
                    <x-field label="Nombre de quien decide" required :error="$errors->first('decisionSigner')">
                        <x-input wire:model="decisionSigner" />
                    </x-field>

                    <x-field :label="$decisionType === 'reject' ? 'Motivo del rechazo' : 'Observaciones'"
                             :required="$decisionType === 'reject'" :error="$errors->first('decisionReason')">
                        <x-textarea wire:model="decisionReason" rows="3" />
                    </x-field>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <x-btn variant="secondary" wire:click="$set('showDecisionModal', false)">Cancelar</x-btn>
                    <x-btn :variant="$decisionType === 'approve' ? 'success' : 'danger'" wire:click="recordDecision">
                        Confirmar
                    </x-btn>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal: convertir en obra --}}
    @if ($showConvertModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl dark:bg-slate-800">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Convertir en obra</h3>
                <p class="mt-1 text-xs text-slate-500">
                    Cada capítulo se convertirá en una fase y cada partida en una tarea.
                    El peso de cada fase se calcula según su importe.
                </p>

                <div class="mt-4 space-y-3">
                    <x-field label="Nombre de la obra" required :error="$errors->first('convert.name')">
                        <x-input wire:model="convert.name" />
                    </x-field>
                    <x-field label="Inicio previsto" required :error="$errors->first('convert.planned_start')">
                        <x-input type="date" wire:model="convert.planned_start" />
                    </x-field>
                    <x-field label="Jefe de obra">
                        <x-select wire:model="convert.manager_id">
                            <option value="">Sin asignar</option>
                            @foreach ($managers as $manager)
                                <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                            @endforeach
                        </x-select>
                    </x-field>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <x-btn variant="secondary" wire:click="$set('showConvertModal', false)">Cancelar</x-btn>
                    <x-btn variant="success" wire:click="convertToProject">Crear obra</x-btn>
                </div>
            </div>
        </div>
    @endif
</div>
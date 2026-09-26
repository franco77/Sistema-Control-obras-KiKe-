<div>
    <x-page-header :title="'Presupuesto '.$quoteModel->reference"
                   :subtitle="$quoteModel->status->label().' · última edición '.$quoteModel->updated_at->diffForHumans()"
                   :back="route('admin.quotes.index')">
        <x-slot:actions>
            <x-btn variant="secondary" :href="route('admin.quotes.pdf', $quoteModel)" target="_blank">Previsualizar PDF</x-btn>
            <x-btn variant="secondary" wire:click="save">Guardar</x-btn>
            <x-btn wire:click="sendToClient" wire:confirm="Se enviará al cliente por email con un enlace seguro. ¿Continuar?">
                Enviar al cliente
            </x-btn>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-4">
        {{-- Columna principal: capítulos y partidas --}}
        <div class="space-y-5 xl:col-span-3">
            <x-card title="Datos del presupuesto">
                <div class="grid gap-4 sm:grid-cols-6">
                    <x-field label="Cliente" required class="sm:col-span-3">
                        <x-select wire:model.live="header.client_id">
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Inmueble" class="sm:col-span-3">
                        <x-select wire:model.live="header.property_id">
                            <option value="">Sin inmueble concreto</option>
                            @foreach ($properties as $property)
                                <option value="{{ $property->id }}">{{ $property->alias }} — {{ $property->address }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Título" required class="sm:col-span-6" :error="$errors->first('header.title')">
                        <x-input wire:model.blur="header.title" placeholder="Reforma integral vivienda C/ Mayor 3" />
                    </x-field>

                    <x-field label="Descripción / alcance" class="sm:col-span-6">
                        <x-textarea wire:model.blur="header.description" rows="3"
                                    placeholder="Resumen del alcance que verá el cliente en la primera página." />
                    </x-field>

                    <x-field label="Fecha de emisión" required class="sm:col-span-2">
                        <x-input type="date" wire:model.blur="header.issue_date" />
                    </x-field>

                    <x-field label="Válido hasta" class="sm:col-span-2" :error="$errors->first('header.valid_until')">
                        <x-input type="date" wire:model.blur="header.valid_until" />
                    </x-field>

                    <x-field label="Duración estimada (días)" class="sm:col-span-2">
                        <x-input type="number" wire:model.blur="header.estimated_duration_days" />
                    </x-field>
                </div>
            </x-card>

            {{-- Capítulos --}}
            @foreach ($quoteModel->sections as $section)
                <x-card wire:key="section-{{ $section->id }}" :padding="false">
                    <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-4 dark:border-slate-700">
                        <div class="flex flex-col">
                            <button wire:click="moveSection({{ $section->id }}, -1)"
                                    class="text-slate-300 transition hover:text-indigo-600" title="Subir">&#9650;</button>
                            <button wire:click="moveSection({{ $section->id }}, 1)"
                                    class="text-slate-300 transition hover:text-indigo-600" title="Bajar">&#9660;</button>
                        </div>

                        <input type="text" wire:model.blur="sections.{{ $section->id }}.name"
                               class="flex-1 border-0 border-b border-transparent bg-transparent px-0 text-sm font-semibold text-slate-900 focus:border-indigo-500 focus:ring-0 dark:text-slate-100"
                               placeholder="Nombre del capítulo">

                        <x-select wire:model.blur="sections.{{ $section->id }}.trade_id" class="w-44 text-xs">
                            <option value="">Sin oficio</option>
                            @foreach ($trades as $trade)
                                <option value="{{ $trade->id }}">{{ $trade->name }}</option>
                            @endforeach
                        </x-select>

                        <span class="text-sm font-semibold tabular-nums text-slate-900 dark:text-slate-100">
                            {{ money($section->subtotal) }}
                        </span>

                        <button wire:click="removeSection({{ $section->id }})"
                                wire:confirm="Se eliminarán también sus partidas. ¿Seguro?"
                                class="text-xs font-medium text-red-600 hover:underline">Eliminar</button>
                    </div>

                    {{-- Partidas --}}
                    <div class="divide-y divide-slate-100 dark:divide-slate-700">
                        @forelse ($section->items as $item)
                            <div wire:key="item-{{ $item->id }}" class="grid grid-cols-12 gap-2 p-3 hover:bg-slate-50/50 dark:hover:bg-slate-700/20">
                                <div class="col-span-12 lg:col-span-5">
                                    <input type="text" wire:model.blur="items.{{ $item->id }}.name"
                                           class="w-full rounded-md border-slate-200 text-sm dark:border-slate-600 dark:bg-slate-900">
                                    <textarea wire:model.blur="items.{{ $item->id }}.description" rows="1"
                                              placeholder="Descripción larga (opcional)"
                                              class="mt-1 w-full rounded-md border-slate-200 text-xs text-slate-500 dark:border-slate-600 dark:bg-slate-900"></textarea>
                                </div>

                                <div class="col-span-3 lg:col-span-1">
                                    <input type="number" step="0.001" wire:model.blur="items.{{ $item->id }}.quantity"
                                           class="w-full rounded-md border-slate-200 text-right text-sm tabular-nums dark:border-slate-600 dark:bg-slate-900">
                                </div>

                                <div class="col-span-3 lg:col-span-1">
                                    <select wire:model.blur="items.{{ $item->id }}.unit"
                                            class="w-full rounded-md border-slate-200 text-xs dark:border-slate-600 dark:bg-slate-900">
                                        @foreach ($units as $unit)
                                            <option value="{{ $unit->value }}">{{ $unit->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-span-3 lg:col-span-1">
                                    <input type="number" step="0.01" wire:model.blur="items.{{ $item->id }}.unit_cost"
                                           title="Coste interno"
                                           class="w-full rounded-md border-slate-200 bg-slate-50 text-right text-sm tabular-nums text-slate-500 dark:border-slate-600 dark:bg-slate-900">
                                </div>

                                <div class="col-span-3 lg:col-span-1">
                                    <input type="number" step="0.01" wire:model.blur="items.{{ $item->id }}.unit_price"
                                           title="Precio de venta"
                                           class="w-full rounded-md border-slate-200 text-right text-sm font-medium tabular-nums dark:border-slate-600 dark:bg-slate-900">
                                </div>

                                <div class="col-span-6 flex items-center justify-end gap-2 lg:col-span-3">
                                    <label class="flex items-center gap-1 text-[11px] text-slate-500" title="El cliente decide si la contrata">
                                        <input type="checkbox" wire:model.live="items.{{ $item->id }}.is_optional"
                                               class="rounded border-slate-300 text-indigo-600">
                                        opcional
                                    </label>

                                    <span class="min-w-20 text-right text-sm font-semibold tabular-nums text-slate-900 dark:text-slate-100">
                                        {{ money($item->total) }}
                                    </span>

                                    <button wire:click="duplicateItem({{ $item->id }})"
                                            class="text-slate-400 transition hover:text-indigo-600" title="Duplicar">&#10697;</button>
                                    <button wire:click="removeItem({{ $item->id }})"
                                            class="text-slate-400 transition hover:text-red-600" title="Eliminar">&times;</button>
                                </div>
                            </div>
                        @empty
                            <p class="px-4 py-6 text-center text-sm text-slate-400">Este capítulo aún no tiene partidas.</p>
                        @endforelse
                    </div>

                    <div class="flex gap-2 border-t border-slate-100 p-3 dark:border-slate-700">
                        <x-btn size="xs" variant="secondary" wire:click="addItem({{ $section->id }})" icon="plus">
                            Partida en blanco
                        </x-btn>
                        <x-btn size="xs" variant="secondary" wire:click="openCatalog({{ $section->id }})">
                            Desde catálogo
                        </x-btn>
                    </div>
                </x-card>
            @endforeach

            <x-btn variant="secondary" wire:click="addSection" icon="plus" class="w-full">Añadir capítulo</x-btn>

            <x-card title="Condiciones">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Forma de pago">
                        <x-textarea wire:model.blur="header.payment_terms" rows="3" />
                    </x-field>
                    <x-field label="Condiciones generales">
                        <x-textarea wire:model.blur="header.terms" rows="3" />
                    </x-field>
                    <x-field label="No incluye" hint="Lo que queda fuera del precio; evita discusiones después">
                        <x-textarea wire:model.blur="header.exclusions" rows="3" />
                    </x-field>
                    <x-field label="Notas internas" hint="No aparecen en el PDF">
                        <x-textarea wire:model.blur="header.internal_notes" rows="3" />
                    </x-field>
                </div>
            </x-card>
        </div>

        {{-- Columna lateral: totales --}}
        <div class="space-y-5">
            <div class="sticky top-20 space-y-5">
                <x-card title="Resumen económico">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Suma de partidas</dt>
                            <dd class="tabular-nums text-slate-900 dark:text-slate-100">{{ money($quoteModel->items_total) }}</dd>
                        </div>

                        <div class="space-y-2 rounded-lg bg-slate-50 p-3 dark:bg-slate-900/40">
                            <x-field label="Descuento">
                                <x-select wire:model.live="header.discount_type" class="text-xs">
                                    @foreach ($discountTypes as $type)
                                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                    @endforeach
                                </x-select>
                            </x-field>

                            @if ($header['discount_type'] !== 'none')
                                <x-input type="number" step="0.01" wire:model.blur="header.discount_value"
                                         class="text-right text-xs" />
                                <div class="flex justify-between text-xs">
                                    <span class="text-slate-500">Descuento aplicado</span>
                                    <span class="tabular-nums text-red-600">-{{ money($quoteModel->discount_amount) }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="flex justify-between border-t border-slate-100 pt-2 dark:border-slate-700">
                            <dt class="text-slate-500">Base imponible</dt>
                            <dd class="font-medium tabular-nums text-slate-900 dark:text-slate-100">{{ money($quoteModel->taxable_base) }}</dd>
                        </div>

                        <div class="flex items-center justify-between">
                            <dt class="flex items-center gap-2 text-slate-500">
                                IVA
                                <input type="number" step="0.01" wire:model.blur="header.tax_rate"
                                       class="w-16 rounded border-slate-200 py-0.5 text-right text-xs dark:border-slate-600 dark:bg-slate-900">
                                %
                            </dt>
                            <dd class="tabular-nums text-slate-900 dark:text-slate-100">{{ money($quoteModel->tax_amount) }}</dd>
                        </div>

                        <div class="flex justify-between border-t-2 border-slate-900 pt-2 dark:border-slate-100">
                            <dt class="font-semibold text-slate-900 dark:text-slate-100">Total</dt>
                            <dd class="text-lg font-bold tabular-nums text-slate-900 dark:text-slate-100">{{ money($quoteModel->total) }}</dd>
                        </div>
                    </dl>
                </x-card>

                @can('manageFinancials', App\Models\Project::class)
                    <x-card title="Rentabilidad" subtitle="Solo visible internamente">
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-slate-500">Coste estimado</dt>
                                <dd class="tabular-nums">{{ money($quoteModel->cost_total) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-slate-500">Margen</dt>
                                <dd class="font-medium tabular-nums">{{ money($quoteModel->margin_amount) }}</dd>
                            </div>
                        </dl>

                        <div class="mt-3">
                            <x-progress :value="min(100, (float) $quoteModel->margin_percent)"
                                        :label="'Margen '.number_format((float) $quoteModel->margin_percent, 1).' %'" />
                        </div>

                        @if ($quoteModel->margin_percent < 12 && $quoteModel->items_total > 0)
                            <p class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700 dark:bg-red-500/10 dark:text-red-300">
                                Margen por debajo del 12 %. Revisa costes o precios antes de enviarlo.
                            </p>
                        @endif
                    </x-card>
                @endcan
            </div>
        </div>
    </div>

    {{-- Selector de catálogo --}}
    @if ($catalogTargetSection)
        <div class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-slate-900/50 p-4 backdrop-blur-sm">
            <div class="mt-16 w-full max-w-2xl overflow-hidden rounded-xl bg-white shadow-xl dark:bg-slate-800">
                <div class="flex items-center gap-3 border-b border-slate-100 p-4 dark:border-slate-700">
                    <x-input wire:model.live.debounce.300ms="catalogSearch" autofocus
                             placeholder="Buscar en el catálogo de partidas…" class="flex-1" />
                    <x-btn variant="ghost" wire:click="closeCatalog">Cerrar</x-btn>
                </div>

                <div class="max-h-96 divide-y divide-slate-100 overflow-y-auto dark:divide-slate-700">
                    @forelse ($catalogResults as $result)
                        <button wire:click="addCatalogItem({{ $result->id }})"
                                class="flex w-full items-center gap-3 px-4 py-3 text-left transition hover:bg-indigo-50 dark:hover:bg-indigo-500/10">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{ $result->name }}</p>
                                <p class="text-xs text-slate-500">
                                    @if ($result->code) {{ $result->code }} · @endif
                                    {{ $result->trade?->name ?? 'Sin oficio' }} · {{ $result->unit->label() }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold tabular-nums">{{ money($result->unit_price) }}</p>
                                <p class="text-xs text-slate-400">coste {{ money($result->unit_cost) }}</p>
                            </div>
                        </button>
                    @empty
                        <p class="px-4 py-8 text-center text-sm text-slate-500">
                            Sin resultados. Puedes añadir una partida en blanco y guardarla luego en el catálogo.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
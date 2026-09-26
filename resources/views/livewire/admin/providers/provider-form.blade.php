<div>
    <x-page-header :title="$provider ? 'Editar proveedor' : 'Nuevo proveedor'"
                   :subtitle="$provider?->code"
                   :back="$provider ? route('admin.providers.show', $provider) : route('admin.providers.index')" />

    <form wire:submit="save" class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card title="Identificación">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Tipo" required>
                        <x-select wire:model="form.type">
                            @foreach ($types as $case)<option value="{{ $case->value }}">{{ $case->label() }}</option>@endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Estado" required hint="«Pendiente documentación» impide asignarle tareas">
                        <x-select wire:model="form.status">
                            @foreach ($statuses as $case)<option value="{{ $case->value }}">{{ $case->label() }}</option>@endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Nombre" required :error="$errors->first('form.name')">
                        <x-input wire:model="form.name" />
                    </x-field>

                    <x-field label="Razón social">
                        <x-input wire:model="form.legal_name" />
                    </x-field>

                    <x-field label="NIF / CIF" :error="$errors->first('form.tax_id')">
                        <x-input wire:model="form.tax_id" />
                    </x-field>

                    <x-field label="IBAN" hint="Para los pagos">
                        <x-input wire:model="form.iban" />
                    </x-field>
                </div>
            </x-card>

            <x-card title="Oficios y tarifas"
                    subtitle="Marca los oficios que realiza; la tarifa concreta prevalece sobre la general.">
                <div class="space-y-2">
                    @foreach ($allTrades as $trade)
                        <div wire:key="trade-{{ $trade->id }}"
                             class="grid grid-cols-12 items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700">
                            <label class="col-span-5 flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                                <input type="checkbox" wire:model.live="trades.{{ $trade->id }}.selected"
                                       class="rounded border-slate-300 text-indigo-600">
                                <x-badge :color="$trade->color" size="xs">{{ $trade->name }}</x-badge>
                            </label>

                            @if ($trades[$trade->id]['selected'] ?? false)
                                <label class="col-span-3 flex items-center gap-2 text-xs text-slate-500">
                                    <input type="radio" name="primary_trade" value="{{ $trade->id }}"
                                           wire:model="trades.{{ $trade->id }}.is_primary"
                                           class="border-slate-300 text-indigo-600">
                                    Principal
                                </label>
                                <div class="col-span-2">
                                    <x-input type="number" step="0.01" placeholder="€/h"
                                             wire:model="trades.{{ $trade->id }}.hourly_rate" class="text-xs" />
                                </div>
                                <div class="col-span-2">
                                    <x-input type="number" placeholder="Años exp."
                                             wire:model="trades.{{ $trade->id }}.experience_years" class="text-xs" />
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-card>

            <x-card title="Ubicación y contacto">
                <div class="grid gap-4 sm:grid-cols-6">
                    <x-field label="Email" class="sm:col-span-3" :error="$errors->first('form.email')">
                        <x-input type="email" wire:model="form.email" />
                    </x-field>
                    <x-field label="Teléfono" class="sm:col-span-3">
                        <x-input wire:model="form.phone" />
                    </x-field>
                    <x-field label="Dirección" class="sm:col-span-4">
                        <x-input wire:model="form.address" />
                    </x-field>
                    <x-field label="C.P." class="sm:col-span-2">
                        <x-input wire:model="form.postal_code" />
                    </x-field>
                    <x-field label="Ciudad" class="sm:col-span-3">
                        <x-input wire:model="form.city" />
                    </x-field>
                    <x-field label="Provincia" class="sm:col-span-3">
                        <x-input wire:model="form.province" />
                    </x-field>
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="Condiciones">
                <div class="space-y-4">
                    <x-field label="Tarifa por hora (€)" :error="$errors->first('form.default_hourly_rate')">
                        <x-input type="number" step="0.01" wire:model="form.default_hourly_rate" />
                    </x-field>

                    <x-field label="Retención IRPF (%)" hint="Solo autónomos">
                        <x-input type="number" step="0.01" wire:model="form.irpf_rate" />
                    </x-field>

                    <x-field label="Obras simultáneas máx." required
                             hint="Se usa como aviso al asignar tareas">
                        <x-input type="number" wire:model="form.max_parallel_projects" />
                    </x-field>

                    <x-field label="Radio de desplazamiento (km)">
                        <x-input type="number" wire:model="form.radius_km" />
                    </x-field>

                    <x-field label="Notas internas">
                        <x-textarea wire:model="form.notes" rows="4" />
                    </x-field>
                </div>
            </x-card>

            <div class="flex flex-col gap-2">
                <x-btn type="submit" size="lg">{{ $provider ? 'Guardar cambios' : 'Crear proveedor' }}</x-btn>
                <x-btn variant="secondary" :href="route('admin.providers.index')">Cancelar</x-btn>
            </div>
        </div>
    </form>
</div>
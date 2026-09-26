<div>
    <x-page-header :title="$client ? 'Editar cliente' : 'Nuevo cliente'"
                   :subtitle="$client?->code"
                   :back="$client ? route('admin.clients.show', $client) : route('admin.clients.index')" />

    <form wire:submit="save" class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card title="Datos principales">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Tipo de cliente" required>
                        <x-select wire:model.live="form.type">
                            @foreach ($types as $case)
                                <option value="{{ $case->value }}">{{ $case->label() }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Estado" required>
                        <x-select wire:model="form.status">
                            @foreach ($statuses as $case)
                                <option value="{{ $case->value }}">{{ $case->label() }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Nombre" required :error="$errors->first('form.name')" class="sm:col-span-2">
                        <x-input wire:model="form.name" placeholder="Nombre y apellidos o nombre comercial" />
                    </x-field>

                    @if ($form->type !== 'individual')
                        <x-field label="Razón social" :error="$errors->first('form.legal_name')">
                            <x-input wire:model="form.legal_name" />
                        </x-field>
                    @endif

                    <x-field label="NIF / CIF / NIE" :error="$errors->first('form.tax_id')">
                        <x-input wire:model="form.tax_id" placeholder="B12345678" />
                    </x-field>
                </div>
            </x-card>

            <x-card title="Contacto">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Email" :error="$errors->first('form.email')"
                             hint="Se usa para enviar presupuestos y avisos de obra">
                        <x-input type="email" wire:model="form.email" />
                    </x-field>

                    <x-field label="Canal preferido">
                        <x-select wire:model="form.preferred_channel">
                            <option value="email">Email</option>
                            <option value="phone">Teléfono</option>
                            <option value="whatsapp">WhatsApp</option>
                        </x-select>
                    </x-field>

                    <x-field label="Teléfono" :error="$errors->first('form.phone')">
                        <x-input wire:model="form.phone" />
                    </x-field>

                    <x-field label="Teléfono alternativo">
                        <x-input wire:model="form.phone_alt" />
                    </x-field>
                </div>
            </x-card>

            <x-card title="Dirección fiscal">
                <div class="grid gap-4 sm:grid-cols-6">
                    <x-field label="Dirección" class="sm:col-span-4" :error="$errors->first('form.address')">
                        <x-input wire:model="form.address" />
                    </x-field>

                    <x-field label="Piso / puerta" class="sm:col-span-2">
                        <x-input wire:model="form.address_extra" />
                    </x-field>

                    <x-field label="C.P." class="sm:col-span-1">
                        <x-input wire:model="form.postal_code" />
                    </x-field>

                    <x-field label="Ciudad" class="sm:col-span-3">
                        <x-input wire:model="form.city" />
                    </x-field>

                    <x-field label="Provincia" class="sm:col-span-2">
                        <x-input wire:model="form.province" />
                    </x-field>
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="Seguimiento comercial">
                <div class="space-y-4">
                    <x-field label="Responsable">
                        <x-select wire:model="form.owner_id">
                            <option value="">Sin asignar</option>
                            @foreach ($owners as $owner)
                                <option value="{{ $owner->id }}">{{ $owner->name }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Origen" hint="Web, recomendación, campaña…">
                        <x-input wire:model="form.source" />
                    </x-field>

                    <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                        <input type="checkbox" wire:model="form.accepts_marketing"
                               class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Acepta comunicaciones comerciales
                    </label>

                    <x-field label="Notas internas" hint="No se muestran al cliente">
                        <x-textarea wire:model="form.notes" rows="5" />
                    </x-field>
                </div>
            </x-card>

            <div class="flex flex-col gap-2">
                <x-btn type="submit" size="lg" wire:loading.attr="disabled">
                    {{ $client ? 'Guardar cambios' : 'Crear cliente' }}
                </x-btn>
                <x-btn variant="secondary"
                       :href="$client ? route('admin.clients.show', $client) : route('admin.clients.index')">
                    Cancelar
                </x-btn>
            </div>
        </div>
    </form>
</div>
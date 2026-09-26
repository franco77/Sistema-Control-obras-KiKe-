<div class="space-y-4">
    <div class="flex justify-end">
        <x-btn size="sm" icon="plus" wire:click="create">Añadir inmueble</x-btn>
    </div>

    @if ($showForm)
        <x-card :title="$editingId ? 'Editar inmueble' : 'Nuevo inmueble'">
            <form wire:submit="save" class="grid gap-4 sm:grid-cols-6">
                <x-field label="Alias" required class="sm:col-span-2" :error="$errors->first('form.alias')"
                         hint="Cómo lo llamáis internamente">
                    <x-input wire:model="form.alias" placeholder="Piso Chamberí" />
                </x-field>

                <x-field label="Tipo" required class="sm:col-span-2">
                    <x-select wire:model="form.type">
                        @foreach ($types as $case)
                            <option value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="Ref. catastral" class="sm:col-span-2">
                    <x-input wire:model="form.cadastral_reference" />
                </x-field>

                <x-field label="Dirección" required class="sm:col-span-3" :error="$errors->first('form.address')">
                    <x-input wire:model="form.address" />
                </x-field>

                <x-field label="Bloque" class="sm:col-span-1"><x-input wire:model="form.block" /></x-field>
                <x-field label="Planta" class="sm:col-span-1"><x-input wire:model="form.floor" /></x-field>
                <x-field label="Puerta" class="sm:col-span-1"><x-input wire:model="form.door" /></x-field>

                <x-field label="C.P." class="sm:col-span-1"><x-input wire:model="form.postal_code" /></x-field>
                <x-field label="Ciudad" class="sm:col-span-3"><x-input wire:model="form.city" /></x-field>
                <x-field label="Provincia" class="sm:col-span-2"><x-input wire:model="form.province" /></x-field>

                <x-field label="m² construidos" class="sm:col-span-1"><x-input type="number" step="0.01" wire:model="form.built_area" /></x-field>
                <x-field label="m² útiles" class="sm:col-span-1"><x-input type="number" step="0.01" wire:model="form.usable_area" /></x-field>
                <x-field label="Habitaciones" class="sm:col-span-1"><x-input type="number" wire:model="form.rooms" /></x-field>
                <x-field label="Baños" class="sm:col-span-1"><x-input type="number" wire:model="form.bathrooms" /></x-field>
                <x-field label="Año construcción" class="sm:col-span-2" :error="$errors->first('form.year_built')">
                    <x-input type="number" wire:model="form.year_built" />
                </x-field>

                <div class="flex items-center gap-6 sm:col-span-6">
                    <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                        <input type="checkbox" wire:model="form.has_elevator" class="rounded border-slate-300 text-indigo-600">
                        Tiene ascensor
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                        <input type="checkbox" wire:model="form.is_occupied" class="rounded border-slate-300 text-indigo-600">
                        Está habitado durante la obra
                    </label>
                </div>

                <x-field label="Notas de acceso" class="sm:col-span-3"
                         hint="Portero, llaves, horarios permitidos, ruidos…">
                    <x-textarea wire:model="form.access_notes" rows="3" />
                </x-field>

                <x-field label="Notas" class="sm:col-span-3">
                    <x-textarea wire:model="form.notes" rows="3" />
                </x-field>

                <div class="flex gap-2 sm:col-span-6">
                    <x-btn type="submit">{{ $editingId ? 'Guardar' : 'Añadir' }}</x-btn>
                    <x-btn variant="secondary" wire:click="$set('showForm', false)">Cancelar</x-btn>
                </div>
            </form>
        </x-card>
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        @forelse ($properties as $property)
            <div wire:key="prop-{{ $property->id }}"
                 class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{ $property->alias }}</p>
                        <p class="text-xs text-slate-500">{{ $property->full_address }}</p>
                    </div>
                    <x-badge :color="$property->type->color()" size="xs">{{ $property->type->label() }}</x-badge>
                </div>

                <dl class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                    @if ($property->built_area)<div>{{ $property->built_area }} m²</div>@endif
                    @if ($property->rooms)<div>{{ $property->rooms }} hab.</div>@endif
                    @if ($property->bathrooms)<div>{{ $property->bathrooms }} baños</div>@endif
                    @if ($property->year_built)<div>Año {{ $property->year_built }}</div>@endif
                    <div>{{ $property->has_elevator ? 'Con ascensor' : 'Sin ascensor' }}</div>
                    <div>{{ $property->projects_count }} obras</div>
                </dl>

                @if ($property->access_notes)
                    <p class="mt-2 rounded-lg bg-amber-50 px-2.5 py-1.5 text-xs text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
                        Acceso: {{ $property->access_notes }}
                    </p>
                @endif

                <div class="mt-3 flex gap-3 border-t border-slate-100 pt-3 dark:border-slate-700">
                    <button wire:click="edit({{ $property->id }})" class="text-xs font-medium text-indigo-600 hover:underline">Editar</button>
                    <button wire:click="delete({{ $property->id }})" wire:confirm="¿Eliminar este inmueble?"
                            class="text-xs font-medium text-red-600 hover:underline">Eliminar</button>
                    <a href="{{ route('admin.quotes.create', ['cliente' => $client->id, 'inmueble' => $property->id]) }}"
                       class="ml-auto text-xs font-medium text-slate-600 hover:underline">Presupuestar</a>
                </div>
            </div>
        @empty
            <div class="sm:col-span-2">
                <x-empty-state title="Sin inmuebles" description="Añade el piso, local o vivienda donde se ejecutará la reforma." icon="building" />
            </div>
        @endforelse
    </div>
</div>
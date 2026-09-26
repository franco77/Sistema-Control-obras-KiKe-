<div>
    <x-page-header title="Configuración" subtitle="Datos de la empresa, valores por defecto y oficios" />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Logo: formulario propio, porque sube fichero y no debe
                 arrastrar consigo el resto de la configuración. --}}
            <x-card title="Logo de la empresa"
                    subtitle="Aparece en el panel, en el portal del cliente y en la cabecera de los PDF.">
                <div class="flex flex-wrap items-start gap-6">
                    <div class="flex h-24 w-44 shrink-0 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 p-3 dark:border-slate-600 dark:bg-slate-900/40">
                        {{-- isPreviewable(): si el usuario elige un fichero que no es
                             imagen, temporaryUrl() lanza excepción y tumbaría la
                             pantalla antes de que se vea el mensaje de validación. --}}
                        @if ($logo && $logo->isPreviewable())
                            <img src="{{ $logo->temporaryUrl() }}" alt="Vista previa"
                                 class="max-h-full max-w-full object-contain">
                        @elseif ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="Logo actual"
                                 class="max-h-full max-w-full object-contain">
                        @else
                            <span class="text-center text-xs text-slate-400">Sin logo<br>se usan las iniciales</span>
                        @endif
                    </div>

                    <div class="min-w-64 flex-1 space-y-3">
                        <input type="file" wire:model="logo" accept="image/png,image/jpeg,image/webp"
                               class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-indigo-700 hover:file:bg-indigo-100">

                        @error('logo')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror

                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            PNG, JPG o WEBP, hasta 2 MB. Se reescala a 320 px de alto y se guarda
                            como PNG para conservar la transparencia.
                        </p>

                        <div class="flex flex-wrap gap-2">
                            <x-btn size="sm" wire:click="saveLogo" wire:loading.attr="disabled" wire:target="logo,saveLogo">
                                <span wire:loading.remove wire:target="logo,saveLogo">Guardar logo</span>
                                <span wire:loading wire:target="logo,saveLogo">Subiendo…</span>
                            </x-btn>

                            @if ($logo)
                                <x-btn size="sm" variant="secondary" wire:click="$set('logo', null)">Descartar</x-btn>
                            @endif

                            @if ($logoUrl)
                                <x-btn size="sm" variant="danger" wire:click="removeLogo"
                                       wire:confirm="¿Quitar el logo y volver a las iniciales?">
                                    Quitar logo
                                </x-btn>
                            @endif
                        </div>
                    </div>
                </div>
            </x-card>

            <form wire:submit="save" class="space-y-6">
            @foreach ($groups as $group => $keys)
                <x-card :title="ucfirst($group)">
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($keys as $key => [$label, $cast, $g])
                            @php $bind = str_replace('.', '_', $key); @endphp
                            <x-field :label="$label" :error="$errors->first('values.'.$bind)"
                                     class="{{ $cast === 'text' ? 'sm:col-span-2' : '' }}">
                                @if ($cast === 'bool')
                                    <label class="flex items-center gap-2 pt-1 text-sm text-slate-600 dark:text-slate-300">
                                        <input type="checkbox" wire:model="values.{{ $bind }}"
                                               class="rounded border-slate-300 text-indigo-600">
                                        Activado
                                    </label>
                                @elseif ($cast === 'text')
                                    <x-textarea wire:model="values.{{ $bind }}" rows="3" />
                                @elseif (in_array($cast, ['int', 'float']))
                                    <x-input type="number" step="{{ $cast === 'float' ? '0.01' : '1' }}"
                                             wire:model="values.{{ $bind }}" />
                                @else
                                    <x-input wire:model="values.{{ $bind }}" />
                                @endif
                            </x-field>
                        @endforeach
                    </div>
                </x-card>
            @endforeach

                <x-btn type="submit" size="lg">Guardar configuración</x-btn>
            </form>
        </div>

        <x-card title="Oficios" subtitle="Clasifican proveedores, partidas, fases y tareas">
            <form wire:submit="addTrade" class="mb-4 flex gap-2">
                <x-input wire:model="tradeForm.name" placeholder="Nuevo oficio" class="flex-1" />
                <x-select wire:model="tradeForm.color" class="w-28 text-xs">
                    @foreach (['blue', 'indigo', 'green', 'amber', 'red', 'purple', 'teal', 'pink', 'orange', 'gray'] as $color)
                        <option value="{{ $color }}">{{ $color }}</option>
                    @endforeach
                </x-select>
                <x-btn type="submit" size="sm">+</x-btn>
            </form>
            @error('tradeForm.name') <p class="-mt-3 mb-3 text-xs text-red-600">{{ $message }}</p> @enderror

            <div class="space-y-1.5">
                @foreach ($trades as $trade)
                    <div wire:key="trade-{{ $trade->id }}"
                         class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700">
                        <x-badge :color="$trade->color" :class="$trade->is_active ? '' : 'opacity-40'">{{ $trade->name }}</x-badge>
                        <button wire:click="toggleTrade({{ $trade->id }})"
                                class="text-xs font-medium {{ $trade->is_active ? 'text-slate-500' : 'text-emerald-600' }} hover:underline">
                            {{ $trade->is_active ? 'Desactivar' : 'Activar' }}
                        </button>
                    </div>
                @endforeach
            </div>
        </x-card>
    </div>
</div>
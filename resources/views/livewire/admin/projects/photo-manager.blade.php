<div class="space-y-5">
    <x-card title="Subir fotos de avance">
        <form wire:submit="save" class="grid gap-4 sm:grid-cols-6">
            <x-field label="Fotos" required class="sm:col-span-2">
                <input type="file" wire:model="uploads" multiple accept="image/*"
                       class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-indigo-700">
                @error('uploads.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('uploads') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </x-field>

            <x-field label="Momento" required class="sm:col-span-1">
                <x-select wire:model="stage">
                    @foreach ($stages as $case)<option value="{{ $case->value }}">{{ $case->label() }}</option>@endforeach
                </x-select>
            </x-field>

            <x-field label="Fase" class="sm:col-span-1">
                <x-select wire:model="phaseId">
                    <option value="">General</option>
                    @foreach ($phases as $phase)<option value="{{ $phase->id }}">{{ $phase->name }}</option>@endforeach
                </x-select>
            </x-field>

            <x-field label="Pie de foto" class="sm:col-span-2">
                <x-input wire:model="caption" placeholder="Se aplica a todas las de esta tanda" />
            </x-field>

            <div class="flex items-center gap-4 sm:col-span-6">
                <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                    <input type="checkbox" wire:model="visibleToClient" class="rounded border-slate-300 text-indigo-600">
                    Visibles en el portal del cliente
                </label>

                <x-btn type="submit" size="sm" wire:loading.attr="disabled" wire:target="uploads,save">
                    <span wire:loading.remove wire:target="uploads,save">Subir</span>
                    <span wire:loading wire:target="uploads,save">Procesando…</span>
                </x-btn>

                @if ($uploads)
                    <span class="text-xs text-slate-500">{{ count($uploads) }} foto(s) seleccionadas</span>
                @endif
            </div>
        </form>
    </x-card>

    <div class="flex items-center gap-3">
        <x-select wire:model.live="filterStage" class="w-48 text-xs">
            <option value="">Todas las etapas</option>
            @foreach ($stages as $case)<option value="{{ $case->value }}">{{ $case->label() }}</option>@endforeach
        </x-select>
    </div>

    @forelse ($photos as $month => $group)
        <div>
            <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                {{ $month === 'sin-fecha' ? 'Sin fecha' : \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y') }}
            </h3>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ($group as $photo)
                    <div wire:key="photo-{{ $photo->id }}"
                         class="group relative overflow-hidden rounded-lg border border-slate-200 bg-white dark:border-slate-700">
                        <img src="{{ $photo->thumbUrl() }}" alt="{{ $photo->caption }}"
                             loading="lazy" class="aspect-square w-full object-cover">

                        <div class="absolute inset-x-0 top-0 flex justify-between p-1.5">
                            <x-badge :color="$photo->stage->color()" size="xs">{{ $photo->stage->label() }}</x-badge>
                            @if ($photo->is_cover)<x-badge color="indigo" size="xs">Portada</x-badge>@endif
                        </div>

                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-900/85 to-transparent p-2 opacity-0 transition group-hover:opacity-100">
                            @if ($photo->caption)
                                <p class="truncate text-[11px] text-white">{{ $photo->caption }}</p>
                            @endif
                            <div class="mt-1 flex flex-wrap gap-2 text-[11px] font-medium">
                                <a href="{{ $photo->url() }}" target="_blank" class="text-white hover:underline">Ver</a>
                                <button wire:click="toggleVisibility({{ $photo->id }})"
                                        class="{{ $photo->visible_to_client ? 'text-emerald-300' : 'text-amber-300' }} hover:underline">
                                    {{ $photo->visible_to_client ? 'Visible' : 'Oculta' }}
                                </button>
                                <button wire:click="setCover({{ $photo->id }})" class="text-white hover:underline">Portada</button>
                                <button wire:click="delete({{ $photo->id }})" wire:confirm="¿Eliminar foto?"
                                        class="text-red-300 hover:underline">Borrar</button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <x-empty-state title="Sin fotos todavía"
                       description="Las fotos de avance son lo que más tranquiliza al cliente. Sube unas cuantas cada semana." icon="camera" />
    @endforelse
</div>
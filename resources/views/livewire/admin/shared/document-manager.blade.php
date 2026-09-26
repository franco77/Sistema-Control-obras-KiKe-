<div class="space-y-4">
    {{-- Formulario de subida --}}
    <form wire:submit="save" class="rounded-lg border border-dashed border-slate-300 p-4 dark:border-slate-600">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <x-field label="Fichero" required class="sm:col-span-2">
                <input type="file" wire:model="file"
                       class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-indigo-700 hover:file:bg-indigo-100">
                @error('file') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </x-field>

            <x-field label="Tipo" required>
                <x-select wire:model="category">
                    @foreach ($categoryOptions as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </x-select>
            </x-field>

            <x-field label="Nombre" hint="Opcional">
                <x-input wire:model="name" placeholder="Se usa el del fichero" />
            </x-field>

            <x-field label="Fecha de emisión">
                <x-input type="date" wire:model="issued_on" />
            </x-field>

            <x-field label="Caduca el" hint="Se avisará antes de vencer">
                <x-input type="date" wire:model="expires_on" />
                @error('expires_on') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </x-field>

            @if ($showClientToggle)
                <label class="flex items-end gap-2 pb-2 text-xs text-slate-600 dark:text-slate-300">
                    <input type="checkbox" wire:model="visible_to_client"
                           class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    Visible para el cliente
                </label>
            @endif

            <div class="flex items-end">
                <x-btn type="submit" size="sm" wire:loading.attr="disabled" wire:target="save,file">
                    <span wire:loading.remove wire:target="save,file">Subir documento</span>
                    <span wire:loading wire:target="save,file">Subiendo…</span>
                </x-btn>
            </div>
        </div>
    </form>

    {{-- Listado --}}
    @forelse ($documents as $document)
        <div class="flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 dark:border-slate-700">
            <x-icon name="document" class="h-5 w-5 shrink-0 text-slate-400" />

            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{ $document->name }}</p>
                <p class="truncate text-xs text-slate-500">
                    {{ $document->category->label() }} · {{ $document->human_size }}
                    @if ($document->uploader) · {{ $document->uploader->name }} @endif
                    · {{ $document->created_at->format('d/m/Y') }}
                </p>
            </div>

            @if ($document->expires_on)
                <x-badge :color="$document->isExpired() ? 'red' : ($document->isExpiringSoon() ? 'amber' : 'green')" size="xs">
                    {{ $document->isExpired() ? 'Caducado' : 'Vence' }} {{ $document->expires_on->format('d/m/y') }}
                </x-badge>
            @endif

            @if ($showClientToggle)
                <button wire:click="toggleClientVisibility({{ $document->id }})"
                        class="shrink-0 text-xs font-medium {{ $document->visible_to_client ? 'text-emerald-600' : 'text-slate-400' }} hover:underline">
                    {{ $document->visible_to_client ? 'Visible al cliente' : 'Solo interno' }}
                </button>
            @endif

            <a href="{{ route('admin.documents.download', $document) }}"
               class="shrink-0 text-xs font-medium text-indigo-600 hover:underline">Descargar</a>

            <button wire:click="delete({{ $document->id }})"
                    wire:confirm="¿Eliminar este documento?"
                    class="shrink-0 text-xs font-medium text-red-600 hover:underline">Eliminar</button>
        </div>
    @empty
        <x-empty-state title="Sin documentos" description="Sube contratos, seguros, planos o facturas." icon="document" />
    @endforelse
</div>
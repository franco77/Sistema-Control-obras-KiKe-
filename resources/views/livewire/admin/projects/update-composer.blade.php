<div class="space-y-5">
    <x-card title="Nuevo parte de obra"
            subtitle="Lo que escribas aquí es lo que el cliente lee como «novedades» en su portal.">
        <form wire:submit="save" class="grid gap-4 sm:grid-cols-6">
            <x-field label="Título" class="sm:col-span-4">
                <x-input wire:model="form.title" placeholder="Semana 3 · terminada la instalación de fontanería" />
            </x-field>

            <x-field label="Fase" class="sm:col-span-2">
                <x-select wire:model="form.project_phase_id">
                    <option value="">General</option>
                    @foreach ($phases as $phase)<option value="{{ $phase->id }}">{{ $phase->name }}</option>@endforeach
                </x-select>
            </x-field>

            <x-field label="Contenido" required class="sm:col-span-6" :error="$errors->first('form.body')">
                <x-textarea wire:model="form.body" rows="4"
                            placeholder="Qué se ha hecho, qué viene ahora y si hay algo que el cliente deba decidir." />
            </x-field>

            <div class="flex flex-wrap items-center gap-5 sm:col-span-6">
                <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                    <input type="checkbox" wire:model.live="form.visible_to_client" class="rounded border-slate-300 text-indigo-600">
                    Publicar en el portal del cliente
                </label>

                <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                    <input type="checkbox" wire:model="form.notify_client" @disabled(! $form['visible_to_client'])
                           class="rounded border-slate-300 text-indigo-600 disabled:opacity-40">
                    Avisar por email
                </label>

                <x-btn type="submit" size="sm">Publicar parte</x-btn>
            </div>
        </form>
    </x-card>

    <div class="space-y-3">
        @forelse ($updates as $update)
            <div wire:key="upd-{{ $update->id }}"
                 class="relative rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                            {{ $update->title ?: 'Parte de obra' }}
                        </p>
                        <p class="text-xs text-slate-500">
                            {{ $update->author?->name }} · {{ $update->published_at?->format('d/m/Y H:i') }}
                            @if ($update->phase) · {{ $update->phase->name }} @endif
                            @if ($update->progress_snapshot !== null) · avance {{ $update->progress_snapshot }} % @endif
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        @if ($update->visible_to_client)
                            <x-badge color="green" size="xs">Publicado</x-badge>
                        @else
                            <x-badge size="xs">Interno</x-badge>
                        @endif
                        @if ($update->notify_client)<x-badge color="blue" size="xs">Notificado</x-badge>@endif
                        <button wire:click="delete({{ $update->id }})" wire:confirm="¿Eliminar parte?"
                                class="text-xs text-red-600 hover:underline">Borrar</button>
                    </div>
                </div>

                <p class="mt-2 whitespace-pre-line text-sm text-slate-600 dark:text-slate-300">{{ $update->body }}</p>
            </div>
        @empty
            <x-empty-state title="Sin partes publicados"
                           description="Un parte semanal reduce a la mitad las llamadas del cliente preguntando cómo va." icon="chat" />
        @endforelse
    </div>
</div>
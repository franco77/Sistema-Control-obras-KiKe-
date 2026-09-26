<div class="space-y-4">
    <div class="flex justify-end">
        <x-btn size="sm" icon="plus" wire:click="create">Añadir contacto</x-btn>
    </div>

    @if ($showForm)
        <x-card :title="$editingId ? 'Editar contacto' : 'Nuevo contacto'">
            <form wire:submit="save" class="grid gap-4 sm:grid-cols-4">
                <x-field label="Nombre" required :error="$errors->first('form.name')">
                    <x-input wire:model="form.name" />
                </x-field>
                <x-field label="Cargo" hint="Presidente, administrador…">
                    <x-input wire:model="form.role" />
                </x-field>
                <x-field label="Email" :error="$errors->first('form.email')">
                    <x-input type="email" wire:model="form.email" />
                </x-field>
                <x-field label="Teléfono">
                    <x-input wire:model="form.phone" />
                </x-field>

                <div class="flex items-center gap-6 sm:col-span-4">
                    <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                        <input type="checkbox" wire:model="form.is_primary" class="rounded border-slate-300 text-indigo-600">
                        Contacto principal
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                        <input type="checkbox" wire:model="form.receives_notifications" class="rounded border-slate-300 text-indigo-600">
                        Recibe avisos de obra
                    </label>
                </div>

                <x-field label="Notas" class="sm:col-span-4">
                    <x-textarea wire:model="form.notes" rows="2" />
                </x-field>

                <div class="flex gap-2 sm:col-span-4">
                    <x-btn type="submit">Guardar</x-btn>
                    <x-btn variant="secondary" wire:click="$set('showForm', false)">Cancelar</x-btn>
                </div>
            </form>
        </x-card>
    @endif

    <x-card :padding="false">
        @forelse ($contacts as $contact)
            <div wire:key="contact-{{ $contact->id }}"
                 class="flex flex-wrap items-center gap-3 border-b border-slate-100 px-5 py-3 last:border-0 dark:border-slate-700">
                <div class="min-w-0 flex-1">
                    <p class="flex items-center gap-2 text-sm font-medium text-slate-900 dark:text-slate-100">
                        {{ $contact->name }}
                        @if ($contact->is_primary)<x-badge color="indigo" size="xs">Principal</x-badge>@endif
                        @if ($contact->receives_notifications)<x-badge color="green" size="xs">Recibe avisos</x-badge>@endif
                    </p>
                    <p class="text-xs text-slate-500">
                        {{ $contact->role ?: 'Contacto' }}
                        @if ($contact->email) · {{ $contact->email }} @endif
                        @if ($contact->phone) · {{ $contact->phone }} @endif
                    </p>
                </div>
                <button wire:click="edit({{ $contact->id }})" class="text-xs font-medium text-indigo-600 hover:underline">Editar</button>
                <button wire:click="delete({{ $contact->id }})" wire:confirm="¿Eliminar contacto?"
                        class="text-xs font-medium text-red-600 hover:underline">Eliminar</button>
            </div>
        @empty
            <div class="p-5"><x-empty-state title="Sin contactos adicionales" icon="users" /></div>
        @endforelse
    </x-card>
</div>
<div>
    <x-page-header title="Clientes" :subtitle="$clients->total().' clientes registrados'">
        <x-slot:actions>
            @can('create', App\Models\Client::class)
                <x-btn :href="route('admin.clients.create')" icon="plus">Nuevo cliente</x-btn>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-card :padding="false">
        {{-- Filtros --}}
        <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-4 dark:border-slate-700">
            <div class="min-w-64 flex-1">
                <x-input wire:model.live.debounce.400ms="search"
                         placeholder="Buscar por nombre, NIF, email o teléfono…" />
            </div>

            <x-select wire:model.live="status" class="w-44">
                <option value="">Todos los estados</option>
                @foreach ($statuses as $case)
                    <option value="{{ $case->value }}">{{ $case->label() }}</option>
                @endforeach
            </x-select>

            <x-select wire:model.live="type" class="w-44">
                <option value="">Todos los tipos</option>
                @foreach ($types as $case)
                    <option value="{{ $case->value }}">{{ $case->label() }}</option>
                @endforeach
            </x-select>

            @if ($search || $status || $type)
                <x-btn variant="ghost" size="sm" wire:click="clearFilters">Limpiar</x-btn>
            @endif
        </div>

        {{-- Tabla --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-900/40">
                    <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3">
                            <button wire:click="sortBy('name')" class="hover:text-slate-900">Cliente</button>
                        </th>
                        <th class="px-4 py-3">Contacto</th>
                        <th class="px-4 py-3">
                            <button wire:click="sortBy('city')" class="hover:text-slate-900">Ubicación</button>
                        </th>
                        <th class="px-4 py-3 text-center">Inmuebles</th>
                        <th class="px-4 py-3 text-center">Obras</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse ($clients as $client)
                        <tr wire:key="client-{{ $client->id }}" class="transition hover:bg-slate-50 dark:hover:bg-slate-700/30">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.clients.show', $client) }}"
                                   class="text-sm font-medium text-slate-900 hover:text-indigo-600 dark:text-slate-100">
                                    {{ $client->name }}
                                </a>
                                <p class="text-xs text-slate-500">{{ $client->code }} · {{ $client->type->label() }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                @if ($client->email)<p class="truncate">{{ $client->email }}</p>@endif
                                @if ($client->phone)<p class="text-xs text-slate-500">{{ $client->phone }}</p>@endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                {{ $client->city ?: '—' }}
                                @if ($client->province)<p class="text-xs text-slate-500">{{ $client->province }}</p>@endif
                            </td>
                            <td class="px-4 py-3 text-center text-sm tabular-nums text-slate-600 dark:text-slate-300">{{ $client->properties_count }}</td>
                            <td class="px-4 py-3 text-center text-sm tabular-nums text-slate-600 dark:text-slate-300">{{ $client->projects_count }}</td>
                            <td class="px-4 py-3">
                                <x-badge :color="$client->status->color()" dot>{{ $client->status->label() }}</x-badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.clients.edit', $client) }}"
                                       class="text-xs font-medium text-indigo-600 hover:underline">Editar</a>
                                    @can('delete', $client)
                                        <button wire:click="delete({{ $client->id }})"
                                                wire:confirm="¿Eliminar a {{ $client->name }}?"
                                                class="text-xs font-medium text-red-600 hover:underline">Eliminar</button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-6">
                                <x-empty-state title="Ningún cliente coincide"
                                               description="Prueba a cambiar los filtros o crea un cliente nuevo." icon="users" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($clients->hasPages())
            <div class="border-t border-slate-100 p-4 dark:border-slate-700">{{ $clients->links() }}</div>
        @endif
    </x-card>
</div>
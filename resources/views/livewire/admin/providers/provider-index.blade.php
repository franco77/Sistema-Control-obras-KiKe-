<div>
    <x-page-header title="Proveedores" subtitle="Autónomos, empresas subcontratadas y personal propio">
        <x-slot:actions>
            @can('create', App\Models\Provider::class)
                <x-btn :href="route('admin.providers.create')" icon="plus">Nuevo proveedor</x-btn>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-card :padding="false">
        <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-4 dark:border-slate-700">
            <div class="min-w-64 flex-1">
                <x-input wire:model.live.debounce.400ms="search" placeholder="Buscar por nombre, NIF o contacto…" />
            </div>

            <x-select wire:model.live="trade" class="w-48">
                <option value="">Todos los oficios</option>
                @foreach ($trades as $t)
                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                @endforeach
            </x-select>

            <x-select wire:model.live="status" class="w-44">
                <option value="">Todos los estados</option>
                @foreach ($statuses as $case)
                    <option value="{{ $case->value }}">{{ $case->label() }}</option>
                @endforeach
            </x-select>

            <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                <input type="checkbox" wire:model.live="onlyCompliant" class="rounded border-slate-300 text-indigo-600">
                Solo con documentación en vigor
            </label>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500 dark:bg-slate-900/40">
                    <tr>
                        <th class="px-4 py-3"><button wire:click="sortBy('name')">Proveedor</button></th>
                        <th class="px-4 py-3">Oficios</th>
                        <th class="px-4 py-3">Contacto</th>
                        <th class="px-4 py-3">Documentación</th>
                        <th class="px-4 py-3 text-center"><button wire:click="sortBy('rating')">Valoración</button></th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse ($providers as $provider)
                        <tr wire:key="prov-{{ $provider->id }}" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.providers.show', $provider) }}"
                                   class="text-sm font-medium text-slate-900 hover:text-indigo-600 dark:text-slate-100">{{ $provider->name }}</a>
                                <p class="text-xs text-slate-500">{{ $provider->code }} · {{ $provider->type->label() }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($provider->trades->take(3) as $trade)
                                        <x-badge :color="$trade->color" size="xs">{{ $trade->name }}</x-badge>
                                    @endforeach
                                    @if ($provider->trades->count() > 3)
                                        <x-badge size="xs">+{{ $provider->trades->count() - 3 }}</x-badge>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                @if ($provider->phone)<p>{{ $provider->phone }}</p>@endif
                                @if ($provider->email)<p class="truncate text-xs text-slate-500">{{ $provider->email }}</p>@endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($provider->hasValidDocumentation())
                                    <x-badge color="green" size="xs">En regla</x-badge>
                                @else
                                    <x-badge color="red" size="xs">
                                        Falta {{ count($provider->missing_documents) }}
                                    </x-badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-sm tabular-nums">
                                @if ($provider->rating)
                                    <span class="font-medium text-slate-900 dark:text-slate-100">{{ number_format((float) $provider->rating, 1) }}</span>
                                    <span class="text-xs text-slate-400">/5</span>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <x-badge :color="$provider->status->color()" dot>{{ $provider->status->label() }}</x-badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.providers.edit', $provider) }}"
                                   class="text-xs font-medium text-indigo-600 hover:underline">Editar</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-6"><x-empty-state title="Sin proveedores" icon="wrench" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($providers->hasPages())
            <div class="border-t border-slate-100 p-4 dark:border-slate-700">{{ $providers->links() }}</div>
        @endif
    </x-card>
</div>
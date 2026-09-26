<div>
    <x-page-header title="Catálogo de partidas"
                   subtitle="Tu banco de precios: coste interno y PVP de referencia">
        <x-slot:actions>
            <x-btn icon="plus" wire:click="create">Nueva partida</x-btn>
        </x-slot:actions>
    </x-page-header>

    @if ($showForm)
        <x-card :title="$editingId ? 'Editar partida' : 'Nueva partida'" class="mb-6">
            <form wire:submit="save" class="grid gap-4 sm:grid-cols-6">
                <x-field label="Capítulo" class="sm:col-span-2">
                    <x-select wire:model="form.catalog_category_id">
                        <option value="">Sin capítulo</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="Oficio" class="sm:col-span-2">
                    <x-select wire:model="form.trade_id">
                        <option value="">Sin oficio</option>
                        @foreach ($trades as $trade)
                            <option value="{{ $trade->id }}">{{ $trade->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="Código" class="sm:col-span-2" :error="$errors->first('form.code')">
                    <x-input wire:model="form.code" placeholder="ALB-001" />
                </x-field>

                <x-field label="Descripción corta" required class="sm:col-span-4" :error="$errors->first('form.name')">
                    <x-input wire:model="form.name" placeholder="Demolición de tabique de ladrillo hueco" />
                </x-field>

                <x-field label="Unidad" required class="sm:col-span-2">
                    <x-select wire:model="form.unit">
                        @foreach ($units as $unit)
                            <option value="{{ $unit->value }}">{{ $unit->label() }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="Texto largo" class="sm:col-span-6"
                         hint="Aparece bajo la partida en el PDF del presupuesto">
                    <x-textarea wire:model="form.description" rows="2" />
                </x-field>

                <x-field label="Coste (€)" required class="sm:col-span-1">
                    <x-input type="number" step="0.0001" wire:model.live="form.unit_cost" />
                </x-field>

                <x-field label="PVP (€)" required class="sm:col-span-1">
                    <x-input type="number" step="0.0001" wire:model.live="form.unit_price" />
                </x-field>

                <div class="sm:col-span-1">
                    <p class="text-xs font-medium text-slate-700 dark:text-slate-300">Margen</p>
                    <p @class([
                        'mt-2 text-lg font-semibold tabular-nums',
                        'text-emerald-600' => $this->margin >= 25,
                        'text-amber-600' => $this->margin < 25 && $this->margin >= 10,
                        'text-red-600' => $this->margin < 10,
                    ])>{{ number_format($this->margin, 1) }} %</p>
                </div>

                <x-field label="Cantidad por defecto" class="sm:col-span-1">
                    <x-input type="number" step="0.001" wire:model="form.default_quantity" />
                </x-field>

                <x-field label="Rendimiento (ud/día)" class="sm:col-span-2"
                         hint="Se usa para estimar horas al crear la obra">
                    <x-input type="number" step="0.001" wire:model="form.yield_per_day" />
                </x-field>

                <div class="flex gap-2 sm:col-span-6">
                    <x-btn type="submit">Guardar partida</x-btn>
                    <x-btn variant="secondary" wire:click="$set('showForm', false)">Cancelar</x-btn>
                </div>
            </form>
        </x-card>
    @endif

    <x-card :padding="false">
        <div class="flex flex-wrap gap-3 border-b border-slate-100 p-4 dark:border-slate-700">
            <div class="min-w-64 flex-1">
                <x-input wire:model.live.debounce.400ms="search" placeholder="Buscar partida…" />
            </div>
            <x-select wire:model.live="categoryId" class="w-56">
                <option value="">Todos los capítulos</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </x-select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500 dark:bg-slate-900/40">
                    <tr>
                        <th class="px-4 py-3">Partida</th>
                        <th class="px-4 py-3">Capítulo</th>
                        <th class="px-4 py-3 text-center">Ud.</th>
                        <th class="px-4 py-3 text-right">Coste</th>
                        <th class="px-4 py-3 text-right">PVP</th>
                        <th class="px-4 py-3 text-right">Margen</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse ($items as $item)
                        <tr wire:key="item-{{ $item->id }}" @class([
                            'hover:bg-slate-50 dark:hover:bg-slate-700/30',
                            'opacity-50' => ! $item->is_active,
                        ])>
                            <td class="px-4 py-3">
                                <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $item->name }}</p>
                                <p class="text-xs text-slate-500">
                                    @if ($item->code) {{ $item->code }} · @endif
                                    {{ $item->trade?->name ?? 'Sin oficio' }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $item->category?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-center text-sm text-slate-500">{{ $item->unit->label() }}</td>
                            <td class="px-4 py-3 text-right text-sm tabular-nums text-slate-600 dark:text-slate-300">{{ money($item->unit_cost) }}</td>
                            <td class="px-4 py-3 text-right text-sm font-medium tabular-nums">{{ money($item->unit_price) }}</td>
                            <td class="px-4 py-3 text-right">
                                <x-badge size="xs" :color="$item->margin_percent >= 25 ? 'green' : ($item->margin_percent >= 10 ? 'amber' : 'red')">
                                    {{ number_format($item->margin_percent, 1) }} %
                                </x-badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <button wire:click="edit({{ $item->id }})" class="text-xs font-medium text-indigo-600 hover:underline">Editar</button>
                                    <button wire:click="toggleActive({{ $item->id }})" class="text-xs font-medium text-slate-500 hover:underline">
                                        {{ $item->is_active ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-6"><x-empty-state title="El catálogo está vacío"
                            description="Crea partidas tipo para montar presupuestos en minutos." icon="chart" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($items->hasPages())
            <div class="border-t border-slate-100 p-4 dark:border-slate-700">{{ $items->links() }}</div>
        @endif
    </x-card>
</div>
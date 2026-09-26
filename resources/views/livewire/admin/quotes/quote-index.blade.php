<div>
    <x-page-header title="Presupuestos" subtitle="Constructor de partidas, versiones y aprobación online">
        <x-slot:actions>
            @can('create', App\Models\Quote::class)
                <x-btn :href="route('admin.quotes.create')" icon="plus">Nuevo presupuesto</x-btn>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-stat label="Pendiente de respuesta" :value="money($summary['pending'])" color="indigo" />
        <x-stat label="Aprobado este mes" :value="money($summary['approved_month'])" color="green" />
        <x-stat label="Tasa de conversión" :value="percent($summary['conversion'])" hint="Últimos 6 meses" />
    </div>

    <x-card :padding="false">
        <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-4 dark:border-slate-700">
            <div class="min-w-64 flex-1">
                <x-input wire:model.live.debounce.400ms="search" placeholder="Buscar por número, título o cliente…" />
            </div>
            <x-select wire:model.live="status" class="w-52">
                <option value="">Todos los estados</option>
                @foreach ($statuses as $case)
                    <option value="{{ $case->value }}">{{ $case->label() }}</option>
                @endforeach
            </x-select>
            <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                <input type="checkbox" wire:model.live="showAllVersions" class="rounded border-slate-300 text-indigo-600">
                Mostrar versiones antiguas
            </label>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500 dark:bg-slate-900/40">
                    <tr>
                        <th class="px-4 py-3"><button wire:click="sortBy('number')">Referencia</button></th>
                        <th class="px-4 py-3">Cliente / obra</th>
                        <th class="px-4 py-3"><button wire:click="sortBy('issue_date')">Emitido</button></th>
                        <th class="px-4 py-3"><button wire:click="sortBy('valid_until')">Validez</button></th>
                        <th class="px-4 py-3 text-right"><button wire:click="sortBy('total')">Total</button></th>
                        <th class="px-4 py-3 text-right">Margen</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse ($quotes as $quote)
                        <tr wire:key="quote-{{ $quote->id }}" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.quotes.show', $quote) }}"
                                   class="text-sm font-medium text-slate-900 hover:text-indigo-600 dark:text-slate-100">
                                    {{ $quote->reference }}
                                </a>
                                <p class="truncate text-xs text-slate-500">{{ $quote->title }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                {{ $quote->client->name }}
                                @if ($quote->project)
                                    <p class="text-xs text-emerald-600">→ {{ $quote->project->code }}</p>
                                @elseif ($quote->property)
                                    <p class="truncate text-xs text-slate-500">{{ $quote->property->alias }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-500">{{ $quote->issue_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if ($quote->valid_until)
                                    <span @class([
                                        'text-red-600' => $quote->isExpired(),
                                        'text-slate-500' => ! $quote->isExpired(),
                                    ])>{{ $quote->valid_until->format('d/m/Y') }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-semibold tabular-nums">{{ money($quote->total) }}</td>
                            <td class="px-4 py-3 text-right">
                                @can('manageFinancials', App\Models\Project::class)
                                    <x-badge size="xs" :color="$quote->margin_percent >= 25 ? 'green' : ($quote->margin_percent >= 12 ? 'amber' : 'red')">
                                        {{ number_format((float) $quote->margin_percent, 1) }} %
                                    </x-badge>
                                @endcan
                            </td>
                            <td class="px-4 py-3">
                                <x-badge :color="$quote->status->color()" dot>{{ $quote->status->label() }}</x-badge>
                                @if ($quote->views_count > 0 && $quote->status->value === 'viewed')
                                    <p class="mt-0.5 text-[11px] text-slate-400">{{ $quote->views_count }} visitas</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.quotes.pdf', $quote) }}" target="_blank"
                                       class="text-xs font-medium text-slate-500 hover:underline">PDF</a>
                                    @can('update', $quote)
                                        <a href="{{ route('admin.quotes.edit', $quote) }}"
                                           class="text-xs font-medium text-indigo-600 hover:underline">Editar</a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="p-6"><x-empty-state title="Sin presupuestos"
                            description="Crea el primero desde la ficha de un cliente o con el botón de arriba." icon="document" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($quotes->hasPages())
            <div class="border-t border-slate-100 p-4 dark:border-slate-700">{{ $quotes->links() }}</div>
        @endif
    </x-card>
</div>
<div>
    <x-page-header title="Documentos" subtitle="Control transversal de caducidades y archivos" />

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-stat label="Caducados" :value="$counters['expired']" :color="$counters['expired'] ? 'red' : 'slate'" />
        <x-stat label="Caducan en 30 días" :value="$counters['soon']" :color="$counters['soon'] ? 'amber' : 'slate'" />
        <x-stat label="Total archivados" :value="$counters['total']" />
    </div>

    <x-card :padding="false">
        <div class="flex flex-wrap gap-3 border-b border-slate-100 p-4 dark:border-slate-700">
            <div class="min-w-64 flex-1">
                <x-input wire:model.live.debounce.400ms="search" placeholder="Buscar documento…" />
            </div>
            <x-select wire:model.live="category" class="w-52">
                <option value="">Todos los tipos</option>
                @foreach ($categories as $case)<option value="{{ $case->value }}">{{ $case->label() }}</option>@endforeach
            </x-select>
            <x-select wire:model.live="expiry" class="w-48">
                <option value="all">Todos</option>
                <option value="expired">Caducados</option>
                <option value="soon">Caducan pronto</option>
            </x-select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500 dark:bg-slate-900/40">
                    <tr>
                        <th class="px-4 py-3">Documento</th>
                        <th class="px-4 py-3">Pertenece a</th>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3">Caducidad</th>
                        <th class="px-4 py-3">Subido</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse ($documents as $document)
                        <tr wire:key="doc-{{ $document->id }}" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                            <td class="px-4 py-3">
                                <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $document->name }}</p>
                                <p class="text-xs text-slate-500">{{ $document->human_size }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                {{ $document->documentable?->name ?? $document->documentable?->alias ?? '—' }}
                                <p class="text-xs text-slate-400">{{ class_basename($document->documentable_type) }}</p>
                            </td>
                            <td class="px-4 py-3"><x-badge size="xs">{{ $document->category->label() }}</x-badge></td>
                            <td class="px-4 py-3">
                                @if ($document->expires_on)
                                    <x-badge size="xs" :color="$document->isExpired() ? 'red' : ($document->isExpiringSoon() ? 'amber' : 'green')">
                                        {{ $document->expires_on->format('d/m/Y') }}
                                    </x-badge>
                                @else
                                    <span class="text-xs text-slate-400">sin caducidad</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">
                                {{ $document->created_at->format('d/m/Y') }}
                                @if ($document->uploader)<p>{{ $document->uploader->name }}</p>@endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.documents.download', $document) }}"
                                   class="text-xs font-medium text-indigo-600 hover:underline">Descargar</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-6"><x-empty-state title="Sin documentos" icon="document" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($documents->hasPages())
            <div class="border-t border-slate-100 p-4 dark:border-slate-700">{{ $documents->links() }}</div>
        @endif
    </x-card>
</div>
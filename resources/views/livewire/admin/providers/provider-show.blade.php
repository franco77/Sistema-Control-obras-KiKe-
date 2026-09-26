<div>
    <x-page-header :title="$provider->name"
                   :subtitle="$provider->code.' · '.$provider->type->label()"
                   :back="route('admin.providers.index')">
        <x-slot:actions>
            <x-badge :color="$provider->status->color()" size="md" dot>{{ $provider->status->label() }}</x-badge>
            <x-btn variant="secondary" wire:click="generatePortalLink">Enlace de portal</x-btn>
            <x-btn :href="route('admin.providers.edit', $provider)">Editar</x-btn>
        </x-slot:actions>
    </x-page-header>

    @if ($portalLink)
        <div class="mb-6 rounded-lg border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-500/30 dark:bg-indigo-500/10">
            <p class="text-xs font-medium text-indigo-900 dark:text-indigo-200">
                Enlace de acceso para {{ $provider->name }} (solo se muestra una vez):
            </p>
            <div class="mt-2 flex items-center gap-2">
                <input type="text" readonly value="{{ $portalLink }}"
                       class="w-full rounded-lg border-indigo-200 bg-white px-3 py-1.5 font-mono text-xs dark:bg-slate-900"
                       x-ref="link" onclick="this.select()">
                <x-btn size="sm" x-on:click="navigator.clipboard.writeText($refs.link.value)">Copiar</x-btn>
            </div>
        </div>
    @endif

    @unless ($provider->hasValidDocumentation())
        <div class="mb-6 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-500/30 dark:bg-red-500/10">
            <x-icon name="warning" class="h-5 w-5 shrink-0 text-red-500" />
            <div>
                <p class="text-sm font-medium text-red-800 dark:text-red-300">Documentación obligatoria incompleta</p>
                <p class="mt-0.5 text-xs text-red-700 dark:text-red-400">
                    Falta o está caducado:
                    {{ collect($provider->missing_documents)
                        ->map(fn ($c) => App\Enums\DocumentCategory::from($c)->label())->implode(', ') }}.
                    No se le pueden asignar tareas hasta regularizarlo.
                </p>
            </div>
        </div>
    @endunless

    <div class="grid gap-4 sm:grid-cols-4">
        <x-stat label="Trabajos realizados" :value="$provider->tasks()->count()" />
        <x-stat label="Valoración media"
                :value="$provider->rating ? number_format((float) $provider->rating, 1).' / 5' : '—'"
                :hint="$reviews->count().' valoraciones'" color="indigo" />
        <x-stat label="Obras activas" :value="$provider->projects()->count()" />
        <x-stat label="Tarifa" :value="$provider->default_hourly_rate ? money($provider->default_hourly_rate).'/h' : '—'" />
    </div>

    <div class="mt-6 border-b border-slate-200 dark:border-slate-700">
        <nav class="-mb-px flex gap-1 overflow-x-auto">
            @foreach ([
                'resumen' => 'Resumen',
                'documentacion' => 'Documentación legal',
                'disponibilidad' => 'Disponibilidad',
                'trabajos' => 'Histórico de trabajos',
                'valoraciones' => 'Valoraciones',
            ] as $key => $label)
                <button wire:click="setTab('{{ $key }}')"
                        @class([
                            'whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-medium transition',
                            'border-indigo-600 text-indigo-600' => $tab === $key,
                            'border-transparent text-slate-500 hover:text-slate-700' => $tab !== $key,
                        ])>{{ $label }}</button>
            @endforeach
        </nav>
    </div>

    <div class="mt-6">
        @if ($tab === 'resumen')
            <div class="grid gap-6 lg:grid-cols-2">
                <x-card title="Datos">
                    <dl class="grid grid-cols-3 gap-y-3 text-sm">
                        @foreach ([
                            'Razón social' => $provider->legal_name,
                            'NIF / CIF' => $provider->tax_id,
                            'Email' => $provider->email,
                            'Teléfono' => $provider->phone,
                            'Población' => trim($provider->city.' '.$provider->province),
                            'IBAN' => $provider->iban,
                            'Retención IRPF' => $provider->irpf_rate ? $provider->irpf_rate.' %' : null,
                            'Radio' => $provider->radius_km ? $provider->radius_km.' km' : null,
                        ] as $label => $value)
                            <dt class="text-slate-500">{{ $label }}</dt>
                            <dd class="col-span-2 text-slate-900 dark:text-slate-100">{{ $value ?: '—' }}</dd>
                        @endforeach
                    </dl>
                </x-card>

                <x-card title="Oficios">
                    <div class="space-y-2">
                        @forelse ($provider->trades as $trade)
                            <div class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700">
                                <div class="flex items-center gap-2">
                                    <x-badge :color="$trade->color">{{ $trade->name }}</x-badge>
                                    @if ($trade->pivot->is_primary)
                                        <span class="text-xs text-slate-500">principal</span>
                                    @endif
                                </div>
                                <div class="text-xs text-slate-500">
                                    @if ($trade->pivot->hourly_rate) {{ money($trade->pivot->hourly_rate) }}/h @endif
                                    @if ($trade->pivot->experience_years) · {{ $trade->pivot->experience_years }} años @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Sin oficios asignados.</p>
                        @endforelse
                    </div>

                    @if ($provider->notes)
                        <div class="mt-4 border-t border-slate-100 pt-3 dark:border-slate-700">
                            <p class="text-xs font-medium text-slate-500">Notas internas</p>
                            <p class="mt-1 whitespace-pre-line text-sm text-slate-600 dark:text-slate-300">{{ $provider->notes }}</p>
                        </div>
                    @endif
                </x-card>
            </div>

        @elseif ($tab === 'documentacion')
            <x-card title="Documentación legal"
                    subtitle="Seguro de responsabilidad civil, PRL y alta de autónomo son obligatorios.">
                @livewire('admin.shared.document-manager', [
                    'model' => $provider,
                    'categories' => $documentCategories,
                    'showClientToggle' => false,
                ], key('docs-prov-'.$provider->id))
            </x-card>

        @elseif ($tab === 'disponibilidad')
            <div class="grid gap-6 lg:grid-cols-3">
                <x-card title="Registrar periodo" class="lg:col-span-1">
                    <form wire:submit="addAvailability" class="space-y-3">
                        <x-field label="Tipo" required>
                            <x-select wire:model="availability.type">
                                @foreach ($availabilityTypes as $case)
                                    <option value="{{ $case->value }}">{{ $case->label() }}</option>
                                @endforeach
                            </x-select>
                        </x-field>
                        <x-field label="Desde" required :error="$errors->first('availability.starts_on')">
                            <x-input type="date" wire:model="availability.starts_on" />
                        </x-field>
                        <x-field label="Hasta" required :error="$errors->first('availability.ends_on')">
                            <x-input type="date" wire:model="availability.ends_on" />
                        </x-field>
                        <x-field label="Nota">
                            <x-input wire:model="availability.note" />
                        </x-field>
                        <x-btn type="submit" class="w-full">Guardar</x-btn>
                    </form>
                </x-card>

                <x-card title="Calendario del proveedor" class="lg:col-span-2" :padding="false">
                    @forelse ($availabilities as $slot)
                        <div wire:key="av-{{ $slot->id }}"
                             class="flex items-center gap-3 border-b border-slate-100 px-5 py-3 last:border-0 dark:border-slate-700">
                            <x-badge :color="$slot->type->color()">{{ $slot->type->label() }}</x-badge>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-slate-900 dark:text-slate-100">
                                    {{ $slot->starts_on->format('d/m/Y') }} — {{ $slot->ends_on->format('d/m/Y') }}
                                    <span class="text-xs text-slate-400">({{ $slot->starts_on->diffInDays($slot->ends_on) + 1 }} días)</span>
                                </p>
                                @if ($slot->note)<p class="text-xs text-slate-500">{{ $slot->note }}</p>@endif
                            </div>
                            @unless ($slot->project_id)
                                <button wire:click="removeAvailability({{ $slot->id }})"
                                        class="text-xs font-medium text-red-600 hover:underline">Quitar</button>
                            @else
                                <span class="text-xs text-slate-400">automático</span>
                            @endunless
                        </div>
                    @empty
                        <div class="p-5"><x-empty-state title="Sin periodos registrados"
                                description="Registra vacaciones o bajas para que no aparezca como disponible." icon="calendar" /></div>
                    @endforelse
                </x-card>
            </div>

        @elseif ($tab === 'trabajos')
            <x-card :padding="false">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500 dark:bg-slate-900/40">
                        <tr>
                            <th class="px-4 py-3">Obra</th>
                            <th class="px-4 py-3">Tarea</th>
                            <th class="px-4 py-3">Fechas</th>
                            <th class="px-4 py-3 text-right">Coste real</th>
                            <th class="px-4 py-3">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @forelse ($tasks as $task)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.projects.show', $task->project) }}"
                                       class="text-sm font-medium text-indigo-600 hover:underline">{{ $task->project->code }}</a>
                                    <p class="text-xs text-slate-500">{{ $task->phase?->name }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">{{ $task->name }}</td>
                                <td class="px-4 py-3 text-xs text-slate-500">
                                    {{ $task->planned_start?->format('d/m/y') ?? '—' }} → {{ $task->planned_end?->format('d/m/y') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm tabular-nums">{{ money($task->cost_real) }}</td>
                                <td class="px-4 py-3"><x-badge :color="$task->status->color()" size="xs">{{ $task->status->label() }}</x-badge></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-6"><x-empty-state title="Sin trabajos registrados" icon="wrench" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if ($tasks?->hasPages())
                    <div class="border-t border-slate-100 p-4 dark:border-slate-700">{{ $tasks->links() }}</div>
                @endif
            </x-card>

        @elseif ($tab === 'valoraciones')
            <div class="grid gap-4 sm:grid-cols-2">
                @forelse ($reviews as $review)
                    <x-card>
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-900 dark:text-slate-100">
                                    {{ $review->project?->code ?? 'Valoración general' }}
                                </p>
                                <p class="text-xs text-slate-500">{{ $review->user?->name }} · {{ $review->created_at->format('d/m/Y') }}</p>
                            </div>
                            <p class="text-lg font-semibold tabular-nums text-indigo-600">{{ number_format((float) $review->score, 1) }}</p>
                        </div>
                        <dl class="mt-3 grid grid-cols-4 gap-2 text-center text-xs">
                            @foreach (['Calidad' => $review->quality, 'Puntualidad' => $review->punctuality, 'Limpieza' => $review->tidiness, 'Trato' => $review->communication] as $label => $value)
                                <div class="rounded-lg bg-slate-50 py-1.5 dark:bg-slate-900/40">
                                    <dt class="text-slate-500">{{ $label }}</dt>
                                    <dd class="font-medium text-slate-900 dark:text-slate-100">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                        @if ($review->comment)
                            <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">{{ $review->comment }}</p>
                        @endif
                    </x-card>
                @empty
                    <div class="sm:col-span-2"><x-empty-state title="Sin valoraciones"
                        description="Se registran al cerrar cada obra." icon="check" /></div>
                @endforelse
            </div>
        @endif
    </div>
</div>
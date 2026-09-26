<div class="space-y-6" x-data="{ lightbox: null }">
    <header>
        <h1 class="text-2xl font-semibold text-stone-900">Fotos de la obra</h1>
        <p class="mt-1 text-sm text-stone-600">Todo lo que vamos documentando, ordenado por fecha.</p>
    </header>

    <div class="flex flex-wrap gap-2">
        <button wire:click="$set('stage', '')"
                @class([
                    'rounded-full px-4 py-1.5 text-sm font-medium transition',
                    'bg-stone-900 text-white' => $stage === '',
                    'border border-stone-200 bg-white text-stone-600 hover:bg-stone-50' => $stage !== '',
                ])>Todas</button>

        @foreach ($stages as $case)
            <button wire:click="$set('stage', '{{ $case->value }}')"
                    @class([
                        'rounded-full px-4 py-1.5 text-sm font-medium transition',
                        'bg-stone-900 text-white' => $stage === $case->value,
                        'border border-stone-200 bg-white text-stone-600 hover:bg-stone-50' => $stage !== $case->value,
                    ])>{{ $case->label() }}</button>
        @endforeach
    </div>

    @forelse ($photos as $month => $group)
        <section>
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-stone-400">
                {{ $month === 'otros' ? 'Otras' : \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y') }}
            </h2>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($group as $photo)
                    <button @click="lightbox = '{{ route('portal.photos.show', $photo) }}'"
                            class="group relative overflow-hidden rounded-xl bg-stone-100">
                        <img src="{{ route('portal.photos.show', ['photo' => $photo, 'thumb' => 1]) }}"
                             alt="{{ $photo->caption }}" loading="lazy"
                             class="aspect-square w-full object-cover transition duration-300 group-hover:scale-105">

                        @if ($photo->caption)
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-stone-900/80 to-transparent p-2.5 text-left">
                                <p class="truncate text-xs text-white">{{ $photo->caption }}</p>
                            </div>
                        @endif
                    </button>
                @endforeach
            </div>
        </section>
    @empty
        <div class="rounded-2xl border border-dashed border-stone-300 px-6 py-16 text-center">
            <p class="text-sm font-medium text-stone-900">Todavía no hay fotos publicadas</p>
            <p class="mt-1 text-sm text-stone-500">En cuanto empecemos a trabajar, iremos subiendo el avance aquí.</p>
        </div>
    @endforelse

    {{-- Visor --}}
    <div x-show="lightbox" x-transition.opacity style="display:none"
         @click="lightbox = null" @keydown.escape.window="lightbox = null"
         class="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/90 p-4">
        <img :src="lightbox" alt="" class="max-h-full max-w-full rounded-lg object-contain">
        <button @click="lightbox = null" class="absolute right-4 top-4 text-3xl text-white/80 hover:text-white">&times;</button>
    </div>
</div>
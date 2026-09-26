@props([
    'title' => null,
    'subtitle' => null,
    'padding' => true,
])

<section {{ $attributes->class([
    'rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800',
]) }}>
    @if ($title || isset($actions))
        <header class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 dark:border-slate-700">
            <div>
                @if ($title)
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $title }}</h2>
                @endif
                @if ($subtitle)
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div class="{{ $padding ? 'p-5' : '' }}">
        {{ $slot }}
    </div>
</section>
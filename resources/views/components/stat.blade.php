@props([
    'label',
    'value',
    'hint' => null,
    'trend' => null,      // 'up' | 'down' | null
    'color' => 'slate',
    'href' => null,
])

@php
    $accent = [
        'slate' => 'text-slate-900 dark:text-slate-100',
        'indigo' => 'text-indigo-600 dark:text-indigo-400',
        'green' => 'text-emerald-600 dark:text-emerald-400',
        'amber' => 'text-amber-600 dark:text-amber-400',
        'red' => 'text-red-600 dark:text-red-400',
    ][$color] ?? 'text-slate-900 dark:text-slate-100';

    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class([
        'block rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800',
        'transition hover:border-indigo-300 hover:shadow' => (bool) $href,
    ]) }}>
    <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $label }}</p>
    <p class="mt-1.5 text-2xl font-semibold tabular-nums {{ $accent }}">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 flex items-center gap-1 text-xs text-slate-500 dark:text-slate-400">
            @if ($trend === 'up') <span class="text-emerald-500">&uarr;</span>
            @elseif ($trend === 'down') <span class="text-red-500">&darr;</span>
            @endif
            {{ $hint }}
        </p>
    @endif
</{{ $tag }}>
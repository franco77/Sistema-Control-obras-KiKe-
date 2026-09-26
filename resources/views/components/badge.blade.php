@props([
    'color' => 'gray',
    'size' => 'sm',
    'dot' => false,
])

@php
    // Las clases se escriben completas (nunca interpoladas) para que el
    // compilador de Tailwind pueda detectarlas.
    $palette = [
        'gray'   => 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-700/40 dark:text-slate-300 dark:ring-slate-600',
        'blue'   => 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/30',
        'indigo' => 'bg-indigo-50 text-indigo-700 ring-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-300 dark:ring-indigo-500/30',
        'green'  => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/30',
        'amber'  => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30',
        'orange' => 'bg-orange-50 text-orange-700 ring-orange-200 dark:bg-orange-500/10 dark:text-orange-300 dark:ring-orange-500/30',
        'red'    => 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/30',
        'purple' => 'bg-purple-50 text-purple-700 ring-purple-200 dark:bg-purple-500/10 dark:text-purple-300 dark:ring-purple-500/30',
        'teal'   => 'bg-teal-50 text-teal-700 ring-teal-200 dark:bg-teal-500/10 dark:text-teal-300 dark:ring-teal-500/30',
        'pink'   => 'bg-pink-50 text-pink-700 ring-pink-200 dark:bg-pink-500/10 dark:text-pink-300 dark:ring-pink-500/30',
    ];

    $dots = [
        'gray' => 'bg-slate-400', 'blue' => 'bg-blue-500', 'indigo' => 'bg-indigo-500',
        'green' => 'bg-emerald-500', 'amber' => 'bg-amber-500', 'orange' => 'bg-orange-500',
        'red' => 'bg-red-500', 'purple' => 'bg-purple-500', 'teal' => 'bg-teal-500', 'pink' => 'bg-pink-500',
    ];

    $sizes = ['xs' => 'px-1.5 py-0.5 text-[11px]', 'sm' => 'px-2 py-0.5 text-xs', 'md' => 'px-2.5 py-1 text-sm'];
@endphp

<span {{ $attributes->class([
    'inline-flex items-center gap-1.5 rounded-full font-medium ring-1 ring-inset whitespace-nowrap',
    $palette[$color] ?? $palette['gray'],
    $sizes[$size] ?? $sizes['sm'],
]) }}>
    @if ($dot)
        <span class="h-1.5 w-1.5 rounded-full {{ $dots[$color] ?? $dots['gray'] }}"></span>
    @endif
    {{ $slot }}
</span>
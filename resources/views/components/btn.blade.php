@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'icon' => null,
])

@php
    $variants = [
        'primary' => 'bg-indigo-600 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline-indigo-600',
        'secondary' => 'bg-white text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-600 dark:hover:bg-slate-700',
        'danger' => 'bg-red-600 text-white shadow-sm hover:bg-red-500',
        'success' => 'bg-emerald-600 text-white shadow-sm hover:bg-emerald-500',
        'ghost' => 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700',
    ];

    $sizes = [
        'xs' => 'px-2 py-1 text-xs gap-1',
        'sm' => 'px-2.5 py-1.5 text-xs gap-1.5',
        'md' => 'px-3 py-2 text-sm gap-2',
        'lg' => 'px-4 py-2.5 text-sm gap-2',
    ];

    $classes = implode(' ', [
        'inline-flex items-center justify-center rounded-lg font-medium transition',
        'disabled:cursor-not-allowed disabled:opacity-50',
        $variants[$variant] ?? $variants['primary'],
        $sizes[$size] ?? $sizes['md'],
    ]);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon)<x-icon :name="$icon" class="h-4 w-4" />@endif
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => 'button'])->class($classes) }}>
        @if ($icon)<x-icon :name="$icon" class="h-4 w-4" />@endif
        {{ $slot }}
    </button>
@endif
@props([
    'value' => 0,
    'label' => null,
    'size' => 'md',
])

@php
    $value = max(0, min(100, (int) $value));
    $bar = match (true) {
        $value >= 100 => 'bg-emerald-500',
        $value >= 60 => 'bg-indigo-500',
        $value >= 25 => 'bg-blue-500',
        default => 'bg-slate-400',
    };
    $height = ['sm' => 'h-1.5', 'md' => 'h-2', 'lg' => 'h-3'][$size] ?? 'h-2';
@endphp

<div {{ $attributes->class('w-full') }}>
    @if ($label !== null)
        <div class="mb-1 flex items-center justify-between text-xs text-slate-600 dark:text-slate-400">
            <span>{{ $label }}</span>
            <span class="font-medium tabular-nums">{{ $value }} %</span>
        </div>
    @endif
    <div class="w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700 {{ $height }}">
        <div class="{{ $height }} {{ $bar }} rounded-full transition-all duration-500" style="width: {{ $value }}%"></div>
    </div>
</div>
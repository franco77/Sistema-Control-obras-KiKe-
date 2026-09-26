@props(['value' => 0, 'size' => 128, 'label' => 'completado'])

@php
    $value = max(0, min(100, (int) $value));
    $radius = 54;
    $circumference = 2 * M_PI * $radius;
    $offset = $circumference * (1 - $value / 100);
@endphp

<div {{ $attributes->class('relative inline-flex items-center justify-center') }}
     style="width: {{ $size }}px; height: {{ $size }}px;">
    <svg class="h-full w-full -rotate-90" viewBox="0 0 120 120">
        <circle cx="60" cy="60" r="{{ $radius }}" fill="none" stroke="currentColor" stroke-width="10" class="text-stone-200" />
        <circle cx="60" cy="60" r="{{ $radius }}" fill="none" stroke="currentColor" stroke-width="10"
                stroke-linecap="round"
                stroke-dasharray="{{ $circumference }}"
                stroke-dashoffset="{{ $offset }}"
                class="{{ $value >= 100 ? 'text-emerald-500' : 'text-stone-900' }} transition-all duration-700" />
    </svg>
    <div class="absolute text-center">
        <p class="text-2xl font-semibold tabular-nums text-stone-900">{{ $value }}<span class="text-base">%</span></p>
        <p class="text-[11px] uppercase tracking-wide text-stone-400">{{ $label }}</p>
    </div>
</div>
@props([
    'label' => null,
    'for' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
])

<div {{ $attributes->class('space-y-1') }}>
    @if ($label)
        <label @if ($for) for="{{ $for }}" @endif
               class="block text-xs font-medium text-slate-700 dark:text-slate-300">
            {{ $label }}
            @if ($required)<span class="text-red-500">*</span>@endif
        </label>
    @endif

    {{ $slot }}

    @if ($hint && ! $error)
        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $hint }}</p>
    @endif

    @if ($error)
        <p class="text-xs text-red-600 dark:text-red-400">{{ $error }}</p>
    @endif
</div>
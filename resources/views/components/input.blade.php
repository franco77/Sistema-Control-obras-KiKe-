@props(['type' => 'text'])

@php
    // El w-full por defecto se omite si el llamante ya define el ancho
    // (class="w-44", "flex-1"…); si no, Tailwind lo ignoraría. Ver App\Support\Html.
    $fullWidth = ! App\Support\Html::hasWidth($attributes);
@endphp

<input type="{{ $type }}" {{ $attributes->class([
    'block rounded-lg border-slate-300 bg-white text-sm shadow-sm transition',
    'w-full' => $fullWidth,
    'focus:border-indigo-500 focus:ring-indigo-500',
    'dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100',
    'disabled:cursor-not-allowed disabled:bg-slate-50 dark:disabled:bg-slate-800',
]) }}>

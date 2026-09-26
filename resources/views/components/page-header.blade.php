@props([
    'title',
    'subtitle' => null,
    'back' => null,
])

<div {{ $attributes->class('mb-6 flex flex-wrap items-start justify-between gap-4') }}>
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="mb-1 inline-flex items-center gap-1 text-xs font-medium text-slate-500 transition hover:text-indigo-600">
                &larr; Volver
            </a>
        @endif
        <h1 class="truncate text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
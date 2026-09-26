@props([
    'title' => 'Nada por aquí todavía',
    'description' => null,
    'icon' => 'inbox',
])

<div {{ $attributes->class('flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 px-6 py-12 text-center dark:border-slate-600') }}>
    <div class="mb-3 rounded-full bg-slate-100 p-3 dark:bg-slate-700">
        <x-icon :name="$icon" class="h-6 w-6 text-slate-400" />
    </div>
    <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-4">{{ $action }}</div>
    @endisset
</div>
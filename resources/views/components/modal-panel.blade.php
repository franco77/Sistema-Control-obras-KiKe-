@props([
    'show' => false,
    'title' => null,
    'maxWidth' => '2xl',
])

@php
    $widths = [
        'sm' => 'sm:max-w-sm', 'md' => 'sm:max-w-md', 'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl', '2xl' => 'sm:max-w-2xl', '4xl' => 'sm:max-w-4xl',
    ][$maxWidth] ?? 'sm:max-w-2xl';
@endphp

<div x-data="{ show: @js($show) }"
     x-show="show"
     x-on:keydown.escape.window="show = false"
     class="fixed inset-0 z-40 overflow-y-auto"
     style="display: none;">
    <div class="flex min-h-screen items-end justify-center p-4 sm:items-center">
        <div x-show="show" x-transition.opacity
             class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
             {{ $attributes->only('wire:click') }}></div>

        <div x-show="show"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
             class="relative w-full {{ $widths }} overflow-hidden rounded-xl bg-white shadow-xl dark:bg-slate-800">
            @if ($title)
                <header class="border-b border-slate-100 px-5 py-4 dark:border-slate-700">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $title }}</h3>
                </header>
            @endif
            <div class="px-5 py-4">{{ $slot }}</div>
            @isset($footer)
                <footer class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3 dark:border-slate-700 dark:bg-slate-900/40">
                    {{ $footer }}
                </footer>
            @endisset
        </div>
    </div>
</div>
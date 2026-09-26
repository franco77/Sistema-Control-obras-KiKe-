<textarea {{ $attributes->class([
    'block w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm transition',
    'focus:border-indigo-500 focus:ring-indigo-500',
    'dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100',
]) }}>{{ $slot }}</textarea>
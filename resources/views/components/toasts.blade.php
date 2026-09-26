<div x-data="{ toasts: [] }"
     @toast.window="
        const id = Date.now() + Math.random();
        toasts.push({ id, ...$event.detail });
        setTimeout(() => toasts = toasts.filter(t => t.id !== id), 5000);
     "
     class="pointer-events-none fixed bottom-4 right-4 z-50 flex w-full max-w-sm flex-col gap-2">
    <template x-for="toast in toasts" :key="toast.id">
        <div x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-end="opacity-0"
             class="pointer-events-auto flex items-start gap-3 rounded-lg border bg-white p-3 shadow-lg dark:bg-slate-800"
             :class="{
                'border-emerald-200 dark:border-emerald-500/30': toast.type === 'success',
                'border-red-200 dark:border-red-500/30': toast.type === 'error',
                'border-slate-200 dark:border-slate-600': toast.type === 'info',
             }">
            <div class="mt-0.5 h-2 w-2 shrink-0 rounded-full"
                 :class="{
                    'bg-emerald-500': toast.type === 'success',
                    'bg-red-500': toast.type === 'error',
                    'bg-slate-400': toast.type === 'info',
                 }"></div>
            <div class="min-w-0 flex-1">
                <p x-show="toast.title" x-text="toast.title" class="text-sm font-semibold text-slate-900 dark:text-slate-100"></p>
                <p x-text="toast.message" class="text-sm text-slate-600 dark:text-slate-300"></p>
            </div>
            <button @click="toasts = toasts.filter(t => t.id !== toast.id)"
                    class="shrink-0 text-slate-400 transition hover:text-slate-600">&times;</button>
        </div>
    </template>
</div>
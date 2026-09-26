<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <button @click="open = !open" wire:poll.60s
            class="relative rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 dark:hover:bg-slate-700">
        <x-icon name="inbox" class="h-5 w-5" />
        @if ($unreadCount > 0)
            <span class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="open" x-transition style="display:none"
         class="absolute right-0 z-30 mt-2 w-80 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-800">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5 dark:border-slate-700">
            <p class="text-xs font-semibold text-slate-900 dark:text-slate-100">Notificaciones</p>
            @if ($unreadCount > 0)
                <button wire:click="markAllAsRead" class="text-[11px] font-medium text-indigo-600 hover:underline">
                    Marcar todas como leídas
                </button>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse ($notifications as $notification)
                <a href="{{ $notification->data['url'] ?? '#' }}"
                   wire:click="markAsRead('{{ $notification->id }}')"
                   @class([
                       'block border-b border-slate-100 px-4 py-3 transition hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700/40',
                       'bg-indigo-50/50 dark:bg-indigo-500/5' => $notification->read_at === null,
                   ])>
                    <p class="text-sm font-medium text-slate-900 dark:text-slate-100">
                        {{ $notification->data['title'] ?? 'Notificación' }}
                    </p>
                    @if (! empty($notification->data['body']))
                        <p class="mt-0.5 text-xs text-slate-500">{{ $notification->data['body'] }}</p>
                    @endif
                    <p class="mt-1 text-[11px] text-slate-400">{{ $notification->created_at->diffForHumans() }}</p>
                </a>
            @empty
                <p class="px-4 py-6 text-center text-sm text-slate-500">Sin notificaciones</p>
            @endforelse
        </div>
    </div>
</div>
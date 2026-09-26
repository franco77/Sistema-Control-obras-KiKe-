<div>
    <x-page-header title="Mensajes" subtitle="Dudas y consultas que llegan desde el portal del cliente" />

    <x-card :padding="false">
        <div class="flex gap-2 border-b border-slate-100 p-4 dark:border-slate-700">
            @foreach (['open' => 'Abiertas', 'unanswered' => 'Sin responder ('.$unansweredCount.')', 'all' => 'Todas'] as $key => $label)
                <button wire:click="$set('filter', '{{ $key }}')"
                        @class([
                            'rounded-lg px-3 py-1.5 text-xs font-medium transition',
                            'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300' => $filter === $key,
                            'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700' => $filter !== $key,
                        ])>{{ $label }}</button>
            @endforeach
        </div>

        @forelse ($conversations as $conversation)
            <a href="{{ route('admin.conversations.show', $conversation) }}" wire:key="conv-{{ $conversation->id }}"
               class="flex items-center gap-4 border-b border-slate-100 px-5 py-3 transition last:border-0 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700/30">
                <div class="min-w-0 flex-1">
                    <p class="flex items-center gap-2 text-sm font-medium text-slate-900 dark:text-slate-100">
                        {{ $conversation->subject }}
                        @if ($conversation->unread_for_staff > 0)
                            <span class="rounded-full bg-red-100 px-1.5 text-[11px] font-semibold text-red-700">
                                {{ $conversation->unread_for_staff }} nuevos
                            </span>
                        @endif
                    </p>
                    <p class="text-xs text-slate-500">
                        {{ $conversation->client->name }}
                        @if ($conversation->project) · {{ $conversation->project->code }} @endif
                        · {{ $conversation->messages_count }} mensajes
                    </p>
                </div>

                <x-badge :color="$conversation->status->color()" size="xs">{{ $conversation->status->label() }}</x-badge>

                <span class="w-28 text-right text-xs text-slate-400">
                    {{ $conversation->last_message_at?->diffForHumans() }}
                </span>
            </a>
        @empty
            <div class="p-5"><x-empty-state title="Sin conversaciones" icon="chat" /></div>
        @endforelse

        @if ($conversations->hasPages())
            <div class="border-t border-slate-100 p-4 dark:border-slate-700">{{ $conversations->links() }}</div>
        @endif
    </x-card>
</div>
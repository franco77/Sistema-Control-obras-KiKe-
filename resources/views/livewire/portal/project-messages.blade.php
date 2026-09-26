<div class="space-y-6">
    <header class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-stone-900">Mensajes</h1>
            <p class="mt-1 text-sm text-stone-600">Consultas sobre tu obra, con todo el histórico en un sitio.</p>
        </div>

        @if ($conversations->isNotEmpty())
            <button wire:click="newThread"
                    class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                Nueva consulta
            </button>
        @endif
    </header>

    <div class="grid gap-6 lg:grid-cols-3">
        @if ($conversations->isNotEmpty())
            <aside class="space-y-2">
                @foreach ($conversations as $item)
                    <button wire:click="openThread({{ $item->id }})"
                            @class([
                                'w-full rounded-xl border p-4 text-left transition',
                                'border-stone-900 bg-white' => $conversationId === $item->id,
                                'border-stone-200 bg-white hover:border-stone-400' => $conversationId !== $item->id,
                            ])>
                        <p class="truncate text-sm font-medium text-stone-900">{{ $item->subject }}</p>
                        <p class="mt-0.5 text-xs text-stone-500">
                            {{ $item->messages_count }} mensajes · {{ $item->last_message_at?->diffForHumans() }}
                        </p>
                    </button>
                @endforeach
            </aside>
        @endif

        <div class="{{ $conversations->isNotEmpty() ? 'lg:col-span-2' : 'lg:col-span-3' }} space-y-4">
            @if ($conversation)
                <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-stone-900">{{ $conversation->subject }}</h2>

                    <div class="mt-5 space-y-4">
                        @foreach ($conversation->messages as $message)
                            <div @class(['flex gap-3', 'flex-row-reverse' => $message->isFromClient()])>
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold
                                            {{ $message->isFromClient() ? 'bg-stone-900 text-white' : 'bg-stone-100 text-stone-600' }}">
                                    {{ Str::substr($message->display_author, 0, 2) }}
                                </div>

                                <div @class([
                                    'max-w-md rounded-2xl px-4 py-2.5',
                                    'bg-stone-900 text-white' => $message->isFromClient(),
                                    'bg-stone-100 text-stone-800' => ! $message->isFromClient(),
                                ])>
                                    <p class="whitespace-pre-line text-sm leading-relaxed">{{ $message->body }}</p>
                                    <p @class([
                                        'mt-1 text-[11px]',
                                        'text-stone-400' => $message->isFromClient(),
                                        'text-stone-500' => ! $message->isFromClient(),
                                    ])>{{ $message->display_author }} · {{ $message->created_at->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-stone-900">
                    {{ $conversation ? 'Responder' : 'Escríbenos' }}
                </h2>

                <form wire:submit="send" class="mt-4 space-y-3">
                    @unless ($conversation)
                        <div>
                            <label class="block text-xs font-medium text-stone-700">Asunto</label>
                            <input type="text" wire:model="subject" placeholder="¿Sobre qué quieres preguntarnos?"
                                   class="mt-1 block w-full rounded-lg border-stone-300 text-sm focus:border-stone-900 focus:ring-stone-900">
                            @error('subject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endunless

                    <div>
                        <textarea wire:model="body" rows="4" placeholder="Cuéntanos…"
                                  class="block w-full rounded-lg border-stone-300 text-sm focus:border-stone-900 focus:ring-stone-900"></textarea>
                        @error('body') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit"
                            class="rounded-lg bg-stone-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-stone-700">
                        Enviar mensaje
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<div>
    <x-page-header :title="$conversation->subject"
                   :subtitle="$conversation->client->name.($conversation->project ? ' · '.$conversation->project->code : '')"
                   :back="route('admin.conversations.index')">
        <x-slot:actions>
            <x-badge :color="$conversation->status->color()" size="md" dot>{{ $conversation->status->label() }}</x-badge>

            <x-select wire:change="assign($event.target.value)" class="w-44 text-xs">
                <option value="">Sin asignar</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected($conversation->assigned_to === $user->id)>{{ $user->name }}</option>
                @endforeach
            </x-select>

            @if ($conversation->status->value !== 'closed')
                <x-btn variant="secondary" wire:click="close">Cerrar hilo</x-btn>
            @endif

            @if ($conversation->project)
                <x-btn :href="route('admin.projects.show', $conversation->project)">Ver obra</x-btn>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mx-auto max-w-3xl space-y-4">
        @foreach ($messages as $message)
            <div @class([
                'flex gap-3',
                'flex-row-reverse' => ! $message->isFromClient(),
            ])>
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold
                            {{ $message->isFromClient() ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700' }}">
                    {{ Str::substr($message->display_author, 0, 2) }}
                </div>

                <div @class([
                    'max-w-lg rounded-2xl px-4 py-2.5',
                    'bg-white border border-slate-200 dark:bg-slate-800 dark:border-slate-700' => $message->isFromClient(),
                    'bg-indigo-600 text-white' => ! $message->isFromClient(),
                ])>
                    <p class="whitespace-pre-line text-sm">{{ $message->body }}</p>
                    <p @class([
                        'mt-1 text-[11px]',
                        'text-slate-400' => $message->isFromClient(),
                        'text-indigo-200' => ! $message->isFromClient(),
                    ])>
                        {{ $message->display_author }} · {{ $message->created_at->format('d/m/Y H:i') }}
                    </p>
                </div>
            </div>
        @endforeach

        @if ($conversation->status->value !== 'closed')
            <x-card title="Responder">
                <form wire:submit="send" class="space-y-3">
                    <x-field :error="$errors->first('reply')">
                        <x-textarea wire:model="reply" rows="4"
                                    placeholder="Escribe tu respuesta. El cliente la verá en su portal y recibirá aviso." />
                    </x-field>
                    <div class="flex justify-end">
                        <x-btn type="submit">Enviar respuesta</x-btn>
                    </div>
                </form>
            </x-card>
        @endif
    </div>
</div>
<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Conversations;

use App\Enums\AuthorType;
use App\Enums\ConversationStatus;
use App\Livewire\Concerns\WithToasts;
use App\Models\Conversation;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class ConversationShow extends Component
{
    use WithToasts;

    public Conversation $conversation;

    public string $reply = '';

    public function mount(Conversation $conversation): void
    {
        $this->authorize('view', $conversation);
        $this->conversation = $conversation;

        // Al abrir el hilo se dan por leídos los mensajes del cliente.
        $conversation->messages()->whereNull('read_at')->update(['read_at' => now()]);
        $conversation->update(['unread_for_staff' => 0]);
    }

    public function send(): void
    {
        $this->validate([
            'reply' => ['required', 'string', 'max:5000'],
        ], attributes: ['reply' => 'mensaje']);

        $this->conversation->messages()->create([
            'author_type' => AuthorType::Staff,
            'user_id' => auth()->id(),
            'author_name' => auth()->user()->name,
            'body' => $this->reply,
            'read_at' => now(),
        ]);

        $this->conversation->update([
            'status' => ConversationStatus::Answered,
            'last_message_at' => now(),
            'unread_for_client' => $this->conversation->unread_for_client + 1,
            'assigned_to' => $this->conversation->assigned_to ?? auth()->id(),
        ]);

        $this->reset('reply');
        $this->toastSuccess('Respuesta enviada. El cliente la verá en su portal.');
    }

    public function close(): void
    {
        $this->conversation->update(['status' => ConversationStatus::Closed]);
        $this->toastSuccess('Conversación cerrada.');
    }

    public function assign(?int $userId): void
    {
        $this->conversation->update(['assigned_to' => $userId]);
    }

    public function render()
    {
        return view('livewire.admin.conversations.conversation-show', [
            'messages' => $this->conversation->messages()->with('user')->get(),
            'users' => User::active()->orderBy('name')->get(['id', 'name']),
        ])->title($this->conversation->subject);
    }
}
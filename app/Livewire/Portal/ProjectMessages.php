<?php

declare(strict_types=1);

namespace App\Livewire\Portal;

use App\Enums\AuthorType;
use App\Enums\ConversationStatus;
use App\Livewire\Concerns\InteractsWithPortal;
use App\Livewire\Concerns\WithToasts;
use App\Models\Conversation;
use App\Notifications\Internal\ClientMessageReceivedNotification;
use App\Services\Notifications\NotificationDispatcher;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Hilo de mensajes entre el cliente y el equipo de obra. */
#[Layout('components.layouts.portal')]
class ProjectMessages extends Component
{
    use InteractsWithPortal;
    use WithToasts;

    public string $subject = '';
    public string $body = '';
    public ?int $conversationId = null;

    public function mount(): void
    {
        $project = $this->portalProject();

        $this->conversationId = $project->conversations()->latest('last_message_at')->value('id');

        // Lo que ha escrito el equipo queda leído al abrir la pantalla.
        $project->conversations()->update(['unread_for_client' => 0]);
    }

    public function send(NotificationDispatcher $notifications): void
    {
        $this->portalAbility('message.send');

        $this->validate([
            'body' => ['required', 'string', 'min:2', 'max:5000'],
            'subject' => [$this->conversationId ? 'nullable' : 'required', 'string', 'max:191'],
        ], [
            'body.required' => 'Escribe tu mensaje.',
            'subject.required' => 'Ponle un asunto a tu consulta.',
        ]);

        $project = $this->portalProject();

        $conversation = $this->conversationId
            ? Conversation::findOrFail($this->conversationId)
            : $project->conversations()->create([
                'client_id' => $project->client_id,
                'subject' => $this->subject,
                'status' => ConversationStatus::Open,
            ]);

        abort_unless($conversation->project_id === $project->id, 403);

        $message = $conversation->messages()->create([
            'author_type' => AuthorType::Client,
            'author_name' => $project->client->name,
            'body' => $this->body,
            'ip' => request()->ip(),
        ]);

        $conversation->update([
            'status' => ConversationStatus::Open,
            'last_message_at' => now(),
            'unread_for_staff' => $conversation->unread_for_staff + 1,
        ]);

        $notifications->toRole('admin', new ClientMessageReceivedNotification($message));

        $this->conversationId = $conversation->id;
        $this->reset('body', 'subject');
        $this->toastSuccess('Mensaje enviado. Te responderemos lo antes posible.');
    }

    public function openThread(int $id): void
    {
        $this->conversationId = $id;
    }

    public function newThread(): void
    {
        $this->conversationId = null;
        $this->reset('body', 'subject');
    }

    public function render()
    {
        $project = $this->portalProject();

        return view('livewire.portal.project-messages', [
            'project' => $project,
            'conversations' => $project->conversations()->withCount('messages')->get(),
            'conversation' => $this->conversationId
                ? Conversation::with('messages.user')->find($this->conversationId)
                : null,
        ])->title('Mensajes');
    }
}
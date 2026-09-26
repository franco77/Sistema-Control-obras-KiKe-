<?php

declare(strict_types=1);

namespace App\Notifications\Internal;

use App\Models\ConversationMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Mensaje o duda enviada por el cliente desde su portal. */
class ClientMessageReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ConversationMessage $message) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $conversation = $this->message->conversation;

        return (new MailMessage)
            ->subject("Nuevo mensaje de {$conversation->client->name}")
            ->line("**{$conversation->subject}**")
            ->line($this->message->body)
            ->action('Responder', route('admin.conversations.show', $conversation));
    }

    public function toArray(object $notifiable): array
    {
        $conversation = $this->message->conversation;

        return [
            'type' => 'conversation.message',
            'title' => 'Mensaje de '.$conversation->client->name,
            'body' => str($this->message->body)->limit(120)->value(),
            'url' => route('admin.conversations.show', $conversation),
        ];
    }
}
<?php

declare(strict_types=1);

namespace App\Notifications\Internal;

use App\Models\CalendarEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Recordatorio de un evento de agenda (visita, hito, reunión…). */
class EventReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly CalendarEvent $event) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Recordatorio: '.$this->event->title)
            ->line("**{$this->event->title}**")
            ->line($this->event->starts_at->translatedFormat('l d \d\e F \a \l\a\s H:i'))
            ->when($this->event->location, fn (MailMessage $m) => $m->line('Lugar: '.$this->event->location))
            ->action('Ver agenda', route('admin.calendar.index'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'event.reminder',
            'title' => $this->event->title,
            'body' => $this->event->starts_at->format('d/m/Y H:i'),
            'url' => route('admin.calendar.index'),
        ];
    }
}
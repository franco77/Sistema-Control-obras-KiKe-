<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CalendarEvent;
use App\Notifications\Internal\EventReminderNotification;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Console\Command;

/** Envía los recordatorios de agenda cuya hora ya ha llegado. */
class SendEventRemindersCommand extends Command
{
    protected $signature = 'crm:send-event-reminders';

    protected $description = 'Envía recordatorios de eventos de calendario';

    public function handle(NotificationDispatcher $dispatcher): int
    {
        $events = CalendarEvent::with('owner', 'attendees')->needingReminder()->get();

        foreach ($events as $event) {
            $recipients = $event->attendees->merge(collect([$event->owner])->filter())->unique('id');

            if ($recipients->isNotEmpty()) {
                $dispatcher->toStaff($recipients, new EventReminderNotification($event));
            }

            $event->forceFill(['reminder_sent_at' => now()])->saveQuietly();
        }

        $this->info("{$events->count()} recordatorios enviados.");

        return self::SUCCESS;
    }
}
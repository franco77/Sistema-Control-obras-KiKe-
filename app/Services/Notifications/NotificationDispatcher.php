<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\Client;
use App\Models\MessageLog;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as Notifier;

/**
 * Punto único de salida de las comunicaciones.
 *
 * Además de enviar, deja traza en message_logs para poder auditar qué se
 * comunicó al cliente y cuándo, algo imprescindible cuando hay discusiones
 * sobre plazos o aprobaciones.
 */
class NotificationDispatcher
{
    public function toClient(Client $client, Notification $notification, ?Model $related = null): void
    {
        $recipients = $client->notification_emails;

        if ($recipients === []) {
            Log::warning('Cliente sin email para notificar', ['client_id' => $client->id]);

            return;
        }

        $this->dispatch($recipients, $notification, 'client', $related);
    }

    public function toProvider(Provider $provider, Notification $notification, ?Model $related = null): void
    {
        if (blank($provider->email)) {
            return;
        }

        $this->dispatch([$provider->email], $notification, 'provider', $related);
    }

    /** Notificación interna al equipo (base de datos + email opcional). */
    public function toStaff(iterable $users, Notification $notification): void
    {
        Notifier::send($users, $notification);
    }

    public function toRole(string $role, Notification $notification): void
    {
        $this->toStaff(User::role($role)->active()->get(), $notification);
    }

    private function dispatch(array $recipients, Notification $notification, string $type, ?Model $related): void
    {
        foreach ($recipients as $email) {
            $log = MessageLog::create([
                'related_type' => $related?->getMorphClass(),
                'related_id' => $related?->getKey(),
                'channel' => 'mail',
                'recipient' => $email,
                'recipient_type' => $type,
                'subject' => method_exists($notification, 'logSubject') ? $notification->logSubject() : class_basename($notification),
                'status' => 'queued',
            ]);

            try {
                Notifier::route('mail', $email)->notify($notification);

                $log->update(['status' => 'sent', 'sent_at' => now()]);
            } catch (\Throwable $e) {
                $log->update(['status' => 'failed', 'error' => $e->getMessage()]);

                Log::error('Fallo al enviar notificación', [
                    'recipient' => $email,
                    'notification' => $notification::class,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
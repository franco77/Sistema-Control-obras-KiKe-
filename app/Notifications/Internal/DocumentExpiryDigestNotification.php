<?php

declare(strict_types=1);

namespace App\Notifications\Internal;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/** Resumen interno diario de documentación caducada o próxima a caducar. */
class DocumentExpiryDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Collection $documents) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('['.config('app.name').'] '.$this->documents->count().' documentos requieren atención')
            ->line('Estos documentos están caducados o caducan en los próximos días:');

        foreach ($this->documents->take(20) as $document) {
            $owner = $document->documentable?->name ?? '—';
            $mail->line("• {$owner} — {$document->category->label()} — vence el ".$document->expires_on->format('d/m/Y'));
        }

        return $mail->action('Revisar documentación', route('admin.documents.index'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'documents.expiring',
            'count' => $this->documents->count(),
            'title' => $this->documents->count().' documentos caducan pronto',
            'url' => route('admin.documents.index'),
        ];
    }
}
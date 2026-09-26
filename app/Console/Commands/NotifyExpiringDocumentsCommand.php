<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\Provider;
use App\Notifications\Internal\DocumentExpiryDigestNotification;
use App\Notifications\Provider\DocumentExpiringNotification;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Console\Command;

/**
 * Vigila la documentación obligatoria (seguros, PRL, RETA): avisa al equipo
 * con un resumen y al proveedor afectado, sin repetir el aviso cada día.
 */
class NotifyExpiringDocumentsCommand extends Command
{
    protected $signature = 'crm:notify-expiring-documents {--days=30}';

    protected $description = 'Avisa de documentación caducada o próxima a caducar';

    public function handle(NotificationDispatcher $dispatcher): int
    {
        $days = (int) $this->option('days');

        $documents = Document::with('documentable')
            ->expiring($days)
            ->where(fn ($q) => $q->whereNull('expiry_notified_at')
                ->orWhere('expiry_notified_at', '<', now()->subDays(7)))
            ->get();

        if ($documents->isEmpty()) {
            $this->info('No hay documentos por caducar.');

            return self::SUCCESS;
        }

        $dispatcher->toRole('admin', new DocumentExpiryDigestNotification($documents));

        foreach ($documents as $document) {
            if ($document->documentable instanceof Provider) {
                $dispatcher->toProvider($document->documentable, new DocumentExpiringNotification($document));
            }

            $document->forceFill(['expiry_notified_at' => now()])->saveQuietly();
        }

        $this->info("{$documents->count()} documentos notificados.");

        return self::SUCCESS;
    }
}
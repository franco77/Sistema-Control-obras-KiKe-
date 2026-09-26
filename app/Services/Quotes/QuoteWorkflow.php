<?php

declare(strict_types=1);

namespace App\Services\Quotes;

use App\Enums\QuoteStatus;
use App\Exceptions\InvalidTransitionException;
use App\Models\Quote;
use App\Notifications\Client\QuoteReadyNotification;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Portal\PortalTokenService;
use Illuminate\Support\Facades\DB;

/**
 * Máquina de estados del presupuesto.
 *
 * Centraliza las transiciones válidas para que ningún componente Livewire
 * pueda dejar un presupuesto en un estado imposible.
 */
class QuoteWorkflow
{
    /** @var array<string, array<int, QuoteStatus>> */
    private const TRANSITIONS = [
        'draft' => [QuoteStatus::Sent, QuoteStatus::Cancelled],
        'sent' => [QuoteStatus::Viewed, QuoteStatus::Approved, QuoteStatus::Rejected, QuoteStatus::Expired, QuoteStatus::Superseded, QuoteStatus::Cancelled],
        'viewed' => [QuoteStatus::Approved, QuoteStatus::Rejected, QuoteStatus::Expired, QuoteStatus::Superseded, QuoteStatus::Cancelled],
        'approved' => [QuoteStatus::Superseded],
        'rejected' => [QuoteStatus::Superseded, QuoteStatus::Draft],
        'expired' => [QuoteStatus::Sent, QuoteStatus::Superseded],
        'superseded' => [],
        'cancelled' => [],
    ];

    public function __construct(
        private readonly QuoteCalculator $calculator,
        private readonly PortalTokenService $tokens,
        private readonly NotificationDispatcher $notifications,
    ) {}

    public function canTransition(Quote $quote, QuoteStatus $to): bool
    {
        return in_array($to, self::TRANSITIONS[$quote->status->value] ?? [], true);
    }

    private function guard(Quote $quote, QuoteStatus $to): void
    {
        if (! $this->canTransition($quote, $to)) {
            throw new InvalidTransitionException(
                "No se puede pasar el presupuesto {$quote->number} de «{$quote->status->label()}» a «{$to->label()}»."
            );
        }
    }

    /**
     * Envía el presupuesto al cliente: recalcula, emite un token de portal
     * acotado y dispara la notificación con el enlace seguro.
     */
    public function send(Quote $quote, ?string $message = null): Quote
    {
        $this->guard($quote, QuoteStatus::Sent);

        return DB::transaction(function () use ($quote, $message) {
            $this->calculator->recalculate($quote);

            $quote->forceFill([
                'status' => QuoteStatus::Sent,
                'sent_at' => now(),
                'valid_until' => $quote->valid_until ?? now()->addDays((int) setting('quotes.valid_days', 30)),
            ])->save();

            $plainToken = $this->tokens->issue(
                $quote,
                abilities: ['quote.view', 'quote.decide', 'quote.download'],
                name: 'Presupuesto '.$quote->reference,
                ttlDays: (int) setting('quotes.token_ttl_days', 120),
            );

            $quote->recordActivity('quote.sent', 'Presupuesto enviado al cliente');

            $this->notifications->toClient(
                $quote->client,
                new QuoteReadyNotification($quote, $plainToken, $message),
                related: $quote,
            );

            return $quote;
        });
    }

    /** Registra la primera apertura del enlace por parte del cliente. */
    public function markViewed(Quote $quote, ?string $ip = null): Quote
    {
        $quote->forceFill([
            'first_viewed_at' => $quote->first_viewed_at ?? now(),
            'last_viewed_at' => now(),
            'views_count' => $quote->views_count + 1,
        ])->save();

        if ($quote->status === QuoteStatus::Sent) {
            $quote->forceFill(['status' => QuoteStatus::Viewed])->save();
            $quote->recordActivity('quote.viewed', 'El cliente abrió el presupuesto', ['ip' => $ip]);
        }

        return $quote;
    }

    public function approve(Quote $quote, string $signerName, ?string $ip = null): Quote
    {
        $this->guard($quote, QuoteStatus::Approved);

        if ($quote->isExpired()) {
            throw new InvalidTransitionException('El presupuesto ha caducado; solicita una nueva versión.');
        }

        return DB::transaction(function () use ($quote, $signerName, $ip) {
            $quote->forceFill([
                'status' => QuoteStatus::Approved,
                'decided_at' => now(),
                'decision_ip' => $ip,
                'signer_name' => $signerName,
                'rejection_reason' => null,
            ])->save();

            $quote->recordActivity('quote.approved', "Aprobado por {$signerName}", ['ip' => $ip]);

            return $quote;
        });
    }

    public function reject(Quote $quote, string $reason, ?string $signerName = null, ?string $ip = null): Quote
    {
        $this->guard($quote, QuoteStatus::Rejected);

        return DB::transaction(function () use ($quote, $reason, $signerName, $ip) {
            $quote->forceFill([
                'status' => QuoteStatus::Rejected,
                'decided_at' => now(),
                'decision_ip' => $ip,
                'signer_name' => $signerName,
                'rejection_reason' => $reason,
            ])->save();

            $quote->recordActivity('quote.rejected', 'Rechazado por el cliente', [
                'reason' => $reason,
                'ip' => $ip,
            ]);

            return $quote;
        });
    }

    public function expire(Quote $quote): Quote
    {
        $this->guard($quote, QuoteStatus::Expired);

        $quote->forceFill(['status' => QuoteStatus::Expired])->save();
        $quote->recordActivity('quote.expired', 'Presupuesto caducado sin respuesta');

        return $quote;
    }

    public function cancel(Quote $quote, ?string $reason = null): Quote
    {
        $this->guard($quote, QuoteStatus::Cancelled);

        $quote->forceFill(['status' => QuoteStatus::Cancelled])->save();
        $quote->portalTokens()->update(['revoked_at' => now()]);
        $quote->recordActivity('quote.cancelled', $reason);

        return $quote;
    }
}
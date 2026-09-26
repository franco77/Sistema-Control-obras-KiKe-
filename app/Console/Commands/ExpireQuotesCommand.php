<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Quote;
use App\Services\Quotes\QuoteWorkflow;
use Illuminate\Console\Command;

/** Marca como caducados los presupuestos vencidos sin respuesta. */
class ExpireQuotesCommand extends Command
{
    protected $signature = 'crm:expire-quotes';

    protected $description = 'Caduca los presupuestos enviados cuya validez ha vencido';

    public function handle(QuoteWorkflow $workflow): int
    {
        $quotes = Quote::pending()
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '<', now())
            ->get();

        foreach ($quotes as $quote) {
            $workflow->expire($quote);
            $this->line("Caducado {$quote->reference}");
        }

        $this->info("{$quotes->count()} presupuestos caducados.");

        return self::SUCCESS;
    }
}
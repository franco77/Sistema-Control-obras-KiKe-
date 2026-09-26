<?php

declare(strict_types=1);

namespace App\Services\Quotes;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\QuoteSection;
use Illuminate\Support\Facades\DB;

/**
 * Control de versiones del presupuesto.
 *
 * Una versión nunca se edita después de enviarse: se clona el árbol completo
 * en una versión nueva en borrador y la anterior queda como "sustituida",
 * conservando su histórico de envíos y decisiones.
 */
class QuoteVersionService
{
    public function __construct(private readonly QuoteCalculator $calculator) {}

    public function createNewVersion(Quote $quote, ?string $note = null): Quote
    {
        return DB::transaction(function () use ($quote, $note) {
            $quote->load('sections.items');

            $rootId = $quote->parent_quote_id ?? $quote->id;
            $nextVersion = (int) Quote::where('id', $rootId)
                ->orWhere('parent_quote_id', $rootId)
                ->max('version') + 1;

            $copy = $quote->replicate([
                'status', 'version', 'parent_quote_id', 'project_id',
                'sent_at', 'first_viewed_at', 'last_viewed_at', 'views_count',
                'decided_at', 'decision_ip', 'signer_name', 'signature_path', 'rejection_reason',
                'deleted_at',
            ]);

            $copy->fill([
                'status' => QuoteStatus::Draft,
                'version' => $nextVersion,
                'parent_quote_id' => $rootId,
                'issue_date' => now()->toDateString(),
                'valid_until' => now()->addDays((int) setting('quotes.valid_days', 30))->toDateString(),
                'views_count' => 0,
                'created_by' => auth()->id(),
                'internal_notes' => trim((string) $quote->internal_notes."\n".$note),
            ]);

            $copy->save();

            foreach ($quote->sections as $section) {
                /** @var QuoteSection $newSection */
                $newSection = $copy->sections()->create(
                    collect($section->only(['trade_id', 'name', 'description', 'position']))->all()
                );

                foreach ($section->items as $item) {
                    $newSection->items()->create(
                        collect($item->only([
                            'catalog_item_id', 'trade_id', 'code', 'name', 'description', 'unit',
                            'quantity', 'unit_price', 'unit_cost', 'discount_percent',
                            'is_optional', 'is_included', 'position', 'notes',
                        ]))->all()
                    );
                }
            }

            $this->calculator->recalculate($copy);

            // La versión anterior deja de estar viva y sus enlaces se revocan.
            if ($quote->status->isNot(QuoteStatus::Approved)) {
                $quote->forceFill(['status' => QuoteStatus::Superseded])->save();
                $quote->portalTokens()->update(['revoked_at' => now()]);
            }

            $quote->recordActivity('quote.superseded', "Sustituido por la versión {$nextVersion}");
            $copy->recordActivity('quote.version_created', "Versión {$nextVersion} creada a partir de {$quote->reference}");

            return $copy->fresh(['sections.items']);
        });
    }

    /** Duplica un presupuesto como uno nuevo e independiente (otra oportunidad). */
    public function duplicate(Quote $quote, ?int $clientId = null, ?int $propertyId = null): Quote
    {
        return DB::transaction(function () use ($quote, $clientId, $propertyId) {
            $quote->load('sections.items');

            $copy = $quote->replicate(['status', 'number', 'version', 'parent_quote_id', 'project_id']);
            $copy->fill([
                'number' => app(QuoteNumberGenerator::class)->next(),
                'status' => QuoteStatus::Draft,
                'version' => 1,
                'parent_quote_id' => null,
                'project_id' => null,
                'client_id' => $clientId ?? $quote->client_id,
                'property_id' => $propertyId ?? $quote->property_id,
                'issue_date' => now()->toDateString(),
                'valid_until' => now()->addDays((int) setting('quotes.valid_days', 30))->toDateString(),
                'sent_at' => null, 'first_viewed_at' => null, 'last_viewed_at' => null,
                'views_count' => 0, 'decided_at' => null, 'decision_ip' => null,
                'signer_name' => null, 'signature_path' => null, 'rejection_reason' => null,
                'created_by' => auth()->id(),
            ]);
            $copy->save();

            foreach ($quote->sections as $section) {
                $newSection = $copy->sections()->create(
                    collect($section->only(['trade_id', 'name', 'description', 'position']))->all()
                );

                foreach ($section->items as $item) {
                    $newSection->items()->create(collect($item->only([
                        'catalog_item_id', 'trade_id', 'code', 'name', 'description', 'unit',
                        'quantity', 'unit_price', 'unit_cost', 'discount_percent',
                        'is_optional', 'is_included', 'position', 'notes',
                    ]))->all());
                }
            }

            return $this->calculator->recalculate($copy);
        });
    }
}
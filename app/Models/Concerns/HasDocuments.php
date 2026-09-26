<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\DocumentCategory;
use App\Models\Document;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Documentación adjunta polimórfica (contratos, seguros, planos, facturas…).
 */
trait HasDocuments
{
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable')->latest();
    }

    public function documentsOfCategory(DocumentCategory $category): MorphMany
    {
        return $this->documents()->where('category', $category);
    }

    public function clientVisibleDocuments(): MorphMany
    {
        return $this->documents()->where('visible_to_client', true);
    }

    /** Documentos caducados o que caducan en los próximos $days días. */
    public function expiringDocuments(int $days = 30): MorphMany
    {
        return $this->documents()
            ->whereNotNull('expires_on')
            ->where('expires_on', '<=', now()->addDays($days));
    }
}
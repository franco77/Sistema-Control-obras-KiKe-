<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\PortalAccessToken;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Acceso sin login al portal mediante enlaces firmados por token.
 * La emisión y validación viven en App\Services\Portal\PortalTokenService.
 */
trait HasPortalTokens
{
    public function portalTokens(): MorphMany
    {
        return $this->morphMany(PortalAccessToken::class, 'tokenable')->latest();
    }

    public function activePortalTokens(): MorphMany
    {
        return $this->portalTokens()->active();
    }
}
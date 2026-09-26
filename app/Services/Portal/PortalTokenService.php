<?php

declare(strict_types=1);

namespace App\Services\Portal;

use App\Models\PortalAccessToken;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Emisión y validación de los tokens de acceso al portal sin login.
 *
 * El token en claro (48 caracteres aleatorios) solo existe en el enlace que
 * se envía; en base de datos se guarda su hash SHA-256. Cada token está
 * ligado a un recurso concreto, con permisos y caducidad propios.
 */
class PortalTokenService
{
    public const TOKEN_BYTES = 36; // 48 caracteres en base64url

    public function issue(
        Model $tokenable,
        array $abilities,
        string $audience = 'client',
        ?string $name = null,
        ?int $ttlDays = null,
        ?int $maxUses = null,
    ): string {
        $plain = $this->generatePlainToken();

        $tokenable->portalTokens()->create([
            'name' => $name,
            'token_hash' => $this->hash($plain),
            'audience' => $audience,
            'abilities' => $abilities,
            'expires_at' => $ttlDays ? now()->addDays($ttlDays) : null,
            'max_uses' => $maxUses,
            'created_by' => auth()->id(),
        ]);

        return $plain;
    }

    /** Reutiliza el token activo del recurso o emite uno nuevo. */
    public function issueOrReuse(Model $tokenable, array $abilities, string $audience = 'client', ?int $ttlDays = null): ?string
    {
        // No se puede recuperar un token ya emitido (solo guardamos el hash),
        // así que se revoca el anterior y se emite uno nuevo.
        $tokenable->portalTokens()->active()->update(['revoked_at' => now()]);

        return $this->issue($tokenable, $abilities, $audience, ttlDays: $ttlDays);
    }

    public function resolve(string $plain): ?PortalAccessToken
    {
        $token = PortalAccessToken::with('tokenable')
            ->where('token_hash', $this->hash($plain))
            ->first();

        return $token?->isValid() ? $token : null;
    }

    public function revokeAllFor(Model $tokenable): int
    {
        return $tokenable->portalTokens()->whereNull('revoked_at')->update(['revoked_at' => now()]);
    }

    public function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }

    private function generatePlainToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(self::TOKEN_BYTES)), '+/', '-_'), '=');
    }

    public function urlFor(string $plain): string
    {
        return route('portal.enter', ['token' => $plain]);
    }
}
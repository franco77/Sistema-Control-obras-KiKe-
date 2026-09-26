<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Token de acceso al portal sin login.
 *
 * Seguridad: en base de datos solo se guarda el hash SHA-256 del token; el
 * valor en claro se entrega una única vez en el enlace enviado al cliente.
 * Cada token lleva "abilities" acotadas, caducidad y contador de usos.
 */
class PortalAccessToken extends Model
{
    protected $fillable = [
        'tokenable_type', 'tokenable_id', 'name', 'token_hash', 'audience', 'abilities',
        'expires_at', 'last_used_at', 'last_used_ip', 'uses', 'max_uses', 'revoked_at', 'created_by',
    ];

    protected $hidden = ['token_hash'];

    protected $attributes = [
        'audience' => 'client',
        'uses' => 0,
    ];
    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function tokenable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function isValid(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return $this->max_uses === null || $this->uses < $this->max_uses;
    }

    public function can(string $ability): bool
    {
        $abilities = $this->abilities ?? [];

        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }

    public function markUsed(?string $ip = null): void
    {
        $this->forceFill([
            'uses' => $this->uses + 1,
            'last_used_at' => now(),
            'last_used_ip' => $ip,
        ])->saveQuietly();
    }

    public function revoke(): void
    {
        $this->forceFill(['revoked_at' => now()])->saveQuietly();
    }
}
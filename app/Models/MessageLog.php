<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Registro de comunicaciones salientes (email, WhatsApp, SMS).
 * Permite auditar qué se envió al cliente y reintentar envíos fallidos.
 */
class MessageLog extends Model
{
    protected $fillable = [
        'related_type', 'related_id', 'channel', 'recipient', 'recipient_type',
        'subject', 'body', 'status', 'sent_at', 'error',
    ];

    protected $attributes = [
        'channel' => 'mail',
        'recipient_type' => 'client',
        'status' => 'queued',
    ];
    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }
}
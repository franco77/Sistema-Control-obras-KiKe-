<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AuthorType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id', 'author_type', 'user_id', 'author_name', 'body', 'ip', 'read_at',
    ];

    protected $attributes = [
        'author_type' => 'client',
    ];
    protected function casts(): array
    {
        return [
            'author_type' => AuthorType::class,
            'read_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isFromClient(): bool
    {
        return $this->author_type === AuthorType::Client;
    }

    /**
     * Nombre a mostrar del autor.
     *
     * Se prioriza author_name, que ambas rutas de escritura guardan en la
     * propia fila, para no depender de la relación con el usuario: así el
     * hilo se pinta igual aunque no se haya hecho eager load, y el nombre
     * se conserva aunque el empleado cause baja.
     */
    public function getDisplayAuthorAttribute(): string
    {
        if (filled($this->author_name)) {
            return $this->author_name;
        }

        if ($this->user_id !== null) {
            return $this->loadMissing('user')->user?->name ?? $this->author_type->label();
        }

        return $this->author_type->label();
    }
}
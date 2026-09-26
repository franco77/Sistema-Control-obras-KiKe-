<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConversationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Hilo de mensajes entre el cliente (desde el portal) y el equipo.
 */
class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'project_id', 'subject', 'status',
        'last_message_at', 'unread_for_staff', 'unread_for_client', 'assigned_to',
    ];

    protected $attributes = [
        'status' => 'open',
        'unread_for_staff' => 0,
        'unread_for_client' => 0,
    ];
    protected function casts(): array
    {
        return [
            'status' => ConversationStatus::class,
            'last_message_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class)->oldest();
    }

    public function latestMessage(): HasMany
    {
        return $this->hasMany(ConversationMessage::class)->latest()->limit(1);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [ConversationStatus::Open, ConversationStatus::Answered]);
    }

    public function scopeUnanswered(Builder $query): Builder
    {
        return $query->where('unread_for_staff', '>', 0);
    }
}
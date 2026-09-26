<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'name', 'role', 'email', 'phone',
        'is_primary', 'receives_notifications', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'receives_notifications' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Solo puede haber un contacto principal por cliente.
        static::saved(function (self $contact): void {
            if ($contact->is_primary) {
                static::where('client_id', $contact->client_id)
                    ->whereKeyNot($contact->getKey())
                    ->update(['is_primary' => false]);
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
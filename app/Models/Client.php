<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ClientStatus;
use App\Enums\ClientType;
use App\Models\Concerns\HasDocuments;
use App\Models\Concerns\HasSequentialCode;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasDocuments;
    use HasFactory;
    use HasSequentialCode;
    use RecordsActivity;
    use SoftDeletes;

    protected $fillable = [
        'code', 'type', 'status', 'name', 'legal_name', 'tax_id',
        'email', 'phone', 'phone_alt', 'address', 'address_extra',
        'postal_code', 'city', 'province', 'country', 'source',
        'preferred_channel', 'accepts_marketing', 'notes', 'owner_id', 'created_by',
    ];

    /** Defaults en memoria, no solo en la base de datos. */
    protected $attributes = [
        'type' => 'individual',
        'status' => 'lead',
        'country' => 'ES',
        'preferred_channel' => 'email',
        'accepts_marketing' => false,
    ];
    protected function casts(): array
    {
        return [
            'type' => ClientType::class,
            'status' => ClientStatus::class,
            'accepts_marketing' => 'boolean',
        ];
    }

    public static function codePrefix(): string
    {
        return 'CLI';
    }

    // -------------------------------------------------------- relaciones --
    public function contacts(): HasMany
    {
        return $this->hasMany(ClientContact::class)->orderByDesc('is_primary');
    }

    public function primaryContact(): HasMany
    {
        return $this->hasMany(ClientContact::class)->where('is_primary', true);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class)->latest('issue_date');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class)->latest();
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class)->latest('last_message_at');
    }

    public function events(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // ------------------------------------------------------------ scopes --
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term): void {
            $like = '%'.$term.'%';
            $q->where('name', 'like', $like)
                ->orWhere('legal_name', 'like', $like)
                ->orWhere('code', 'like', $like)
                ->orWhere('tax_id', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like);
        });
    }

    public function scopeStatus(Builder $query, ClientStatus|string|null $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    // ---------------------------------------------------------- accessors --
    public function getDisplayNameAttribute(): string
    {
        return $this->legal_name ?: $this->name;
    }

    public function getFullAddressAttribute(): string
    {
        return collect([$this->address, $this->postal_code, $this->city, $this->province])
            ->filter()->implode(', ');
    }

    public function getNotificationEmailsAttribute(): array
    {
        return collect([$this->email])
            ->merge($this->loadMissing('contacts')->contacts
                ->where('receives_notifications', true)->pluck('email'))
            ->filter()->unique()->values()->all();
    }
}
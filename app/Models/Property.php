<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PropertyType;
use App\Models\Concerns\HasDocuments;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Inmueble del cliente sobre el que se presupuesta y se ejecuta la obra.
 */
class Property extends Model
{
    use HasDocuments;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'properties';

    protected $fillable = [
        'client_id', 'alias', 'type', 'address', 'block', 'floor', 'door',
        'postal_code', 'city', 'province', 'cadastral_reference',
        'built_area', 'usable_area', 'rooms', 'bathrooms', 'year_built',
        'has_elevator', 'is_occupied', 'latitude', 'longitude',
        'access_notes', 'notes',
    ];

    protected $attributes = [
        'type' => 'apartment',
        'has_elevator' => false,
        'is_occupied' => true,
    ];
    protected function casts(): array
    {
        return [
            'type' => PropertyType::class,
            'built_area' => 'decimal:2',
            'usable_area' => 'decimal:2',
            'has_elevator' => 'boolean',
            'is_occupied' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(fn (Builder $q) => $q
            ->where('alias', 'like', $like)
            ->orWhere('address', 'like', $like)
            ->orWhere('city', 'like', $like));
    }

    public function getFullAddressAttribute(): string
    {
        $street = collect([$this->address, $this->block, $this->floor, $this->door])->filter()->implode(' ');

        return collect([$street, $this->postal_code, $this->city, $this->province])->filter()->implode(', ');
    }

    public function getLabelAttribute(): string
    {
        return $this->alias.' — '.$this->full_address;
    }
}
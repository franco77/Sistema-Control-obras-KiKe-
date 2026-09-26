<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Oficio (fontanero, electricista, pintor…). Clasifica proveedores,
 * partidas del catálogo, fases y tareas de obra.
 */
class Trade extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'color', 'icon', 'description', 'position', 'is_active'];

    protected $attributes = [
        'color' => 'gray',
        'position' => 0,
        'is_active' => true,
    ];
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $trade): void {
            if (blank($trade->slug)) {
                $trade->slug = Str::slug($trade->name);
            }
        });
    }

    public function providers(): BelongsToMany
    {
        return $this->belongsToMany(Provider::class)
            ->using(ProviderTrade::class)
            ->withPivot(['is_primary', 'hourly_rate', 'experience_years', 'notes'])
            ->withTimestamps();
    }

    public function catalogItems(): HasMany
    {
        return $this->hasMany(CatalogItem::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('position')->orderBy('name');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
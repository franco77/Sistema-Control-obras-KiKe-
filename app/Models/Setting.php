<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Configuración de la empresa en clave/valor tipada y cacheada.
 * Acceso preferente a través del helper setting().
 */
class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'cast', 'label', 'is_public'];

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }

    public const CACHE_KEY = 'settings.all';

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /** @return array<string, mixed> */
    public static function allValues(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()
            ->get()
            ->mapWithKeys(fn (self $s) => [$s->key => $s->typedValue()])
            ->all());
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::allValues()[$key] ?? $default;
    }

    public static function put(string $key, mixed $value, string $cast = 'string', string $group = 'general'): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'cast' => $cast,
                'group' => $group,
            ]
        );
    }

    public function typedValue(): mixed
    {
        return match ($this->cast) {
            'int' => (int) $this->value,
            'float' => (float) $this->value,
            'bool' => filter_var($this->value, FILTER_VALIDATE_BOOL),
            'array' => json_decode((string) $this->value, true) ?? [],
            'date' => $this->value ? \Illuminate\Support\Carbon::parse($this->value) : null,
            default => $this->value,
        };
    }
}
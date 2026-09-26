<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * Asigna un código legible y correlativo por año (CLI-2026-0001).
 *
 * El modelo que lo usa define codePrefix(). La generación se hace dentro de
 * una transacción con bloqueo de lectura para evitar colisiones cuando hay
 * varios usuarios creando registros a la vez.
 */
trait HasSequentialCode
{
    public static function bootHasSequentialCode(): void
    {
        static::creating(function (self $model): void {
            if (blank($model->code)) {
                $model->code = static::nextCode();
            }
        });
    }

    abstract public static function codePrefix(): string;

    public static function nextCode(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');
        $prefix = static::codePrefix().'-'.$year.'-';

        return DB::transaction(function () use ($prefix) {
            $softDeletes = in_array(SoftDeletes::class, class_uses_recursive(static::class), true);

            $last = static::query()
                ->when($softDeletes, fn ($q) => $q->withTrashed())
                ->where('code', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('code')
                ->value('code');

            $sequence = $last ? ((int) substr((string) $last, -4)) + 1 : 1;

            return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        });
    }
}
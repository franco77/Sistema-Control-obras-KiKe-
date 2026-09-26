<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Parte de obra / diario. Es lo que el cliente lee como "novedades"
 * en su portal cuando visible_to_client está activo.
 */
class ProjectUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'project_phase_id', 'user_id', 'title', 'body',
        'progress_snapshot', 'visible_to_client', 'notify_client', 'published_at',
    ];

    protected $attributes = [
        'visible_to_client' => true,
        'notify_client' => false,
    ];
    protected function casts(): array
    {
        return [
            'visible_to_client' => 'boolean',
            'notify_client' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ProjectPhase::class, 'project_phase_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function scopeVisibleToClient(Builder $query): Builder
    {
        return $query->published()->where('visible_to_client', true);
    }
}
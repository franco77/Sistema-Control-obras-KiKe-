<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PhotoStage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Fotografía de avance de obra. Se sirve siempre por una ruta controlada
 * (nunca por URL pública directa) para respetar visible_to_client.
 */
class ProjectPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'project_phase_id', 'project_task_id', 'project_incident_id',
        'disk', 'path', 'thumb_path', 'original_name', 'mime', 'size', 'width', 'height',
        'stage', 'caption', 'taken_at', 'visible_to_client', 'is_cover',
        'uploaded_by', 'uploaded_by_provider_id',
    ];

    protected $attributes = [
        'disk' => 'documents',
        'stage' => 'progress',
        'size' => 0,
        'visible_to_client' => true,
        'is_cover' => false,
    ];
    protected function casts(): array
    {
        return [
            'stage' => PhotoStage::class,
            'taken_at' => 'datetime',
            'visible_to_client' => 'boolean',
            'is_cover' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (self $photo): void {
            Storage::disk($photo->disk)->delete(array_filter([$photo->path, $photo->thumb_path]));
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ProjectPhase::class, 'project_phase_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id');
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(ProjectIncident::class, 'project_incident_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeVisibleToClient(Builder $query): Builder
    {
        return $query->where('visible_to_client', true);
    }

    public function scopeStage(Builder $query, PhotoStage|string|null $stage): Builder
    {
        return $stage ? $query->where('stage', $stage) : $query;
    }

    public function url(): string
    {
        return route('admin.photos.show', $this);
    }

    public function thumbUrl(): string
    {
        return route('admin.photos.show', ['photo' => $this, 'thumb' => 1]);
    }
}
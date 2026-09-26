<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'position',
        'avatar_path', 'color', 'is_active', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // -------------------------------------------------------- relaciones --
    public function managedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'manager_id');
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class, 'assigned_user_id');
    }

    public function ownedClients(): HasMany
    {
        return $this->hasMany(Client::class, 'owner_id');
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(CalendarEvent::class)->withPivot('response')->withTimestamps();
    }

    // ------------------------------------------------------------ scopes --
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ---------------------------------------------------------- accessors --
    public function getInitialsAttribute(): string
    {
        return Str::of($this->name)->explode(' ')->take(2)
            ->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))->implode('');
    }
}
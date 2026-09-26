<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CalendarEvent;
use App\Models\User;

class CalendarEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('calendar.view');
    }

    public function view(User $user, CalendarEvent $event): bool
    {
        return $user->can('calendar.view');
    }

    public function create(User $user): bool
    {
        return $user->can('calendar.create');
    }

    public function update(User $user, CalendarEvent $event): bool
    {
        return $user->can('calendar.update');
    }

    public function delete(User $user, CalendarEvent $event): bool
    {
        return $user->can('calendar.delete');
    }
}

<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('clients.view');
    }

    public function view(User $user, Property $property): bool
    {
        return $user->can('clients.view');
    }

    public function create(User $user): bool
    {
        return $user->can('clients.create');
    }

    public function update(User $user, Property $property): bool
    {
        return $user->can('clients.update');
    }

    public function delete(User $user, Property $property): bool
    {
        return $user->can('clients.delete');
    }
}

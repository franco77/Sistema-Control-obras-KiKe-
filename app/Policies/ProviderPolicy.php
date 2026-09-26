<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Provider;
use App\Models\User;

class ProviderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('providers.view');
    }

    public function view(User $user, Provider $provider): bool
    {
        return $user->can('providers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('providers.create');
    }

    public function update(User $user, Provider $provider): bool
    {
        return $user->can('providers.update');
    }

    public function delete(User $user, Provider $provider): bool
    {
        return $user->can('providers.delete');
    }
}

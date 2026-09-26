<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CatalogItem;
use App\Models\User;

class CatalogItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('catalog.view');
    }

    public function view(User $user, CatalogItem $item): bool
    {
        return $user->can('catalog.view');
    }

    public function create(User $user): bool
    {
        return $user->can('catalog.create');
    }

    public function update(User $user, CatalogItem $item): bool
    {
        return $user->can('catalog.update');
    }

    public function delete(User $user, CatalogItem $item): bool
    {
        return $user->can('catalog.delete');
    }
}

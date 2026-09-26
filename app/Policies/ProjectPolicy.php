<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('projects.view');
    }

    /** El jefe de obra siempre ve las suyas, aunque su rol sea limitado. */
    public function view(User $user, Project $project): bool
    {
        return $user->can('projects.view') || $project->manager_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('projects.create');
    }

    public function update(User $user, Project $project): bool
    {
        return $user->can('projects.update') || $project->manager_id === $user->id;
    }

    public function manageFinancials(User $user, Project $project): bool
    {
        return $user->can('projects.financials');
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->can('projects.delete');
    }
}
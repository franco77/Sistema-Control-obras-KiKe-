<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('conversations.view');
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return $user->can('conversations.view');
    }

    public function create(User $user): bool
    {
        return $user->can('conversations.create');
    }

    public function update(User $user, Conversation $conversation): bool
    {
        return $user->can('conversations.update');
    }

    public function delete(User $user, Conversation $conversation): bool
    {
        return $user->can('conversations.delete');
    }
}

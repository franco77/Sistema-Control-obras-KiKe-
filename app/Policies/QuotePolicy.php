<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\User;

class QuotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('quotes.view');
    }

    public function view(User $user, Quote $quote): bool
    {
        return $user->can('quotes.view');
    }

    public function create(User $user): bool
    {
        return $user->can('quotes.create');
    }

    /** Un presupuesto enviado deja de ser editable: hay que versionarlo. */
    public function update(User $user, Quote $quote): bool
    {
        return $user->can('quotes.update') && $quote->isEditable();
    }

    public function send(User $user, Quote $quote): bool
    {
        return $user->can('quotes.send') && $quote->status === QuoteStatus::Draft;
    }

    public function createVersion(User $user, Quote $quote): bool
    {
        return $user->can('quotes.update') && $quote->status->isNot(QuoteStatus::Draft);
    }

    public function convert(User $user, Quote $quote): bool
    {
        return $user->can('projects.create')
            && $quote->status === QuoteStatus::Approved
            && $quote->project_id === null;
    }

    public function delete(User $user, Quote $quote): bool
    {
        return $user->can('quotes.delete') && $quote->status === QuoteStatus::Draft;
    }
}
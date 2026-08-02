<?php

namespace App\Policies;

use App\Models\SavedSearch;
use App\Models\User;

class SavedSearchPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, SavedSearch $savedSearch): bool
    {
        return $user->id === $savedSearch->user_id;
    }
}

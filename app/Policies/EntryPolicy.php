<?php

namespace App\Policies;

use App\Models\Entry;
use App\Models\User;

class EntryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Entry $entry): bool
    {
        return $user->id === $entry->feed->user_id;
    }

    public function update(User $user, Entry $entry): bool
    {
        return $user->id === $entry->feed->user_id;
    }
}

<?php

namespace App\Policies;

use App\Models\Feed;
use App\Models\User;

class FeedPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Feed $feed): bool
    {
        return $user->id === $feed->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Feed $feed): bool
    {
        return $user->id === $feed->user_id;
    }

    public function delete(User $user, Feed $feed): bool
    {
        return $user->id === $feed->user_id;
    }
}

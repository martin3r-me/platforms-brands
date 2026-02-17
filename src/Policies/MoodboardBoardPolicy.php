<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsMoodboardBoard;

class MoodboardBoardPolicy
{
    public function view(User $user, BrandsMoodboardBoard $moodboardBoard): bool
    {
        return $moodboardBoard->team_id === $user->currentTeam?->id;
    }

    public function update(User $user, BrandsMoodboardBoard $moodboardBoard): bool
    {
        return $moodboardBoard->team_id === $user->currentTeam?->id;
    }

    public function delete(User $user, BrandsMoodboardBoard $moodboardBoard): bool
    {
        return $moodboardBoard->team_id === $user->currentTeam?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }
}

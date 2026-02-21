<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsMoodboardImage;
use Platform\Brands\Models\BrandsMoodboardBoard;

class MoodboardImagePolicy
{
    public function view(User $user, BrandsMoodboardImage $image): bool
    {
        $board = $image->moodboardBoard;
        if (!$board) {
            return false;
        }

        return $user->currentTeam?->id === $board->team_id;
    }

    public function create(User $user, BrandsMoodboardBoard $board): bool
    {
        return $user->currentTeam?->id === $board->team_id;
    }

    public function update(User $user, BrandsMoodboardImage $image): bool
    {
        $board = $image->moodboardBoard;
        if (!$board) {
            return false;
        }

        return $user->currentTeam?->id === $board->team_id;
    }

    public function delete(User $user, BrandsMoodboardImage $image): bool
    {
        $board = $image->moodboardBoard;
        if (!$board) {
            return false;
        }

        return $user->currentTeam?->id === $board->team_id;
    }
}

<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsSeoBoard;

class SeoBoardPolicy
{
    public function view(User $user, BrandsSeoBoard $seoBoard): bool
    {
        return $seoBoard->team_id === $user->currentTeam?->id;
    }

    public function update(User $user, BrandsSeoBoard $seoBoard): bool
    {
        return $seoBoard->team_id === $user->currentTeam?->id;
    }

    public function delete(User $user, BrandsSeoBoard $seoBoard): bool
    {
        return $seoBoard->team_id === $user->currentTeam?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }
}

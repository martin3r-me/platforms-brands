<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsLogoBoard;

class LogoBoardPolicy
{
    public function view(User $user, BrandsLogoBoard $logoBoard): bool
    {
        return $logoBoard->team_id === $user->currentTeam?->id;
    }

    public function update(User $user, BrandsLogoBoard $logoBoard): bool
    {
        return $logoBoard->team_id === $user->currentTeam?->id;
    }

    public function delete(User $user, BrandsLogoBoard $logoBoard): bool
    {
        return $logoBoard->team_id === $user->currentTeam?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }
}

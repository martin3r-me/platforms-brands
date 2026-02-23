<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsIntakeBoardBlock;

class IntakeBoardBlockPolicy
{
    public function view(User $user, BrandsIntakeBoardBlock $boardBlock): bool
    {
        return $boardBlock->team_id === $user->currentTeam?->id;
    }

    public function update(User $user, BrandsIntakeBoardBlock $boardBlock): bool
    {
        return $boardBlock->team_id === $user->currentTeam?->id;
    }

    public function delete(User $user, BrandsIntakeBoardBlock $boardBlock): bool
    {
        return $boardBlock->team_id === $user->currentTeam?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }
}

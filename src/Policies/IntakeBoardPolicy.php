<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsIntakeBoard;

class IntakeBoardPolicy
{
    public function view(User $user, BrandsIntakeBoard $intakeBoard): bool
    {
        return $intakeBoard->team_id === $user->currentTeam?->id;
    }

    public function update(User $user, BrandsIntakeBoard $intakeBoard): bool
    {
        return $intakeBoard->team_id === $user->currentTeam?->id;
    }

    public function delete(User $user, BrandsIntakeBoard $intakeBoard): bool
    {
        return $intakeBoard->team_id === $user->currentTeam?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }
}

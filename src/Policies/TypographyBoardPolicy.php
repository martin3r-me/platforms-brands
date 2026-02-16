<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsTypographyBoard;

class TypographyBoardPolicy
{
    public function view(User $user, BrandsTypographyBoard $typographyBoard): bool
    {
        return $typographyBoard->team_id === $user->currentTeam?->id;
    }

    public function update(User $user, BrandsTypographyBoard $typographyBoard): bool
    {
        return $typographyBoard->team_id === $user->currentTeam?->id;
    }

    public function delete(User $user, BrandsTypographyBoard $typographyBoard): bool
    {
        return $typographyBoard->team_id === $user->currentTeam?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }
}

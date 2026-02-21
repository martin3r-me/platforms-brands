<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsCtaBoard;

class CtaBoardPolicy
{
    public function view(User $user, BrandsCtaBoard $ctaBoard): bool
    {
        return $ctaBoard->team_id === $user->currentTeam?->id;
    }

    public function update(User $user, BrandsCtaBoard $ctaBoard): bool
    {
        return $ctaBoard->team_id === $user->currentTeam?->id;
    }

    public function delete(User $user, BrandsCtaBoard $ctaBoard): bool
    {
        return $ctaBoard->team_id === $user->currentTeam?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }
}

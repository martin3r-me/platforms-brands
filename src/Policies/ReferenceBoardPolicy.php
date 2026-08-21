<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsReferenceBoard;

class ReferenceBoardPolicy
{
    public function view(User $user, BrandsReferenceBoard $referenceBoard): bool
    {
        return $referenceBoard->team_id === $user->currentTeam?->id;
    }

    public function update(User $user, BrandsReferenceBoard $referenceBoard): bool
    {
        return $referenceBoard->team_id === $user->currentTeam?->id;
    }

    public function delete(User $user, BrandsReferenceBoard $referenceBoard): bool
    {
        return $referenceBoard->team_id === $user->currentTeam?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }
}

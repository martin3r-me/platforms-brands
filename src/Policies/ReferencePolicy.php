<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsReference;

class ReferencePolicy
{
    public function view(User $user, BrandsReference $reference): bool
    {
        return $reference->referenceBoard?->team_id === $user->currentTeam?->id;
    }

    public function update(User $user, BrandsReference $reference): bool
    {
        return $reference->referenceBoard?->team_id === $user->currentTeam?->id;
    }

    public function delete(User $user, BrandsReference $reference): bool
    {
        return $reference->referenceBoard?->team_id === $user->currentTeam?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }
}

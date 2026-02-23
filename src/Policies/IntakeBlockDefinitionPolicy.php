<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsIntakeBlockDefinition;

class IntakeBlockDefinitionPolicy
{
    public function view(User $user, BrandsIntakeBlockDefinition $blockDefinition): bool
    {
        return $blockDefinition->team_id === $user->currentTeam?->id;
    }

    public function update(User $user, BrandsIntakeBlockDefinition $blockDefinition): bool
    {
        return $blockDefinition->team_id === $user->currentTeam?->id;
    }

    public function delete(User $user, BrandsIntakeBlockDefinition $blockDefinition): bool
    {
        return $blockDefinition->team_id === $user->currentTeam?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }
}

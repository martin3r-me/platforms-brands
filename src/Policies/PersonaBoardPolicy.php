<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsPersonaBoard;

class PersonaBoardPolicy
{
    public function view(User $user, BrandsPersonaBoard $personaBoard): bool
    {
        return $personaBoard->team_id === $user->currentTeam?->id;
    }

    public function update(User $user, BrandsPersonaBoard $personaBoard): bool
    {
        return $personaBoard->team_id === $user->currentTeam?->id;
    }

    public function delete(User $user, BrandsPersonaBoard $personaBoard): bool
    {
        return $personaBoard->team_id === $user->currentTeam?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }
}

<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsPersona;
use Platform\Brands\Models\BrandsPersonaBoard;

class PersonaPolicy
{
    public function view(User $user, BrandsPersona $persona): bool
    {
        $board = $persona->personaBoard;
        if (!$board) {
            return false;
        }

        return $user->current_team_id === $board->team_id;
    }

    public function create(User $user, BrandsPersonaBoard $board): bool
    {
        return $user->current_team_id === $board->team_id;
    }

    public function update(User $user, BrandsPersona $persona): bool
    {
        $board = $persona->personaBoard;
        if (!$board) {
            return false;
        }

        return $user->current_team_id === $board->team_id;
    }

    public function delete(User $user, BrandsPersona $persona): bool
    {
        $board = $persona->personaBoard;
        if (!$board) {
            return false;
        }

        return $user->current_team_id === $board->team_id;
    }
}

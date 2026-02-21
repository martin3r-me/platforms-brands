<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsCiBoardColor;
use Platform\Brands\Models\BrandsCiBoard;

class CiBoardColorPolicy
{
    public function view(User $user, BrandsCiBoardColor $color): bool
    {
        // Farben können nur gesehen werden, wenn das zugehörige CI Board sichtbar ist
        $ciBoard = $color->ciBoard;
        if (!$ciBoard) {
            return false;
        }
        
        return $user->currentTeam?->id === $ciBoard->team_id;
    }

    public function create(User $user, BrandsCiBoard $ciBoard): bool
    {
        // Farben können nur erstellt werden, wenn das CI Board bearbeitet werden kann
        return $user->currentTeam?->id === $ciBoard->team_id;
    }

    public function update(User $user, BrandsCiBoardColor $color): bool
    {
        // Farben können nur bearbeitet werden, wenn das zugehörige CI Board bearbeitet werden kann
        $ciBoard = $color->ciBoard;
        if (!$ciBoard) {
            return false;
        }
        
        return $user->currentTeam?->id === $ciBoard->team_id;
    }

    public function delete(User $user, BrandsCiBoardColor $color): bool
    {
        // Farben können nur gelöscht werden, wenn das zugehörige CI Board bearbeitet werden kann
        $ciBoard = $color->ciBoard;
        if (!$ciBoard) {
            return false;
        }
        
        return $user->currentTeam?->id === $ciBoard->team_id;
    }
}

<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsCiBoard;

class CiBoardPolicy
{
    /**
     * Darf der User dieses CI Board sehen?
     */
    public function view(User $user, BrandsCiBoard $ciBoard): bool
    {
        // User muss im selben Team sein und Zugriff auf die Marke haben
        return $ciBoard->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User dieses CI Board bearbeiten?
     */
    public function update(User $user, BrandsCiBoard $ciBoard): bool
    {
        // User muss im selben Team sein
        return $ciBoard->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User dieses CI Board löschen?
     */
    public function delete(User $user, BrandsCiBoard $ciBoard): bool
    {
        // Nur Team-Mitglied im selben Team darf löschen
        return $ciBoard->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User ein CI Board erstellen?
     */
    public function create(User $user): bool
    {
        // Jeder Team-Mitglied kann CI Boards erstellen
        return $user->currentTeam !== null;
    }
}

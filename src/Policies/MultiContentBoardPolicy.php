<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsMultiContentBoard;

class MultiContentBoardPolicy
{
    /**
     * Darf der User dieses Multi-Content-Board sehen?
     */
    public function view(User $user, BrandsMultiContentBoard $multiContentBoard): bool
    {
        // User muss im selben Team sein und Zugriff auf die Marke haben
        return $multiContentBoard->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User dieses Multi-Content-Board bearbeiten?
     */
    public function update(User $user, BrandsMultiContentBoard $multiContentBoard): bool
    {
        // User muss im selben Team sein
        return $multiContentBoard->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User dieses Multi-Content-Board löschen?
     */
    public function delete(User $user, BrandsMultiContentBoard $multiContentBoard): bool
    {
        // Nur Team-Mitglied im selben Team darf löschen
        return $multiContentBoard->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User ein Multi-Content-Board erstellen?
     */
    public function create(User $user): bool
    {
        // Jeder Team-Mitglied kann Multi-Content-Boards erstellen
        return $user->currentTeam !== null;
    }
}

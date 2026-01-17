<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsContentBoard;

class ContentBoardPolicy
{
    /**
     * Darf der User dieses Content Board sehen?
     */
    public function view(User $user, BrandsContentBoard $contentBoard): bool
    {
        // User muss im selben Team sein und Zugriff auf die Marke haben
        return $contentBoard->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User dieses Content Board bearbeiten?
     */
    public function update(User $user, BrandsContentBoard $contentBoard): bool
    {
        // User muss im selben Team sein
        return $contentBoard->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User dieses Content Board löschen?
     */
    public function delete(User $user, BrandsContentBoard $contentBoard): bool
    {
        // Nur Team-Mitglied im selben Team darf löschen
        return $contentBoard->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User ein Content Board erstellen?
     */
    public function create(User $user): bool
    {
        // Jeder Team-Mitglied kann Content Boards erstellen
        return $user->currentTeam !== null;
    }
}

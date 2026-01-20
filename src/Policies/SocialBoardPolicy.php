<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsSocialBoard;

class SocialBoardPolicy
{
    /**
     * Darf der User dieses Social Board sehen?
     */
    public function view(User $user, BrandsSocialBoard $socialBoard): bool
    {
        // User muss im selben Team sein und Zugriff auf die Marke haben
        return $socialBoard->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User dieses Social Board bearbeiten?
     */
    public function update(User $user, BrandsSocialBoard $socialBoard): bool
    {
        // User muss im selben Team sein
        return $socialBoard->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User dieses Social Board löschen?
     */
    public function delete(User $user, BrandsSocialBoard $socialBoard): bool
    {
        // Nur Team-Mitglied im selben Team darf löschen
        return $socialBoard->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User ein Social Board erstellen?
     */
    public function create(User $user): bool
    {
        // Jeder Team-Mitglied kann Social Boards erstellen
        return $user->currentTeam !== null;
    }
}

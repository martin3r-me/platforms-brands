<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\InstagramAccount;

class InstagramAccountPolicy
{
    /**
     * Darf der User diesen Instagram Account sehen?
     */
    public function view(User $user, InstagramAccount $instagramAccount): bool
    {
        // User muss im selben Team sein
        return $instagramAccount->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User diesen Instagram Account bearbeiten?
     */
    public function update(User $user, InstagramAccount $instagramAccount): bool
    {
        // User muss im selben Team sein
        return $instagramAccount->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User diesen Instagram Account löschen?
     */
    public function delete(User $user, InstagramAccount $instagramAccount): bool
    {
        // Nur Team-Mitglied im selben Team darf löschen
        return $instagramAccount->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User einen Instagram Account erstellen?
     */
    public function create(User $user): bool
    {
        // Jeder Team-Mitglied kann Instagram Accounts erstellen
        return $user->currentTeam !== null;
    }
}

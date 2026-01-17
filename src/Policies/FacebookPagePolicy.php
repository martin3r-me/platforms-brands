<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsFacebookPage;

class FacebookPagePolicy
{
    /**
     * Darf der User diese Facebook Page sehen?
     */
    public function view(User $user, BrandsFacebookPage $facebookPage): bool
    {
        // User muss im selben Team sein
        return $facebookPage->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User diese Facebook Page bearbeiten?
     */
    public function update(User $user, BrandsFacebookPage $facebookPage): bool
    {
        // User muss im selben Team sein
        return $facebookPage->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User diese Facebook Page löschen?
     */
    public function delete(User $user, BrandsFacebookPage $facebookPage): bool
    {
        // Nur Team-Mitglied im selben Team darf löschen
        return $facebookPage->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User eine Facebook Page erstellen?
     */
    public function create(User $user): bool
    {
        // Jeder Team-Mitglied kann Facebook Pages erstellen
        return $user->currentTeam !== null;
    }
}

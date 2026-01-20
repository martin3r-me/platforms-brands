<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsSocialCard;

class SocialCardPolicy
{
    /**
     * Darf der User diese Social Card sehen?
     */
    public function view(User $user, BrandsSocialCard $socialCard): bool
    {
        // User muss im selben Team sein
        return $socialCard->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User diese Social Card bearbeiten?
     */
    public function update(User $user, BrandsSocialCard $socialCard): bool
    {
        // User muss im selben Team sein
        return $socialCard->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User diese Social Card löschen?
     */
    public function delete(User $user, BrandsSocialCard $socialCard): bool
    {
        // Nur Team-Mitglied im selben Team darf löschen
        return $socialCard->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User eine Social Card erstellen?
     */
    public function create(User $user): bool
    {
        // Jeder Team-Mitglied kann Social Cards erstellen
        return $user->currentTeam !== null;
    }
}

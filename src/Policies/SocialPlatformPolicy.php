<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsSocialPlatform;

class SocialPlatformPolicy
{
    /**
     * Darf der User diese Plattform sehen?
     */
    public function view(User $user, BrandsSocialPlatform $platform): bool
    {
        // Globale Plattformen (team_id = null) sind für alle sichtbar
        if ($platform->team_id === null) {
            return true;
        }

        return $platform->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User diese Plattform bearbeiten?
     */
    public function update(User $user, BrandsSocialPlatform $platform): bool
    {
        // Globale Plattformen (team_id = null) dürfen von jedem Team-Mitglied bearbeitet werden
        if ($platform->team_id === null) {
            return $user->currentTeam !== null;
        }

        return $platform->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User diese Plattform löschen?
     */
    public function delete(User $user, BrandsSocialPlatform $platform): bool
    {
        // Globale Plattformen (team_id = null) dürfen von jedem Team-Mitglied gelöscht werden
        if ($platform->team_id === null) {
            return $user->currentTeam !== null;
        }

        return $platform->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User eine Plattform erstellen?
     */
    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }
}

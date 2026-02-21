<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsSocialPlatformFormat;

class SocialPlatformFormatPolicy
{
    /**
     * Darf der User dieses Format sehen?
     */
    public function view(User $user, BrandsSocialPlatformFormat $format): bool
    {
        // Globale Formate (team_id = null) sind für alle sichtbar
        if ($format->team_id === null) {
            return true;
        }

        return $format->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User dieses Format bearbeiten?
     */
    public function update(User $user, BrandsSocialPlatformFormat $format): bool
    {
        if ($format->team_id === null) {
            return $user->currentTeam !== null;
        }

        return $format->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User dieses Format löschen?
     */
    public function delete(User $user, BrandsSocialPlatformFormat $format): bool
    {
        if ($format->team_id === null) {
            return $user->currentTeam !== null;
        }

        return $format->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User ein Format erstellen?
     */
    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }
}

<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsBrand;

class BrandPolicy
{
    /**
     * Darf der User diese Marke sehen?
     */
    public function view(User $user, BrandsBrand $brand): bool
    {
        // User muss im selben Team sein
        return $brand->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User diese Marke bearbeiten?
     */
    public function update(User $user, BrandsBrand $brand): bool
    {
        // User muss im selben Team sein
        return $brand->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User diese Marke löschen?
     */
    public function delete(User $user, BrandsBrand $brand): bool
    {
        // Nur Ersteller oder Team-Admin darf löschen
        return $brand->user_id === $user->id || 
               $brand->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User eine Marke erstellen?
     */
    public function create(User $user): bool
    {
        // Jeder Team-Mitglied kann Marken erstellen
        return $user->currentTeam !== null;
    }
}

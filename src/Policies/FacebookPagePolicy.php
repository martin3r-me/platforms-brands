<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Integrations\Models\IntegrationsFacebookPage;

class FacebookPagePolicy
{
    /**
     * Darf der User diese Facebook Page sehen?
     */
    public function view(User $user, IntegrationsFacebookPage $facebookPage): bool
    {
        // User muss Owner sein
        return $facebookPage->user_id === $user->id;
    }

    /**
     * Darf der User diese Facebook Page bearbeiten?
     */
    public function update(User $user, IntegrationsFacebookPage $facebookPage): bool
    {
        // User muss Owner sein
        return $facebookPage->user_id === $user->id;
    }

    /**
     * Darf der User diese Facebook Page löschen?
     */
    public function delete(User $user, IntegrationsFacebookPage $facebookPage): bool
    {
        // User muss Owner sein
        return $facebookPage->user_id === $user->id;
    }

    /**
     * Darf der User eine Facebook Page erstellen?
     */
    public function create(User $user): bool
    {
        // Jeder User kann Facebook Pages erstellen
        return true;
    }
}

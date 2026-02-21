<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsCta;

class CtaPolicy
{
    /**
     * Darf der User diesen CTA sehen?
     */
    public function view(User $user, BrandsCta $cta): bool
    {
        return $cta->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User diesen CTA bearbeiten?
     */
    public function update(User $user, BrandsCta $cta): bool
    {
        return $cta->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User diesen CTA löschen?
     */
    public function delete(User $user, BrandsCta $cta): bool
    {
        return $cta->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User einen CTA erstellen?
     */
    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }
}

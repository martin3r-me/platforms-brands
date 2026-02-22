<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsSocialCardContract;

class SocialCardContractPolicy
{
    /**
     * Darf der User diesen Contract sehen?
     */
    public function view(User $user, BrandsSocialCardContract $contract): bool
    {
        return $contract->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User diesen Contract bearbeiten?
     */
    public function update(User $user, BrandsSocialCardContract $contract): bool
    {
        return $contract->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User diesen Contract löschen?
     */
    public function delete(User $user, BrandsSocialCardContract $contract): bool
    {
        return $contract->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User Contracts erstellen?
     */
    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }
}

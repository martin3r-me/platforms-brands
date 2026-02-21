<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsSeoKeywordPosition;

class SeoKeywordPositionPolicy
{
    public function view(User $user, BrandsSeoKeywordPosition $position): bool
    {
        return $position->seoKeyword?->team_id === $user->currentTeam?->id;
    }

    public function update(User $user, BrandsSeoKeywordPosition $position): bool
    {
        return $position->seoKeyword?->team_id === $user->currentTeam?->id;
    }

    public function delete(User $user, BrandsSeoKeywordPosition $position): bool
    {
        return $position->seoKeyword?->team_id === $user->currentTeam?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }
}

<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsSeoKeywordCompetitor;

class SeoKeywordCompetitorPolicy
{
    public function view(User $user, BrandsSeoKeywordCompetitor $competitor): bool
    {
        return $competitor->seoKeyword?->team_id === $user->currentTeam?->id;
    }

    public function update(User $user, BrandsSeoKeywordCompetitor $competitor): bool
    {
        return $competitor->seoKeyword?->team_id === $user->currentTeam?->id;
    }

    public function delete(User $user, BrandsSeoKeywordCompetitor $competitor): bool
    {
        return $competitor->seoKeyword?->team_id === $user->currentTeam?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }
}

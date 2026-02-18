<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsSeoKeyword;

class SeoKeywordPolicy
{
    public function view(User $user, BrandsSeoKeyword $seoKeyword): bool
    {
        return $seoKeyword->team_id === $user->currentTeam?->id;
    }

    public function update(User $user, BrandsSeoKeyword $seoKeyword): bool
    {
        return $seoKeyword->team_id === $user->currentTeam?->id;
    }

    public function delete(User $user, BrandsSeoKeyword $seoKeyword): bool
    {
        return $seoKeyword->team_id === $user->currentTeam?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }
}

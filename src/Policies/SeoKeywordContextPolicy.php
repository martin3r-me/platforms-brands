<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsSeoKeywordContext;

class SeoKeywordContextPolicy
{
    public function view(User $user, BrandsSeoKeywordContext $context): bool
    {
        return $context->seoKeyword?->team_id === $user->currentTeam?->id;
    }

    public function update(User $user, BrandsSeoKeywordContext $context): bool
    {
        return $context->seoKeyword?->team_id === $user->currentTeam?->id;
    }

    public function delete(User $user, BrandsSeoKeywordContext $context): bool
    {
        return $context->seoKeyword?->team_id === $user->currentTeam?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }
}

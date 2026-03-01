<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsContentBriefBoard;

class ContentBriefBoardPolicy
{
    public function view(User $user, BrandsContentBriefBoard $contentBriefBoard): bool
    {
        return $contentBriefBoard->team_id === $user->currentTeam?->id;
    }

    public function update(User $user, BrandsContentBriefBoard $contentBriefBoard): bool
    {
        return $contentBriefBoard->team_id === $user->currentTeam?->id;
    }

    public function delete(User $user, BrandsContentBriefBoard $contentBriefBoard): bool
    {
        return $contentBriefBoard->team_id === $user->currentTeam?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }
}

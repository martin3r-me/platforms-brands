<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsGuidelineBoard;

class GuidelineBoardPolicy
{
    public function view(User $user, BrandsGuidelineBoard $guidelineBoard): bool
    {
        return $guidelineBoard->team_id === $user->currentTeam?->id;
    }

    public function update(User $user, BrandsGuidelineBoard $guidelineBoard): bool
    {
        return $guidelineBoard->team_id === $user->currentTeam?->id;
    }

    public function delete(User $user, BrandsGuidelineBoard $guidelineBoard): bool
    {
        return $guidelineBoard->team_id === $user->currentTeam?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }
}

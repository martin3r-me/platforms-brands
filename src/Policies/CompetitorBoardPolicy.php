<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsCompetitorBoard;

class CompetitorBoardPolicy
{
    public function view(User $user, BrandsCompetitorBoard $competitorBoard): bool
    {
        return $competitorBoard->team_id === $user->currentTeam?->id;
    }

    public function update(User $user, BrandsCompetitorBoard $competitorBoard): bool
    {
        return $competitorBoard->team_id === $user->currentTeam?->id;
    }

    public function delete(User $user, BrandsCompetitorBoard $competitorBoard): bool
    {
        return $competitorBoard->team_id === $user->currentTeam?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }
}

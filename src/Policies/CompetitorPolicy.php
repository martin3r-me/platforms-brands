<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsCompetitor;
use Platform\Brands\Models\BrandsCompetitorBoard;

class CompetitorPolicy
{
    public function view(User $user, BrandsCompetitor $competitor): bool
    {
        $board = $competitor->competitorBoard;
        if (!$board) {
            return false;
        }

        return $user->current_team_id === $board->team_id;
    }

    public function create(User $user, BrandsCompetitorBoard $board): bool
    {
        return $user->current_team_id === $board->team_id;
    }

    public function update(User $user, BrandsCompetitor $competitor): bool
    {
        $board = $competitor->competitorBoard;
        if (!$board) {
            return false;
        }

        return $user->current_team_id === $board->team_id;
    }

    public function delete(User $user, BrandsCompetitor $competitor): bool
    {
        $board = $competitor->competitorBoard;
        if (!$board) {
            return false;
        }

        return $user->current_team_id === $board->team_id;
    }
}

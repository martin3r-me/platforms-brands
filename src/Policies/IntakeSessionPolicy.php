<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsIntakeSession;

class IntakeSessionPolicy
{
    public function view(User $user, BrandsIntakeSession $session): bool
    {
        $board = $session->intakeBoard;
        return $board && $board->team_id === $user->currentTeam?->id;
    }

    public function update(User $user, BrandsIntakeSession $session): bool
    {
        $board = $session->intakeBoard;
        return $board && $board->team_id === $user->currentTeam?->id;
    }

    public function delete(User $user, BrandsIntakeSession $session): bool
    {
        $board = $session->intakeBoard;
        return $board && $board->team_id === $user->currentTeam?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }
}

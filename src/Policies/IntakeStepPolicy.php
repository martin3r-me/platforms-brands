<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsIntakeStep;

class IntakeStepPolicy
{
    public function view(User $user, BrandsIntakeStep $step): bool
    {
        $board = $step->boardBlock?->intakeBoard;
        return $board && $board->team_id === $user->currentTeam?->id;
    }

    public function update(User $user, BrandsIntakeStep $step): bool
    {
        $board = $step->boardBlock?->intakeBoard;
        return $board && $board->team_id === $user->currentTeam?->id;
    }

    public function delete(User $user, BrandsIntakeStep $step): bool
    {
        $board = $step->boardBlock?->intakeBoard;
        return $board && $board->team_id === $user->currentTeam?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }
}

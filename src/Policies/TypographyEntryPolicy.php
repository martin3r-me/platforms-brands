<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsTypographyEntry;
use Platform\Brands\Models\BrandsTypographyBoard;

class TypographyEntryPolicy
{
    public function view(User $user, BrandsTypographyEntry $entry): bool
    {
        $board = $entry->typographyBoard;
        if (!$board) {
            return false;
        }

        return $user->current_team_id === $board->team_id;
    }

    public function create(User $user, BrandsTypographyBoard $board): bool
    {
        return $user->current_team_id === $board->team_id;
    }

    public function update(User $user, BrandsTypographyEntry $entry): bool
    {
        $board = $entry->typographyBoard;
        if (!$board) {
            return false;
        }

        return $user->current_team_id === $board->team_id;
    }

    public function delete(User $user, BrandsTypographyEntry $entry): bool
    {
        $board = $entry->typographyBoard;
        if (!$board) {
            return false;
        }

        return $user->current_team_id === $board->team_id;
    }
}

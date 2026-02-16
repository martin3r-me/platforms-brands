<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsToneOfVoiceDimension;
use Platform\Brands\Models\BrandsToneOfVoiceBoard;

class ToneOfVoiceDimensionPolicy
{
    public function view(User $user, BrandsToneOfVoiceDimension $dimension): bool
    {
        $board = $dimension->toneOfVoiceBoard;
        if (!$board) {
            return false;
        }

        return $user->current_team_id === $board->team_id;
    }

    public function create(User $user, BrandsToneOfVoiceBoard $board): bool
    {
        return $user->current_team_id === $board->team_id;
    }

    public function update(User $user, BrandsToneOfVoiceDimension $dimension): bool
    {
        $board = $dimension->toneOfVoiceBoard;
        if (!$board) {
            return false;
        }

        return $user->current_team_id === $board->team_id;
    }

    public function delete(User $user, BrandsToneOfVoiceDimension $dimension): bool
    {
        $board = $dimension->toneOfVoiceBoard;
        if (!$board) {
            return false;
        }

        return $user->current_team_id === $board->team_id;
    }
}

<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsToneOfVoiceEntry;
use Platform\Brands\Models\BrandsToneOfVoiceBoard;

class ToneOfVoiceEntryPolicy
{
    public function view(User $user, BrandsToneOfVoiceEntry $entry): bool
    {
        $board = $entry->toneOfVoiceBoard;
        if (!$board) {
            return false;
        }

        return $user->current_team_id === $board->team_id;
    }

    public function create(User $user, BrandsToneOfVoiceBoard $board): bool
    {
        return $user->current_team_id === $board->team_id;
    }

    public function update(User $user, BrandsToneOfVoiceEntry $entry): bool
    {
        $board = $entry->toneOfVoiceBoard;
        if (!$board) {
            return false;
        }

        return $user->current_team_id === $board->team_id;
    }

    public function delete(User $user, BrandsToneOfVoiceEntry $entry): bool
    {
        $board = $entry->toneOfVoiceBoard;
        if (!$board) {
            return false;
        }

        return $user->current_team_id === $board->team_id;
    }
}

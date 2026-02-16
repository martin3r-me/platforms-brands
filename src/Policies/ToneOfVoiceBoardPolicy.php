<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsToneOfVoiceBoard;

class ToneOfVoiceBoardPolicy
{
    public function view(User $user, BrandsToneOfVoiceBoard $toneOfVoiceBoard): bool
    {
        return $toneOfVoiceBoard->team_id === $user->currentTeam?->id;
    }

    public function update(User $user, BrandsToneOfVoiceBoard $toneOfVoiceBoard): bool
    {
        return $toneOfVoiceBoard->team_id === $user->currentTeam?->id;
    }

    public function delete(User $user, BrandsToneOfVoiceBoard $toneOfVoiceBoard): bool
    {
        return $toneOfVoiceBoard->team_id === $user->currentTeam?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }
}

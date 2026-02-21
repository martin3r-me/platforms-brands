<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsGuidelineChapter;
use Platform\Brands\Models\BrandsGuidelineBoard;

class GuidelineChapterPolicy
{
    public function view(User $user, BrandsGuidelineChapter $chapter): bool
    {
        $board = $chapter->guidelineBoard;
        if (!$board) {
            return false;
        }

        return $user->currentTeam?->id === $board->team_id;
    }

    public function create(User $user, BrandsGuidelineBoard $board): bool
    {
        return $user->currentTeam?->id === $board->team_id;
    }

    public function update(User $user, BrandsGuidelineChapter $chapter): bool
    {
        $board = $chapter->guidelineBoard;
        if (!$board) {
            return false;
        }

        return $user->currentTeam?->id === $board->team_id;
    }

    public function delete(User $user, BrandsGuidelineChapter $chapter): bool
    {
        $board = $chapter->guidelineBoard;
        if (!$board) {
            return false;
        }

        return $user->currentTeam?->id === $board->team_id;
    }
}

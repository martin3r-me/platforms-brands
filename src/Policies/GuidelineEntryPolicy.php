<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsGuidelineEntry;
use Platform\Brands\Models\BrandsGuidelineChapter;

class GuidelineEntryPolicy
{
    public function view(User $user, BrandsGuidelineEntry $entry): bool
    {
        $board = $entry->guidelineChapter?->guidelineBoard;
        if (!$board) {
            return false;
        }

        return $user->currentTeam?->id === $board->team_id;
    }

    public function create(User $user, BrandsGuidelineChapter $chapter): bool
    {
        $board = $chapter->guidelineBoard;
        if (!$board) {
            return false;
        }

        return $user->currentTeam?->id === $board->team_id;
    }

    public function update(User $user, BrandsGuidelineEntry $entry): bool
    {
        $board = $entry->guidelineChapter?->guidelineBoard;
        if (!$board) {
            return false;
        }

        return $user->currentTeam?->id === $board->team_id;
    }

    public function delete(User $user, BrandsGuidelineEntry $entry): bool
    {
        $board = $entry->guidelineChapter?->guidelineBoard;
        if (!$board) {
            return false;
        }

        return $user->currentTeam?->id === $board->team_id;
    }
}

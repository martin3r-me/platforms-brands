<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsLogoVariant;
use Platform\Brands\Models\BrandsLogoBoard;

class LogoVariantPolicy
{
    public function view(User $user, BrandsLogoVariant $variant): bool
    {
        $board = $variant->logoBoard;
        if (!$board) {
            return false;
        }

        return $user->current_team_id === $board->team_id;
    }

    public function create(User $user, BrandsLogoBoard $board): bool
    {
        return $user->current_team_id === $board->team_id;
    }

    public function update(User $user, BrandsLogoVariant $variant): bool
    {
        $board = $variant->logoBoard;
        if (!$board) {
            return false;
        }

        return $user->current_team_id === $board->team_id;
    }

    public function delete(User $user, BrandsLogoVariant $variant): bool
    {
        $board = $variant->logoBoard;
        if (!$board) {
            return false;
        }

        return $user->current_team_id === $board->team_id;
    }
}

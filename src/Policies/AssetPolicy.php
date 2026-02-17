<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsAsset;
use Platform\Brands\Models\BrandsAssetBoard;

class AssetPolicy
{
    public function view(User $user, BrandsAsset $asset): bool
    {
        $board = $asset->assetBoard;
        if (!$board) {
            return false;
        }

        return $user->current_team_id === $board->team_id;
    }

    public function create(User $user, BrandsAssetBoard $board): bool
    {
        return $user->current_team_id === $board->team_id;
    }

    public function update(User $user, BrandsAsset $asset): bool
    {
        $board = $asset->assetBoard;
        if (!$board) {
            return false;
        }

        return $user->current_team_id === $board->team_id;
    }

    public function delete(User $user, BrandsAsset $asset): bool
    {
        $board = $asset->assetBoard;
        if (!$board) {
            return false;
        }

        return $user->current_team_id === $board->team_id;
    }
}

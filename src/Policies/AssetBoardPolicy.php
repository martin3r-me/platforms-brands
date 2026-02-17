<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Brands\Models\BrandsAssetBoard;

class AssetBoardPolicy
{
    public function view(User $user, BrandsAssetBoard $assetBoard): bool
    {
        return $assetBoard->team_id === $user->currentTeam?->id;
    }

    public function update(User $user, BrandsAssetBoard $assetBoard): bool
    {
        return $assetBoard->team_id === $user->currentTeam?->id;
    }

    public function delete(User $user, BrandsAssetBoard $assetBoard): bool
    {
        return $assetBoard->team_id === $user->currentTeam?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }
}

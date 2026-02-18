<?php

namespace Platform\Brands\Services;

use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsSeoBoard;
use Platform\Core\Models\User;

class SeoBoardService
{
    public function createBoard(BrandsBrand $brand, User $user, array $data): BrandsSeoBoard
    {
        return BrandsSeoBoard::create([
            'name' => $data['name'] ?? 'Neues SEO Board',
            'description' => $data['description'] ?? null,
            'brand_id' => $brand->id,
            'user_id' => $user->id,
            'team_id' => $brand->team_id,
            'budget_limit_cents' => $data['budget_limit_cents'] ?? null,
            'refresh_interval_days' => $data['refresh_interval_days'] ?? 30,
            'dataforseo_config' => $data['dataforseo_config'] ?? null,
        ]);
    }

    public function updateBoard(BrandsSeoBoard $board, array $data): BrandsSeoBoard
    {
        $updateData = [];

        foreach (['name', 'description', 'budget_limit_cents', 'refresh_interval_days', 'dataforseo_config'] as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (isset($data['done'])) {
            $updateData['done'] = $data['done'];
            $updateData['done_at'] = $data['done'] ? now() : null;
        }

        if (!empty($updateData)) {
            $board->update($updateData);
        }

        return $board->fresh();
    }

    public function deleteBoard(BrandsSeoBoard $board): void
    {
        $board->delete();
    }

    public function getBoardWithFullData(BrandsSeoBoard $board): BrandsSeoBoard
    {
        return $board->load([
            'brand',
            'user',
            'team',
            'keywordClusters.keywords',
            'keywords',
            'budgetLogs',
        ]);
    }
}

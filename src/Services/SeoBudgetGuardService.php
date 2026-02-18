<?php

namespace Platform\Brands\Services;

use Platform\Brands\Models\BrandsSeoBoard;
use Platform\Brands\Models\BrandsSeoBudgetLog;
use Platform\Core\Models\User;

class SeoBudgetGuardService
{
    public function canFetch(BrandsSeoBoard $board, int $estimatedCostCents): bool
    {
        if ($board->budget_limit_cents === null) {
            return true;
        }

        return ($board->budget_spent_cents + $estimatedCostCents) <= $board->budget_limit_cents;
    }

    public function recordCost(BrandsSeoBoard $board, string $action, int $count, int $costCents, ?User $user = null): BrandsSeoBudgetLog
    {
        $log = BrandsSeoBudgetLog::create([
            'seo_board_id' => $board->id,
            'action' => $action,
            'keywords_count' => $count,
            'cost_cents' => $costCents,
            'user_id' => $user?->id,
            'fetched_at' => now(),
        ]);

        $board->increment('budget_spent_cents', $costCents);

        return $log;
    }

    public function resetMonthlyBudget(BrandsSeoBoard $board): void
    {
        $board->update([
            'budget_spent_cents' => 0,
            'budget_reset_at' => now(),
        ]);
    }

    public function getBudgetSummary(BrandsSeoBoard $board): array
    {
        return [
            'limit_cents' => $board->budget_limit_cents,
            'spent_cents' => $board->budget_spent_cents,
            'remaining_cents' => $board->budget_remaining_cents,
            'percentage' => $board->budget_percentage,
            'reset_at' => $board->budget_reset_at?->toIso8601String(),
        ];
    }
}

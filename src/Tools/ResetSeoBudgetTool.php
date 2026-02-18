<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSeoBoard;
use Platform\Brands\Services\SeoBudgetGuardService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class ResetSeoBudgetTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.seo_budget.RESET';
    }

    public function getDescription(): string
    {
        return 'POST /brands/seo_boards/{seo_board_id}/budget/reset - Setzt das verbrauchte Budget eines SEO Boards zurück. REST-Parameter: seo_board_id (required, integer).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'seo_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des SEO Boards (ERFORDERLICH).'
                ],
            ],
            'required' => ['seo_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $seoBoardId = $arguments['seo_board_id'] ?? null;
            if (!$seoBoardId) {
                return ToolResult::error('VALIDATION_ERROR', 'seo_board_id ist erforderlich.');
            }

            $seoBoard = BrandsSeoBoard::find($seoBoardId);
            if (!$seoBoard) {
                return ToolResult::error('SEO_BOARD_NOT_FOUND', 'Das angegebene SEO Board wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $seoBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst das Budget dieses SEO Boards nicht zurücksetzen (Policy).');
            }

            $previousSpent = $seoBoard->budget_spent_cents;

            $budgetGuard = app(SeoBudgetGuardService::class);
            $budgetGuard->resetMonthlyBudget($seoBoard);

            $seoBoard->refresh();

            return ToolResult::success([
                'seo_board_id' => $seoBoard->id,
                'seo_board_name' => $seoBoard->name,
                'previous_spent_cents' => $previousSpent,
                'budget_spent_cents' => $seoBoard->budget_spent_cents,
                'budget_limit_cents' => $seoBoard->budget_limit_cents,
                'budget_reset_at' => $seoBoard->budget_reset_at?->toIso8601String(),
                'message' => "Budget für '{$seoBoard->name}' erfolgreich zurückgesetzt. Vorheriger Verbrauch: {$previousSpent} Cents."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Zurücksetzen des Budgets: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'seo_budget', 'reset'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['updates'],
        ];
    }
}

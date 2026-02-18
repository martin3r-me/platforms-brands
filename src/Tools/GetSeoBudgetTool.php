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

class GetSeoBudgetTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.seo_budget.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/seo_boards/{seo_board_id}/budget - Zeigt Budget-Status eines SEO Boards an. REST-Parameter: seo_board_id (required, integer).';
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
                Gate::forUser($context->user)->authorize('view', $seoBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses SEO Board (Policy).');
            }

            $budgetGuard = app(SeoBudgetGuardService::class);
            $summary = $budgetGuard->getBudgetSummary($seoBoard);

            $recentLogs = $seoBoard->budgetLogs()
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(function ($log) {
                    return [
                        'action' => $log->action,
                        'keywords_count' => $log->keywords_count,
                        'cost_cents' => $log->cost_cents,
                        'fetched_at' => $log->fetched_at?->toIso8601String(),
                    ];
                })->toArray();

            return ToolResult::success([
                'seo_board_id' => $seoBoard->id,
                'seo_board_name' => $seoBoard->name,
                'budget' => $summary,
                'recent_logs' => $recentLogs,
                'message' => $summary['limit_cents'] !== null
                    ? "Budget: {$summary['spent_cents']}/{$summary['limit_cents']} Cents verbraucht ({$summary['percentage']}%)."
                    : 'Kein Budget-Limit gesetzt (unbegrenzt).'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Budget-Status: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'seo_budget', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}

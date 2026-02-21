<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsCompetitor;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteCompetitorTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.competitors.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/competitors/{id} - Löscht einen Wettbewerber. REST-Parameter: competitor_id (required, integer) - Wettbewerber-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'competitor_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden Wettbewerbers (ERFORDERLICH). Nutze "brands.competitors.GET" um Wettbewerber zu finden.'
                ],
            ],
            'required' => ['competitor_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'competitor_id',
                BrandsCompetitor::class,
                'COMPETITOR_NOT_FOUND',
                'Der angegebene Wettbewerber wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $competitor = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('delete', $competitor);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diesen Wettbewerber nicht löschen.');
            }

            $competitorName = $competitor->name;
            $competitorId = $competitor->id;
            $boardId = $competitor->competitor_board_id;

            $competitor->delete();

            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('brands.competitors.GET', $context->user->id, $context->team?->id);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'competitor_id' => $competitorId,
                'competitor_name' => $competitorName,
                'competitor_board_id' => $boardId,
                'message' => "Wettbewerber '{$competitorName}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Wettbewerbers: ' . $e->getMessage());
        }
    }
}

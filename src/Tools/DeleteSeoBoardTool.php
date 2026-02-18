<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsSeoBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteSeoBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.seo_boards.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/seo_boards/{id} - Löscht ein SEO Board. REST-Parameter: seo_board_id (required, integer) - SEO Board-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'seo_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden SEO Boards (ERFORDERLICH). Nutze "brands.seo_boards.GET" um SEO Boards zu finden.'
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestätigung, dass das SEO Board wirklich gelöscht werden soll.'
                ]
            ],
            'required' => ['seo_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments, $context, 'seo_board_id', BrandsSeoBoard::class,
                'SEO_BOARD_NOT_FOUND', 'Das angegebene SEO Board wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $seoBoard = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('delete', $seoBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses SEO Board nicht löschen (Policy).');
            }

            $seoBoardName = $seoBoard->name;
            $seoBoardId = $seoBoard->id;
            $brandId = $seoBoard->brand_id;
            $teamId = $seoBoard->team_id;

            $seoBoard->delete();

            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('brands.seo_boards.GET', $context->user->id, $teamId);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'seo_board_id' => $seoBoardId,
                'seo_board_name' => $seoBoardName,
                'brand_id' => $brandId,
                'message' => "SEO Board '{$seoBoardName}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des SEO Boards: ' . $e->getMessage());
        }
    }
}

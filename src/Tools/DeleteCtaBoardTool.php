<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsCtaBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteCtaBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.cta_boards.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/cta_boards/{id} - Löscht ein CTA Board. REST-Parameter: cta_board_id (required, integer) - CTA Board-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'cta_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden CTA Boards (ERFORDERLICH). Nutze "brands.cta_boards.GET" um CTA Boards zu finden.'
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestätigung, dass das CTA Board wirklich gelöscht werden soll.'
                ]
            ],
            'required' => ['cta_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments, $context, 'cta_board_id', BrandsCtaBoard::class,
                'CTA_BOARD_NOT_FOUND', 'Das angegebene CTA Board wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $ctaBoard = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('delete', $ctaBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses CTA Board nicht löschen (Policy).');
            }

            $ctaBoardName = $ctaBoard->name;
            $ctaBoardId = $ctaBoard->id;
            $brandId = $ctaBoard->brand_id;
            $teamId = $ctaBoard->team_id;

            $ctaBoard->delete();

            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('brands.cta_boards.GET', $context->user->id, $teamId);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'cta_board_id' => $ctaBoardId,
                'cta_board_name' => $ctaBoardName,
                'brand_id' => $brandId,
                'message' => "CTA Board '{$ctaBoardName}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des CTA Boards: ' . $e->getMessage());
        }
    }
}

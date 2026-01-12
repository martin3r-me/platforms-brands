<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsCiBoardColor;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteCiBoardColorTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.ci_board_colors.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/ci_board_colors/{id} - Löscht eine Farbe. REST-Parameter: color_id (required, integer) - Farb-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'color_id' => [
                    'type' => 'integer',
                    'description' => 'ID der zu löschenden Farbe (ERFORDERLICH). Nutze "brands.ci_board_colors.GET" um Farben zu finden.'
                ],
            ],
            'required' => ['color_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'color_id',
                BrandsCiBoardColor::class,
                'COLOR_NOT_FOUND',
                'Die angegebene Farbe wurde nicht gefunden.'
            );
            
            if ($validation['error']) {
                return $validation['error'];
            }
            
            $color = $validation['model'];
            
            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('delete', $color);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diese Farbe nicht löschen.');
            }

            $colorTitle = $color->title;
            $colorId = $color->id;
            $ciBoardId = $color->brand_ci_board_id;

            $color->delete();

            // Cache invalidieren
            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('brands.ci_board_colors.GET', $context->user->id, $context->user->current_team_id);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'color_id' => $colorId,
                'color_title' => $colorTitle,
                'ci_board_id' => $ciBoardId,
                'message' => "Farbe '{$colorTitle}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen der Farbe: ' . $e->getMessage());
        }
    }
}

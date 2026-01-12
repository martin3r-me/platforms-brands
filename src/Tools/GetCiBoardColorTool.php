<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsCiBoardColor;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class GetCiBoardColorTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.ci_board_color.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/ci_board_colors/{id} - Ruft eine einzelne Farbe ab. REST-Parameter: id (required, integer) - Farb-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID der Farbe. Nutze "brands.ci_board_colors.GET" um Farben zu finden.'
                ]
            ],
            'required' => ['id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            if (empty($arguments['id'])) {
                return ToolResult::error('VALIDATION_ERROR', 'Farb-ID ist erforderlich.');
            }

            $color = BrandsCiBoardColor::with('ciBoard')->find($arguments['id']);

            if (!$color) {
                return ToolResult::error('COLOR_NOT_FOUND', 'Die angegebene Farbe wurde nicht gefunden.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('view', $color);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diese Farbe.');
            }

            $data = [
                'id' => $color->id,
                'uuid' => $color->uuid,
                'title' => $color->title,
                'color' => $color->color,
                'description' => $color->description,
                'order' => $color->order,
                'ci_board_id' => $color->brand_ci_board_id,
                'ci_board_name' => $color->ciBoard->name,
                'created_at' => $color->created_at->toIso8601String(),
                'updated_at' => $color->updated_at->toIso8601String(),
            ];

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Farbe: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'ci_board_color', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}

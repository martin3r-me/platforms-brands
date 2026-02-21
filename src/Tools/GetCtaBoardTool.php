<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsCtaBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class GetCtaBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.cta_board.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/cta_boards/{id} - Ruft ein einzelnes CTA Board mit allen CTAs ab. REST-Parameter: id (required, integer) - CTA Board-ID. Nutze "brands.cta_boards.GET" um verfügbare CTA Board-IDs zu sehen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID des CTA Boards. Nutze "brands.cta_boards.GET" um verfügbare CTA Board-IDs zu sehen.'
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
                return ToolResult::error('VALIDATION_ERROR', 'CTA Board-ID ist erforderlich. Nutze "brands.cta_boards.GET" um CTA Boards zu finden.');
            }

            $ctaBoard = BrandsCtaBoard::with(['brand', 'user', 'team', 'ctas.targetPage'])
                ->find($arguments['id']);

            if (!$ctaBoard) {
                return ToolResult::error('CTA_BOARD_NOT_FOUND', 'Das angegebene CTA Board wurde nicht gefunden. Nutze "brands.cta_boards.GET" um alle verfügbaren CTA Boards zu sehen.');
            }

            try {
                Gate::forUser($context->user)->authorize('view', $ctaBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses CTA Board (Policy).');
            }

            $ctasList = $ctaBoard->ctas->map(function ($cta) {
                return [
                    'id' => $cta->id,
                    'uuid' => $cta->uuid,
                    'label' => $cta->label,
                    'description' => $cta->description,
                    'type' => $cta->type,
                    'funnel_stage' => $cta->funnel_stage,
                    'target_page_id' => $cta->target_page_id,
                    'target_page_title' => $cta->targetPage?->title ?? $cta->targetPage?->name ?? null,
                    'target_url' => $cta->target_url,
                    'is_active' => $cta->is_active,
                    'order' => $cta->order,
                ];
            })->values()->toArray();

            $data = [
                'id' => $ctaBoard->id,
                'uuid' => $ctaBoard->uuid,
                'name' => $ctaBoard->name,
                'description' => $ctaBoard->description,
                'brand_id' => $ctaBoard->brand_id,
                'brand_name' => $ctaBoard->brand->name,
                'team_id' => $ctaBoard->team_id,
                'user_id' => $ctaBoard->user_id,
                'done' => $ctaBoard->done,
                'done_at' => $ctaBoard->done_at?->toIso8601String(),
                'ctas_count' => count($ctasList),
                'ctas' => $ctasList,
                'created_at' => $ctaBoard->created_at->toIso8601String(),
            ];

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des CTA Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'cta_board', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}

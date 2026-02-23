<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsIntakeBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Abrufen eines einzelnen IntakeBoards
 */
class GetIntakeBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.intake_board.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/intake_boards/{id} - Ruft ein einzelnes Intake Board (Erhebung) ab. REST-Parameter: id (required, integer) - Intake Board-ID. Nutze "brands.intake_boards.GET" um verfügbare Intake Board-IDs zu sehen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID des Intake Boards. Nutze "brands.intake_boards.GET" um verfügbare Intake Board-IDs zu sehen.'
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
                return ToolResult::error('VALIDATION_ERROR', 'Intake Board-ID ist erforderlich. Nutze "brands.intake_boards.GET" um Intake Boards zu finden.');
            }

            // IntakeBoard holen
            $intakeBoard = BrandsIntakeBoard::with(['brand', 'user', 'team', 'boardBlocks.blockDefinition'])
                ->find($arguments['id']);

            if (!$intakeBoard) {
                return ToolResult::error('INTAKE_BOARD_NOT_FOUND', 'Das angegebene Intake Board wurde nicht gefunden. Nutze "brands.intake_boards.GET" um alle verfügbaren Intake Boards zu sehen.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('view', $intakeBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Intake Board (Policy).');
            }

            $data = [
                'id' => $intakeBoard->id,
                'uuid' => $intakeBoard->uuid,
                'name' => $intakeBoard->name,
                'description' => $intakeBoard->description,
                'status' => $intakeBoard->status,
                'is_active' => $intakeBoard->is_active,
                'public_token' => $intakeBoard->public_token,
                'public_url' => $intakeBoard->getPublicUrl(),
                'ai_personality' => $intakeBoard->ai_personality,
                'industry_context' => $intakeBoard->industry_context,
                'ai_instructions' => $intakeBoard->ai_instructions,
                'brand_id' => $intakeBoard->brand_id,
                'brand_name' => $intakeBoard->brand->name,
                'team_id' => $intakeBoard->team_id,
                'user_id' => $intakeBoard->user_id,
                'done' => $intakeBoard->done,
                'done_at' => $intakeBoard->done_at?->toIso8601String(),
                'started_at' => $intakeBoard->started_at?->toIso8601String(),
                'completed_at' => $intakeBoard->completed_at?->toIso8601String(),
                'session_count' => $intakeBoard->sessions()->count(),
                'block_count' => $intakeBoard->boardBlocks->count(),
                'created_at' => $intakeBoard->created_at->toIso8601String(),
            ];

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Intake Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'intake_board', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}

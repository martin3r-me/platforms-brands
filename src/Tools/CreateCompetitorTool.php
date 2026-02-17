<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsCompetitorBoard;
use Platform\Brands\Models\BrandsCompetitor;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateCompetitorTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.competitors.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/competitor_boards/{competitor_board_id}/competitors - Erstellt einen neuen Wettbewerber. REST-Parameter: competitor_board_id (required), name (required), logo_url, website_url, description, strengths (array), weaknesses (array), notes, position_x (0-100), position_y (0-100), is_own_brand (boolean), differentiation (array).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'competitor_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Competitor Boards (ERFORDERLICH). Nutze "brands.competitor_boards.GET" um Boards zu finden.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name des Wettbewerbers (ERFORDERLICH).'
                ],
                'logo_url' => [
                    'type' => 'string',
                    'description' => 'Logo-URL des Wettbewerbers.'
                ],
                'website_url' => [
                    'type' => 'string',
                    'description' => 'Website-URL des Wettbewerbers.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Beschreibung des Wettbewerbers.'
                ],
                'strengths' => [
                    'type' => 'array',
                    'description' => 'Stärken des Wettbewerbers als Array von Objekten [{text: string}].',
                    'items' => ['type' => 'object', 'properties' => ['text' => ['type' => 'string']]]
                ],
                'weaknesses' => [
                    'type' => 'array',
                    'description' => 'Schwächen des Wettbewerbers als Array von Objekten [{text: string}].',
                    'items' => ['type' => 'object', 'properties' => ['text' => ['type' => 'string']]]
                ],
                'notes' => [
                    'type' => 'string',
                    'description' => 'Notizen zum Wettbewerber.'
                ],
                'position_x' => [
                    'type' => 'integer',
                    'description' => 'X-Position auf der Positionierungsmatrix (0-100).'
                ],
                'position_y' => [
                    'type' => 'integer',
                    'description' => 'Y-Position auf der Positionierungsmatrix (0-100).'
                ],
                'is_own_brand' => [
                    'type' => 'boolean',
                    'description' => 'Eigene Marke markieren.'
                ],
                'differentiation' => [
                    'type' => 'array',
                    'description' => 'Differenzierungsmerkmale als Array von Objekten [{category: string, own_value: string, competitor_value: string}].',
                    'items' => ['type' => 'object', 'properties' => ['category' => ['type' => 'string'], 'own_value' => ['type' => 'string'], 'competitor_value' => ['type' => 'string']]]
                ],
            ],
            'required' => ['competitor_board_id', 'name']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $boardId = $arguments['competitor_board_id'] ?? null;
            if (!$boardId) {
                return ToolResult::error('VALIDATION_ERROR', 'competitor_board_id ist erforderlich.');
            }

            $board = BrandsCompetitorBoard::find($boardId);
            if (!$board) {
                return ToolResult::error('COMPETITOR_BOARD_NOT_FOUND', 'Das angegebene Competitor Board wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Wettbewerber für dieses Board erstellen.');
            }

            $name = $arguments['name'] ?? null;
            if (!$name) {
                return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');
            }

            $competitor = BrandsCompetitor::create([
                'competitor_board_id' => $board->id,
                'name' => $name,
                'logo_url' => $arguments['logo_url'] ?? null,
                'website_url' => $arguments['website_url'] ?? null,
                'description' => $arguments['description'] ?? null,
                'strengths' => $arguments['strengths'] ?? null,
                'weaknesses' => $arguments['weaknesses'] ?? null,
                'notes' => $arguments['notes'] ?? null,
                'position_x' => $arguments['position_x'] ?? null,
                'position_y' => $arguments['position_y'] ?? null,
                'is_own_brand' => $arguments['is_own_brand'] ?? false,
                'differentiation' => $arguments['differentiation'] ?? null,
            ]);

            $competitor->load(['competitorBoard']);

            return ToolResult::success([
                'id' => $competitor->id,
                'uuid' => $competitor->uuid,
                'name' => $competitor->name,
                'logo_url' => $competitor->logo_url,
                'website_url' => $competitor->website_url,
                'description' => $competitor->description,
                'strengths' => $competitor->strengths,
                'weaknesses' => $competitor->weaknesses,
                'notes' => $competitor->notes,
                'position_x' => $competitor->position_x,
                'position_y' => $competitor->position_y,
                'is_own_brand' => $competitor->is_own_brand,
                'differentiation' => $competitor->differentiation,
                'order' => $competitor->order,
                'competitor_board_id' => $competitor->competitor_board_id,
                'created_at' => $competitor->created_at->toIso8601String(),
                'message' => "Wettbewerber '{$competitor->name}' erfolgreich erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Wettbewerbers: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'competitor', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}

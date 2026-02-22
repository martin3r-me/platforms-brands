<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsSocialBoard;
use Platform\Brands\Models\BrandsSocialCard;
use Illuminate\Support\Facades\Gate;

/**
 * Tool zum Auflisten von SocialCards im Brands-Modul
 */
class ListSocialCardsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.social_cards.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/social_boards/{social_board_id}/social_cards - Listet Social Cards eines Social Boards auf. REST-Parameter: social_board_id (required, integer) - Social Board-ID. filters (optional, array) - Filter-Array. search (optional, string) - Suchbegriff. sort (optional, array) - Sortierung. limit/offset (optional) - Pagination.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'social_board_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (required): ID des Social Boards. Nutze "brands.social_boards.GET" um Social Boards zu finden.'
                    ],
                ]
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $socialBoardId = $arguments['social_board_id'] ?? null;
            if (!$socialBoardId) {
                return ToolResult::error('VALIDATION_ERROR', 'social_board_id ist erforderlich.');
            }

            $socialBoard = BrandsSocialBoard::find($socialBoardId);
            if (!$socialBoard) {
                return ToolResult::error('SOCIAL_BOARD_NOT_FOUND', 'Das angegebene Social Board wurde nicht gefunden.');
            }

            // Policy prüfen
            if (!Gate::forUser($context->user)->allows('view', $socialBoard)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Social Board.');
            }
            
            // Query aufbauen - Social Cards
            $query = BrandsSocialCard::query()
                ->where('social_board_id', $socialBoardId)
                ->with(['socialBoard', 'slot', 'user', 'team']);

            // Standard-Operationen anwenden
            $this->applyStandardFilters($query, $arguments, [
                'title', 'description', 'status', 'publish_at', 'published_at', 'created_at', 'updated_at'
            ]);
            
            // Standard-Suche anwenden
            $this->applyStandardSearch($query, $arguments, ['title', 'body_md', 'description']);
            
            // Standard-Sortierung anwenden
            $this->applyStandardSort($query, $arguments, [
                'title', 'created_at', 'updated_at', 'order', 'status', 'publish_at'
            ], 'order', 'asc');
            
            // Standard-Pagination anwenden
            $this->applyStandardPagination($query, $arguments);

            // Cards holen und per Policy filtern
            $cards = $query->get()->filter(function ($card) use ($context) {
                try {
                    return Gate::forUser($context->user)->allows('view', $card);
                } catch (\Throwable $e) {
                    return false;
                }
            })->values();

            // Cards formatieren
            $cardsList = $cards->map(function($socialCard) {
                return [
                    'id' => $socialCard->id,
                    'uuid' => $socialCard->uuid,
                    'title' => $socialCard->title,
                    'body_md' => $socialCard->body_md,
                    'description' => $socialCard->description,
                    'social_board_id' => $socialCard->social_board_id,
                    'social_board_name' => $socialCard->socialBoard->name,
                    'slot_id' => $socialCard->social_board_slot_id,
                    'slot_name' => $socialCard->slot->name ?? null,
                    'status' => $socialCard->status ?? 'draft',
                    'publish_at' => $socialCard->publish_at?->toIso8601String(),
                    'published_at' => $socialCard->published_at?->toIso8601String(),
                    'team_id' => $socialCard->team_id,
                    'user_id' => $socialCard->user_id,
                    'created_at' => $socialCard->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'social_cards' => $cardsList,
                'count' => count($cardsList),
                'social_board_id' => $socialBoardId,
                'social_board_name' => $socialBoard->name,
                'message' => count($cardsList) > 0 
                    ? count($cardsList) . ' Social Card(s) gefunden für Social Board "' . $socialBoard->name . '".'
                    : 'Keine Social Cards gefunden für Social Board "' . $socialBoard->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Social Cards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'social_card', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}

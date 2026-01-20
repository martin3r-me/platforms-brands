<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSocialBoard;
use Platform\Brands\Models\BrandsSocialBoardSlot;
use Platform\Brands\Models\BrandsSocialCard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Erstellen von SocialCards im Brands-Modul
 */
class CreateSocialCardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.social_cards.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/social_boards/{social_board_id}/slots/{slot_id}/social_cards - Erstellt eine neue Social Card. REST-Parameter: social_board_id (required, integer) - Social Board-ID. social_board_slot_id (required, integer) - Slot-ID. title (optional, string) - Titel. body_md (optional, string) - Markdown-Inhalt. description (optional, string) - Beschreibung.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'social_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Social Boards (ERFORDERLICH). Nutze "brands.social_boards.GET" um Social Boards zu finden.'
                ],
                'social_board_slot_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Slots im Social Board (ERFORDERLICH). Nutze "brands.social_board.GET" um Slots zu sehen.'
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Titel der Social Card. Wenn nicht angegeben, wird automatisch "Neue Social Card" verwendet.'
                ],
                'body_md' => [
                    'type' => 'string',
                    'description' => 'Markdown-Inhalt der Social Card (Caption/Text).'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Beschreibung der Social Card.'
                ],
            ],
            'required' => ['social_board_id', 'social_board_slot_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            // SocialBoard finden
            $socialBoardId = $arguments['social_board_id'] ?? null;
            if (!$socialBoardId) {
                return ToolResult::error('VALIDATION_ERROR', 'social_board_id ist erforderlich.');
            }

            $socialBoard = BrandsSocialBoard::find($socialBoardId);
            if (!$socialBoard) {
                return ToolResult::error('SOCIAL_BOARD_NOT_FOUND', 'Das angegebene Social Board wurde nicht gefunden.');
            }

            // Slot finden
            $slotId = $arguments['social_board_slot_id'] ?? null;
            if (!$slotId) {
                return ToolResult::error('VALIDATION_ERROR', 'social_board_slot_id ist erforderlich.');
            }

            $slot = BrandsSocialBoardSlot::find($slotId);
            if (!$slot || $slot->social_board_id != $socialBoardId) {
                return ToolResult::error('SLOT_NOT_FOUND', 'Der angegebene Slot wurde nicht gefunden oder gehört nicht zu diesem Social Board.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $socialBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Cards für dieses Social Board erstellen (Policy).');
            }

            $title = $arguments['title'] ?? 'Neue Social Card';

            // SocialCard direkt erstellen
            $socialCard = BrandsSocialCard::create([
                'title' => $title,
                'body_md' => $arguments['body_md'] ?? null,
                'description' => $arguments['description'] ?? null,
                'user_id' => $context->user->id,
                'team_id' => $socialBoard->team_id,
                'social_board_id' => $socialBoard->id,
                'social_board_slot_id' => $slot->id,
            ]);

            $socialCard->load(['socialBoard', 'slot', 'user', 'team']);

            return ToolResult::success([
                'id' => $socialCard->id,
                'uuid' => $socialCard->uuid,
                'title' => $socialCard->title,
                'body_md' => $socialCard->body_md,
                'description' => $socialCard->description,
                'social_board_id' => $socialCard->social_board_id,
                'social_board_name' => $socialCard->socialBoard->name,
                'slot_id' => $socialCard->social_board_slot_id,
                'slot_name' => $socialCard->slot->name,
                'team_id' => $socialCard->team_id,
                'created_at' => $socialCard->created_at->toIso8601String(),
                'message' => "Social Card '{$socialCard->title}' erfolgreich erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der Social Card: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'social_card', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}

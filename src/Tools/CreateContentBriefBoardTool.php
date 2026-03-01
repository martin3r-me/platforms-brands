<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsContentBriefBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateContentBriefBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_brief_boards.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/{brand_id}/content_brief_boards - Erstellt ein neues Content Brief Board für eine Marke. REST-Parameter: brand_id (required, integer) - Marken-ID. name (required, string) - Arbeitstitel / H1-Kandidat. content_type (optional, string) - pillar|how-to|listicle|faq|comparison|deep-dive|guide. search_intent (optional, string) - informational|commercial|transactional|navigational. status (optional, string) - draft|briefed|in_production|review|published. target_slug (optional, string) - Geplante URL. target_word_count (optional, integer) - Zielwortanzahl. description (optional, string) - Zusammenfassung des Artikelziels. seo_board_id (optional, integer) - Relation zum SEO Board.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'brand_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Marke, zu der das Content Brief Board gehört (ERFORDERLICH).'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Arbeitstitel / H1-Kandidat des Content Briefs.'
                ],
                'content_type' => [
                    'type' => 'string',
                    'enum' => ['pillar', 'how-to', 'listicle', 'faq', 'comparison', 'deep-dive', 'guide'],
                    'description' => 'Content-Typ des Briefs.'
                ],
                'search_intent' => [
                    'type' => 'string',
                    'enum' => ['informational', 'commercial', 'transactional', 'navigational'],
                    'description' => 'Such-Intent des Briefs.'
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['draft', 'briefed', 'in_production', 'review', 'published'],
                    'description' => 'Status des Briefs.'
                ],
                'target_slug' => [
                    'type' => 'string',
                    'description' => 'Geplante URL / Slug.'
                ],
                'target_word_count' => [
                    'type' => 'integer',
                    'description' => 'Zielwortanzahl.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Kurze Zusammenfassung des Artikelziels.'
                ],
                'seo_board_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: ID des verknüpften SEO Boards.'
                ],
            ],
            'required' => ['brand_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $brandId = $arguments['brand_id'] ?? null;
            if (!$brandId) {
                return ToolResult::error('VALIDATION_ERROR', 'brand_id ist erforderlich.');
            }

            $brand = BrandsBrand::find($brandId);
            if (!$brand) {
                return ToolResult::error('BRAND_NOT_FOUND', 'Die angegebene Marke wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $brand);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Boards für diese Marke erstellen (Policy).');
            }

            // Validate enum values
            if (isset($arguments['content_type']) && !array_key_exists($arguments['content_type'], BrandsContentBriefBoard::CONTENT_TYPES)) {
                return ToolResult::error('VALIDATION_ERROR', 'Ungültiger content_type. Erlaubt: ' . implode(', ', array_keys(BrandsContentBriefBoard::CONTENT_TYPES)));
            }

            if (isset($arguments['search_intent']) && !array_key_exists($arguments['search_intent'], BrandsContentBriefBoard::SEARCH_INTENTS)) {
                return ToolResult::error('VALIDATION_ERROR', 'Ungültiger search_intent. Erlaubt: ' . implode(', ', array_keys(BrandsContentBriefBoard::SEARCH_INTENTS)));
            }

            if (isset($arguments['status']) && !array_key_exists($arguments['status'], BrandsContentBriefBoard::STATUSES)) {
                return ToolResult::error('VALIDATION_ERROR', 'Ungültiger status. Erlaubt: ' . implode(', ', array_keys(BrandsContentBriefBoard::STATUSES)));
            }

            $contentBriefBoard = BrandsContentBriefBoard::create([
                'name' => $arguments['name'] ?? 'Neues Content Brief',
                'description' => $arguments['description'] ?? null,
                'content_type' => $arguments['content_type'] ?? 'guide',
                'search_intent' => $arguments['search_intent'] ?? 'informational',
                'status' => $arguments['status'] ?? 'draft',
                'target_slug' => $arguments['target_slug'] ?? null,
                'target_word_count' => $arguments['target_word_count'] ?? null,
                'seo_board_id' => $arguments['seo_board_id'] ?? null,
                'user_id' => $context->user->id,
                'team_id' => $brand->team_id,
                'brand_id' => $brand->id,
            ]);

            $contentBriefBoard->load(['brand', 'user', 'team', 'seoBoard']);

            return ToolResult::success([
                'id' => $contentBriefBoard->id,
                'uuid' => $contentBriefBoard->uuid,
                'name' => $contentBriefBoard->name,
                'description' => $contentBriefBoard->description,
                'content_type' => $contentBriefBoard->content_type,
                'search_intent' => $contentBriefBoard->search_intent,
                'status' => $contentBriefBoard->status,
                'target_slug' => $contentBriefBoard->target_slug,
                'target_word_count' => $contentBriefBoard->target_word_count,
                'brand_id' => $contentBriefBoard->brand_id,
                'brand_name' => $contentBriefBoard->brand->name,
                'seo_board_id' => $contentBriefBoard->seo_board_id,
                'team_id' => $contentBriefBoard->team_id,
                'created_at' => $contentBriefBoard->created_at->toIso8601String(),
                'message' => "Content Brief '{$contentBriefBoard->name}' erfolgreich für Marke '{$contentBriefBoard->brand->name}' erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Content Brief Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'content_brief_board', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}

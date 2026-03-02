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
                    'description' => 'Content-Typ des Briefs. Erlaubte Werte per Lookup-Tabelle (brands.lookup_values.GET name="content_type").'
                ],
                'search_intent' => [
                    'type' => 'string',
                    'description' => 'Such-Intent des Briefs. Erlaubte Werte per Lookup-Tabelle (brands.lookup_values.GET name="search_intent").'
                ],
                'status' => [
                    'type' => 'string',
                    'description' => 'Status des Briefs. Erlaubte Werte per Lookup-Tabelle (brands.lookup_values.GET name="content_brief_status").'
                ],
                'target_slug' => [
                    'type' => 'string',
                    'description' => 'Geplante URL / Slug.'
                ],
                'target_url' => [
                    'type' => 'string',
                    'description' => 'Qualifizierte Ziel-URL (z.B. https://example.com/guide/keyword).'
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

            // Validate gegen Lookup-Tabellen (mit Fallback auf Konstanten)
            $teamId = $brand->team_id;

            if (isset($arguments['content_type']) && !BrandsContentBriefBoard::isValidLookupValue('content_type', $arguments['content_type'], $teamId)) {
                $allowed = BrandsContentBriefBoard::getAllowedValues('content_type', $teamId);
                return ToolResult::error('VALIDATION_ERROR', 'Ungültiger content_type. Erlaubt: ' . implode(', ', $allowed));
            }

            if (isset($arguments['search_intent']) && !BrandsContentBriefBoard::isValidLookupValue('search_intent', $arguments['search_intent'], $teamId)) {
                $allowed = BrandsContentBriefBoard::getAllowedValues('search_intent', $teamId);
                return ToolResult::error('VALIDATION_ERROR', 'Ungültiger search_intent. Erlaubt: ' . implode(', ', $allowed));
            }

            if (isset($arguments['status']) && !BrandsContentBriefBoard::isValidLookupValue('content_brief_status', $arguments['status'], $teamId)) {
                $allowed = BrandsContentBriefBoard::getAllowedValues('content_brief_status', $teamId);
                return ToolResult::error('VALIDATION_ERROR', 'Ungültiger status. Erlaubt: ' . implode(', ', $allowed));
            }

            $contentBriefBoard = BrandsContentBriefBoard::create([
                'name' => $arguments['name'] ?? 'Neues Content Brief',
                'description' => $arguments['description'] ?? null,
                'content_type' => $arguments['content_type'] ?? 'guide',
                'search_intent' => $arguments['search_intent'] ?? 'informational',
                'status' => $arguments['status'] ?? 'draft',
                'target_slug' => $arguments['target_slug'] ?? null,
                'target_url' => $arguments['target_url'] ?? null,
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
                'target_url' => $contentBriefBoard->target_url,
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

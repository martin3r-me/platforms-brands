<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSeoBoard;
use Platform\Brands\Models\BrandsSeoKeyword;
use Platform\Brands\Models\BrandsSeoKeywordCluster;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateSeoKeywordTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.seo_keywords.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/seo_boards/{seo_board_id}/keywords - Erstellt ein neues SEO Keyword. REST-Parameter: seo_board_id (required), keyword (required, string), seo_keyword_cluster_id (optional), search_volume/keyword_difficulty/cpc_cents (optional), search_intent/keyword_type/priority (optional), content_idea/url/notes (optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'seo_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des SEO Boards (ERFORDERLICH).'
                ],
                'keyword' => [
                    'type' => 'string',
                    'description' => 'Das Keyword (ERFORDERLICH).'
                ],
                'seo_keyword_cluster_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: ID des Keyword-Clusters.'
                ],
                'keyword_cluster_id' => [
                    'type' => 'integer',
                    'description' => 'Alias für seo_keyword_cluster_id (deprecated, nutze seo_keyword_cluster_id).'
                ],
                'search_volume' => [
                    'type' => 'integer',
                    'description' => 'Optional: Monatliches Suchvolumen.'
                ],
                'keyword_difficulty' => [
                    'type' => 'integer',
                    'description' => 'Optional: Keyword Difficulty (0-100).'
                ],
                'cpc_cents' => [
                    'type' => 'integer',
                    'description' => 'Optional: Cost per Click in Cents.'
                ],
                'trend' => [
                    'type' => 'string',
                    'description' => 'Optional: Trend (up, down, stable, seasonal).'
                ],
                'search_intent' => [
                    'type' => 'string',
                    'description' => 'Optional: Search Intent (informational, navigational, commercial, transactional).'
                ],
                'keyword_type' => [
                    'type' => 'string',
                    'description' => 'Optional: Keyword-Typ (head, body, long_tail, branded, local).'
                ],
                'content_idea' => [
                    'type' => 'string',
                    'description' => 'Optional: Content-Idee für dieses Keyword.'
                ],
                'priority' => [
                    'type' => 'string',
                    'description' => 'Optional: Priorität (high, medium, low).'
                ],
                'url' => [
                    'type' => 'string',
                    'description' => 'Optional: Zugeordnete URL.'
                ],
                'position' => [
                    'type' => 'integer',
                    'description' => 'Optional: Aktuelle Ranking-Position.'
                ],
                'notes' => [
                    'type' => 'string',
                    'description' => 'Optional: Notizen.'
                ],
            ],
            'required' => ['seo_board_id', 'keyword']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $seoBoardId = $arguments['seo_board_id'] ?? null;
            if (!$seoBoardId) {
                return ToolResult::error('VALIDATION_ERROR', 'seo_board_id ist erforderlich.');
            }

            $seoBoard = BrandsSeoBoard::find($seoBoardId);
            if (!$seoBoard) {
                return ToolResult::error('SEO_BOARD_NOT_FOUND', 'Das angegebene SEO Board wurde nicht gefunden.');
            }

            $keyword = $arguments['keyword'] ?? null;
            if (!$keyword) {
                return ToolResult::error('VALIDATION_ERROR', 'keyword ist erforderlich.');
            }

            // seo_keyword_cluster_id hat Vorrang, keyword_cluster_id als Fallback
            $clusterId = $arguments['seo_keyword_cluster_id'] ?? $arguments['keyword_cluster_id'] ?? null;

            if (!empty($clusterId)) {
                $cluster = BrandsSeoKeywordCluster::find($clusterId);
                if (!$cluster || $cluster->seo_board_id != $seoBoardId) {
                    return ToolResult::error('CLUSTER_NOT_FOUND', 'Der angegebene Cluster wurde nicht gefunden oder gehört nicht zu diesem SEO Board.');
                }
            }

            try {
                Gate::forUser($context->user)->authorize('update', $seoBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Keywords für dieses SEO Board erstellen (Policy).');
            }

            $seoKeyword = BrandsSeoKeyword::create([
                'seo_board_id' => $seoBoard->id,
                'keyword_cluster_id' => $clusterId,
                'keyword' => $keyword,
                'search_volume' => $arguments['search_volume'] ?? null,
                'keyword_difficulty' => $arguments['keyword_difficulty'] ?? null,
                'cpc_cents' => $arguments['cpc_cents'] ?? null,
                'trend' => $arguments['trend'] ?? null,
                'search_intent' => $arguments['search_intent'] ?? null,
                'keyword_type' => $arguments['keyword_type'] ?? null,
                'content_idea' => $arguments['content_idea'] ?? null,
                'priority' => $arguments['priority'] ?? null,
                'url' => $arguments['url'] ?? null,
                'position' => $arguments['position'] ?? null,
                'notes' => $arguments['notes'] ?? null,
                'user_id' => $context->user->id,
                'team_id' => $seoBoard->team_id,
            ]);

            $seoKeyword->load(['seoBoard', 'cluster']);

            return ToolResult::success([
                'id' => $seoKeyword->id,
                'uuid' => $seoKeyword->uuid,
                'keyword' => $seoKeyword->keyword,
                'seo_board_id' => $seoKeyword->seo_board_id,
                'seo_board_name' => $seoKeyword->seoBoard->name,
                'keyword_cluster_id' => $seoKeyword->keyword_cluster_id,
                'cluster_name' => $seoKeyword->cluster?->name,
                'search_volume' => $seoKeyword->search_volume,
                'keyword_difficulty' => $seoKeyword->keyword_difficulty,
                'search_intent' => $seoKeyword->search_intent,
                'priority' => $seoKeyword->priority,
                'created_at' => $seoKeyword->created_at->toIso8601String(),
                'message' => "Keyword '{$seoKeyword->keyword}' erfolgreich erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Keywords: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'seo_keyword', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}

<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSeoBoard;
use Platform\Brands\Models\BrandsSeoKeywordCluster;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateSeoKeywordClusterTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.seo_keyword_clusters.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/seo_boards/{seo_board_id}/keyword_clusters - Erstellt einen neuen Keyword-Cluster. REST-Parameter: seo_board_id (required, integer) - SEO Board-ID. name (optional, string) - Cluster-Name. color (optional, string) - Farbe.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'seo_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des SEO Boards (ERFORDERLICH). Nutze "brands.seo_boards.GET" um SEO Boards zu finden.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name des Keyword-Clusters. Wenn nicht angegeben, wird "Neuer Cluster" verwendet.'
                ],
                'color' => [
                    'type' => 'string',
                    'description' => 'Optional: Farbe des Clusters (z.B. "blue", "red", "green").'
                ],
            ],
            'required' => ['seo_board_id']
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

            try {
                Gate::forUser($context->user)->authorize('update', $seoBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Cluster für dieses SEO Board erstellen (Policy).');
            }

            $cluster = BrandsSeoKeywordCluster::create([
                'seo_board_id' => $seoBoard->id,
                'name' => $arguments['name'] ?? 'Neuer Cluster',
                'color' => $arguments['color'] ?? null,
                'user_id' => $context->user->id,
                'team_id' => $seoBoard->team_id,
            ]);

            $cluster->load(['seoBoard', 'user', 'team']);

            return ToolResult::success([
                'id' => $cluster->id,
                'uuid' => $cluster->uuid,
                'name' => $cluster->name,
                'color' => $cluster->color,
                'seo_board_id' => $cluster->seo_board_id,
                'seo_board_name' => $cluster->seoBoard->name,
                'team_id' => $cluster->team_id,
                'created_at' => $cluster->created_at->toIso8601String(),
                'message' => "Keyword-Cluster '{$cluster->name}' erfolgreich erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Keyword-Clusters: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'seo_keyword_cluster', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}

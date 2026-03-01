<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsContentBriefBoard;
use Platform\Brands\Models\BrandsSeoKeywordCluster;
use Platform\Brands\Models\BrandsContentBriefKeywordCluster;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Verknüpfen eines SEO Keyword Clusters mit einem Content Brief.
 *
 * Jeder Content Brief hat genau 1 primary Cluster und beliebig viele secondary/supporting.
 * Das primary Cluster definiert, welches Keyword-Cluster den Artikel treibt.
 */
class CreateContentBriefKeywordClusterTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_brief_keyword_clusters.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/content_brief_keyword_clusters - Verknüpft ein SEO Keyword Cluster mit einem Content Brief. '
            . 'Jeder Content Brief hat genau 1 primary Cluster (treibendes Cluster) und beliebig viele secondary/supporting Cluster. '
            . 'REST-Parameter: content_brief_id (required), seo_keyword_cluster_id (required), role (required: primary|secondary|supporting).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'content_brief_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Content Briefs (ERFORDERLICH). Nutze "brands.content_brief_boards.GET" um IDs zu sehen.',
                ],
                'seo_keyword_cluster_id' => [
                    'type' => 'integer',
                    'description' => 'ID des SEO Keyword Clusters (ERFORDERLICH). Nutze "brands.seo_keyword_clusters.GET" um IDs zu sehen.',
                ],
                'role' => [
                    'type' => 'string',
                    'enum' => ['primary', 'secondary', 'supporting'],
                    'description' => 'Rolle des Clusters für diesen Content Brief (ERFORDERLICH). "primary" = treibendes Cluster (genau 1 pro Brief), "secondary" = ergänzendes Cluster, "supporting" = unterstützendes Cluster.',
                ],
            ],
            'required' => ['content_brief_id', 'seo_keyword_cluster_id', 'role'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            // content_brief_id validieren
            $contentBriefId = $arguments['content_brief_id'] ?? null;
            if (!$contentBriefId) {
                return ToolResult::error('VALIDATION_ERROR', 'content_brief_id ist erforderlich.');
            }

            $contentBrief = BrandsContentBriefBoard::with('brand')->find($contentBriefId);
            if (!$contentBrief) {
                return ToolResult::error('CONTENT_BRIEF_BOARD_NOT_FOUND', 'Das angegebene Content Brief wurde nicht gefunden.');
            }

            // Policy: update auf Content Brief prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $contentBrief);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Keyword-Cluster-Verknüpfungen für dieses Content Brief erstellen (Policy).');
            }

            // seo_keyword_cluster_id validieren
            $clusterId = $arguments['seo_keyword_cluster_id'] ?? null;
            if (!$clusterId) {
                return ToolResult::error('VALIDATION_ERROR', 'seo_keyword_cluster_id ist erforderlich.');
            }

            $cluster = BrandsSeoKeywordCluster::find($clusterId);
            if (!$cluster) {
                return ToolResult::error('KEYWORD_CLUSTER_NOT_FOUND', 'Das angegebene SEO Keyword Cluster wurde nicht gefunden.');
            }

            // role validieren
            $role = $arguments['role'] ?? null;
            if (!$role) {
                return ToolResult::error('VALIDATION_ERROR', 'role ist erforderlich.');
            }

            if (!in_array($role, array_keys(BrandsContentBriefKeywordCluster::ROLES))) {
                return ToolResult::error('VALIDATION_ERROR', 'Ungültige role. Erlaubt: primary, secondary, supporting.');
            }

            // Duplikat-Check
            $existing = BrandsContentBriefKeywordCluster::where('content_brief_id', $contentBriefId)
                ->where('seo_keyword_cluster_id', $clusterId)
                ->first();

            if ($existing) {
                return ToolResult::error('DUPLICATE_LINK', "Dieses Keyword Cluster ist bereits mit dem Content Brief verknüpft (ID: {$existing->id}, Rolle: {$existing->role}).");
            }

            // Primary-Constraint: Nur 1 primary pro Content Brief
            if ($role === 'primary') {
                $existingPrimary = BrandsContentBriefKeywordCluster::where('content_brief_id', $contentBriefId)
                    ->where('role', 'primary')
                    ->with('keywordCluster')
                    ->first();

                if ($existingPrimary) {
                    return ToolResult::error(
                        'PRIMARY_ALREADY_EXISTS',
                        "Dieses Content Brief hat bereits ein primary Cluster: '{$existingPrimary->keywordCluster->name}' (ID: {$existingPrimary->id}). "
                        . "Entferne zuerst das bestehende primary Cluster oder verwende 'secondary'/'supporting'."
                    );
                }
            }

            // Verknüpfung erstellen
            $link = BrandsContentBriefKeywordCluster::create([
                'content_brief_id' => $contentBriefId,
                'seo_keyword_cluster_id' => $clusterId,
                'role' => $role,
            ]);

            $link->load(['contentBrief.brand', 'keywordCluster']);

            return ToolResult::success([
                'id' => $link->id,
                'content_brief_id' => $link->content_brief_id,
                'content_brief_name' => $link->contentBrief->name,
                'seo_keyword_cluster_id' => $link->seo_keyword_cluster_id,
                'cluster_name' => $link->keywordCluster->name,
                'role' => $link->role,
                'role_label' => BrandsContentBriefKeywordCluster::ROLES[$link->role] ?? $link->role,
                'created_at' => $link->created_at->toIso8601String(),
                'message' => "Keyword Cluster '{$link->keywordCluster->name}' als {$link->role} mit Content Brief '{$link->contentBrief->name}' verknüpft.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Verknüpfen des Keyword Clusters: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'content_brief', 'keyword_cluster', 'seo', 'link', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}

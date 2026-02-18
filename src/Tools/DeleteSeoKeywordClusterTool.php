<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsSeoKeywordCluster;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteSeoKeywordClusterTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.seo_keyword_clusters.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/seo_keyword_clusters/{id} - Löscht einen Keyword-Cluster. Keywords im Cluster werden beibehalten (Cluster-Zuordnung wird null). REST-Parameter: keyword_cluster_id (required, integer) - Cluster-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'keyword_cluster_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden Keyword-Clusters (ERFORDERLICH).'
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestätigung.'
                ]
            ],
            'required' => ['keyword_cluster_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments, $context, 'keyword_cluster_id', BrandsSeoKeywordCluster::class,
                'CLUSTER_NOT_FOUND', 'Der angegebene Keyword-Cluster wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $cluster = $validation['model'];
            $cluster->load('seoBoard');

            try {
                Gate::forUser($context->user)->authorize('update', $cluster->seoBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diesen Keyword-Cluster nicht löschen (Policy).');
            }

            $clusterName = $cluster->name;
            $clusterId = $cluster->id;
            $seoBoardId = $cluster->seo_board_id;

            $cluster->delete();

            return ToolResult::success([
                'keyword_cluster_id' => $clusterId,
                'keyword_cluster_name' => $clusterName,
                'seo_board_id' => $seoBoardId,
                'message' => "Keyword-Cluster '{$clusterName}' wurde erfolgreich gelöscht. Keywords sind weiterhin vorhanden."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Keyword-Clusters: ' . $e->getMessage());
        }
    }
}

<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsSeoKeywordCluster;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateSeoKeywordClusterTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.seo_keyword_clusters.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/seo_keyword_clusters/{id} - Aktualisiert einen Keyword-Cluster. REST-Parameter: keyword_cluster_id (required, integer) - Cluster-ID. name (optional, string) - Name. color (optional, string) - Farbe.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'keyword_cluster_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Keyword-Clusters (ERFORDERLICH).'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Name des Clusters.'
                ],
                'color' => [
                    'type' => 'string',
                    'description' => 'Optional: Farbe des Clusters.'
                ],
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
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diesen Keyword-Cluster nicht bearbeiten (Policy).');
            }

            $updateData = [];
            foreach (['name', 'color'] as $field) {
                if (isset($arguments[$field])) {
                    $updateData[$field] = $arguments[$field];
                }
            }

            if (!empty($updateData)) {
                $cluster->update($updateData);
            }

            $cluster->refresh();
            $cluster->load('seoBoard');

            return ToolResult::success([
                'keyword_cluster_id' => $cluster->id,
                'name' => $cluster->name,
                'color' => $cluster->color,
                'seo_board_id' => $cluster->seo_board_id,
                'seo_board_name' => $cluster->seoBoard->name,
                'updated_at' => $cluster->updated_at->toIso8601String(),
                'message' => "Keyword-Cluster '{$cluster->name}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Keyword-Clusters: ' . $e->getMessage());
        }
    }
}

<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsSeoKeyword;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateSeoKeywordTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.seo_keywords.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/seo_keywords/{id} - Aktualisiert ein SEO Keyword. REST-Parameter: seo_keyword_id (required, integer). keyword/seo_keyword_cluster_id/search_volume/keyword_difficulty/cpc_cents/trend/search_intent/keyword_type/content_idea/priority/url/position/notes (optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'seo_keyword_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Keywords (ERFORDERLICH).'
                ],
                'keyword' => ['type' => 'string', 'description' => 'Optional: Keyword-Text.'],
                'seo_keyword_cluster_id' => ['type' => 'integer', 'description' => 'Optional: Cluster-ID (null zum Entfernen).'],
                'keyword_cluster_id' => ['type' => 'integer', 'description' => 'Alias für seo_keyword_cluster_id (deprecated, nutze seo_keyword_cluster_id).'],
                'search_volume' => ['type' => 'integer', 'description' => 'Optional: Suchvolumen.'],
                'keyword_difficulty' => ['type' => 'integer', 'description' => 'Optional: KD (0-100).'],
                'cpc_cents' => ['type' => 'integer', 'description' => 'Optional: CPC in Cents.'],
                'trend' => ['type' => 'string', 'description' => 'Optional: Trend.'],
                'search_intent' => ['type' => 'string', 'description' => 'Optional: Search Intent.'],
                'keyword_type' => ['type' => 'string', 'description' => 'Optional: Keyword-Typ.'],
                'content_idea' => ['type' => 'string', 'description' => 'Optional: Content-Idee.'],
                'priority' => ['type' => 'string', 'description' => 'Optional: Priorität.'],
                'url' => ['type' => 'string', 'description' => 'Optional: URL.'],
                'position' => ['type' => 'integer', 'description' => 'Optional: Ranking-Position.'],
                'notes' => ['type' => 'string', 'description' => 'Optional: Notizen.'],
            ],
            'required' => ['seo_keyword_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments, $context, 'seo_keyword_id', BrandsSeoKeyword::class,
                'KEYWORD_NOT_FOUND', 'Das angegebene Keyword wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $keyword = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('update', $keyword);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Keyword nicht bearbeiten (Policy).');
            }

            // seo_keyword_cluster_id → keyword_cluster_id mapping
            if (array_key_exists('seo_keyword_cluster_id', $arguments) && !array_key_exists('keyword_cluster_id', $arguments)) {
                $arguments['keyword_cluster_id'] = $arguments['seo_keyword_cluster_id'];
            }

            $updateData = [];
            foreach ([
                'keyword', 'keyword_cluster_id', 'search_volume', 'keyword_difficulty',
                'cpc_cents', 'trend', 'search_intent', 'keyword_type', 'content_idea',
                'priority', 'url', 'position', 'notes',
            ] as $field) {
                if (array_key_exists($field, $arguments)) {
                    $updateData[$field] = $arguments[$field];
                }
            }

            if (!empty($updateData)) {
                $keyword->update($updateData);
            }

            $keyword->refresh();
            $keyword->load(['seoBoard', 'cluster']);

            return ToolResult::success([
                'seo_keyword_id' => $keyword->id,
                'keyword' => $keyword->keyword,
                'seo_board_id' => $keyword->seo_board_id,
                'seo_board_name' => $keyword->seoBoard->name,
                'keyword_cluster_id' => $keyword->keyword_cluster_id,
                'cluster_name' => $keyword->cluster?->name,
                'search_volume' => $keyword->search_volume,
                'keyword_difficulty' => $keyword->keyword_difficulty,
                'priority' => $keyword->priority,
                'updated_at' => $keyword->updated_at->toIso8601String(),
                'message' => "Keyword '{$keyword->keyword}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Keywords: ' . $e->getMessage());
        }
    }
}

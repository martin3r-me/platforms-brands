<?php

namespace Platform\Brands\Tools;

use Illuminate\Support\Facades\DB;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class BulkCreateSeoKeywordsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.seo_keywords.BULK_POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/seo_keywords/bulk - Erstellt mehrere SEO Keywords in einem Call. Body MUSS {seo_keywords:[{seo_board_id, keyword, ...}], defaults?} enthalten. Unterstützt Lifecycle-Felder: content_status (none|planned|draft|published|optimized), target_url, published_url, target_position, location. Defaults können Lifecycle-Felder für alle Keywords setzen (z.B. defaults.content_status="planned" für Batch-Planung).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'atomic' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Wenn true, alle Creates in einer DB-Transaktion. Standard: false.',
                ],
                'defaults' => [
                    'type' => 'object',
                    'description' => 'Optional: Default-Werte für alle Keywords.',
                    'properties' => [
                        'seo_board_id' => ['type' => 'integer'],
                        'seo_keyword_cluster_id' => ['type' => 'integer'],
                        'keyword_cluster_id' => ['type' => 'integer', 'description' => 'Alias für seo_keyword_cluster_id (deprecated).'],
                        'search_intent' => ['type' => 'string'],
                        'keyword_type' => ['type' => 'string'],
                        'priority' => ['type' => 'string'],
                        'content_status' => ['type' => 'string', 'enum' => ['none', 'planned', 'draft', 'published', 'optimized']],
                        'target_url' => ['type' => 'string'],
                        'published_url' => ['type' => 'string'],
                        'target_position' => ['type' => 'integer'],
                        'location' => ['type' => 'string'],
                    ],
                ],
                'seo_keywords' => [
                    'type' => 'array',
                    'description' => 'Liste von Keywords. Jedes Element: {seo_board_id, keyword, ...}.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'seo_board_id' => ['type' => 'integer'],
                            'keyword' => ['type' => 'string'],
                            'seo_keyword_cluster_id' => ['type' => 'integer'],
                            'keyword_cluster_id' => ['type' => 'integer', 'description' => 'Alias für seo_keyword_cluster_id (deprecated).'],
                            'search_volume' => ['type' => 'integer'],
                            'keyword_difficulty' => ['type' => 'integer'],
                            'cpc_cents' => ['type' => 'integer'],
                            'search_intent' => ['type' => 'string'],
                            'keyword_type' => ['type' => 'string'],
                            'content_idea' => ['type' => 'string'],
                            'priority' => ['type' => 'string'],
                            'url' => ['type' => 'string'],
                            'notes' => ['type' => 'string'],
                            'content_status' => ['type' => 'string', 'enum' => ['none', 'planned', 'draft', 'published', 'optimized']],
                            'target_url' => ['type' => 'string'],
                            'published_url' => ['type' => 'string'],
                            'target_position' => ['type' => 'integer'],
                            'location' => ['type' => 'string'],
                        ],
                        'required' => ['keyword'],
                    ],
                ],
            ],
            'required' => ['seo_keywords'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $seoKeywords = $arguments['seo_keywords'] ?? null;
            if (!is_array($seoKeywords) || empty($seoKeywords)) {
                return ToolResult::error('INVALID_ARGUMENT', 'seo_keywords muss ein nicht-leeres Array sein.');
            }

            $defaults = $arguments['defaults'] ?? [];
            if (!is_array($defaults)) {
                $defaults = [];
            }

            $atomic = (bool) ($arguments['atomic'] ?? false);
            $singleTool = new CreateSeoKeywordTool();

            $run = function () use ($seoKeywords, $defaults, $singleTool, $context) {
                $results = [];
                $okCount = 0;
                $failCount = 0;

                foreach ($seoKeywords as $idx => $kw) {
                    if (!is_array($kw)) {
                        $failCount++;
                        $results[] = [
                            'index' => $idx,
                            'ok' => false,
                            'error' => ['code' => 'INVALID_ITEM', 'message' => 'Keyword-Item muss ein Objekt sein.'],
                        ];
                        continue;
                    }

                    $payload = $defaults;
                    foreach ($kw as $k => $v) {
                        $payload[$k] = $v;
                    }

                    $res = $singleTool->execute($payload, $context);
                    if ($res->success) {
                        $okCount++;
                        $results[] = ['index' => $idx, 'ok' => true, 'data' => $res->data];
                    } else {
                        $failCount++;
                        $results[] = [
                            'index' => $idx,
                            'ok' => false,
                            'error' => ['code' => $res->errorCode, 'message' => $res->error],
                        ];
                    }
                }

                return [
                    'results' => $results,
                    'summary' => [
                        'requested' => count($seoKeywords),
                        'ok' => $okCount,
                        'failed' => $failCount,
                    ],
                ];
            };

            $payload = $atomic ? DB::transaction(fn () => $run()) : $run();

            return ToolResult::success($payload);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Create der Keywords: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'bulk',
            'tags' => ['brands', 'seo_keywords', 'bulk', 'batch', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}

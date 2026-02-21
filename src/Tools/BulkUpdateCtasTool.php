<?php

namespace Platform\Brands\Tools;

use Illuminate\Support\Facades\DB;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

/**
 * Bulk Update: mehrere CTAs in einem Call aktualisieren.
 *
 * Sinn: reduziert Toolcalls/Iterationen (LLM kann 10+ Updates in einem Schritt erledigen).
 * REST-Idee: PUT /brands/ctas/bulk
 */
class BulkUpdateCtasTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.ctas.bulk.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/ctas/bulk - Aktualisiert mehrere CTAs (Call-to-Actions) in einem Request. Nützlich für Batch-Operationen (z.B. mehrere CTA-Labels oder Funnel-Stages aktualisieren) ohne viele Toolcalls.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'atomic' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Wenn true, werden alle Updates in einer DB-Transaktion ausgeführt (bei einem Fehler wird alles zurückgerollt). Standard: false.',
                ],
                'updates' => [
                    'type' => 'array',
                    'description' => 'Liste von Updates. Jedes Element entspricht den Parametern von brands.ctas.PUT.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'cta_id' => ['type' => 'integer'],
                            'label' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'type' => ['type' => 'string', 'enum' => ['primary', 'secondary', 'micro']],
                            'funnel_stage' => ['type' => 'string', 'enum' => ['awareness', 'consideration', 'decision']],
                            'target_page_id' => ['type' => 'integer'],
                            'target_url' => ['type' => 'string'],
                            'is_active' => ['type' => 'boolean'],
                        ],
                        'required' => ['cta_id'],
                    ],
                ],
            ],
            'required' => ['updates'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $updates = $arguments['updates'] ?? null;
            if (!is_array($updates) || empty($updates)) {
                return ToolResult::error('INVALID_ARGUMENT', 'updates muss ein nicht-leeres Array sein.');
            }

            $atomic = (bool) ($arguments['atomic'] ?? false);
            $singleTool = new UpdateCtaTool();

            $run = function () use ($updates, $singleTool, $context) {
                $results = [];
                $okCount = 0;
                $failCount = 0;

                foreach ($updates as $idx => $u) {
                    if (!is_array($u)) {
                        $failCount++;
                        $results[] = [
                            'index' => $idx,
                            'ok' => false,
                            'error' => ['code' => 'INVALID_ITEM', 'message' => 'Update-Item muss ein Objekt sein.'],
                        ];
                        continue;
                    }

                    $res = $singleTool->execute($u, $context);
                    if ($res->success) {
                        $okCount++;
                        $results[] = [
                            'index' => $idx,
                            'ok' => true,
                            'data' => $res->data,
                        ];
                    } else {
                        $failCount++;
                        $results[] = [
                            'index' => $idx,
                            'ok' => false,
                            'error' => [
                                'code' => $res->errorCode,
                                'message' => $res->error,
                            ],
                        ];
                    }
                }

                return [
                    'results' => $results,
                    'summary' => [
                        'requested' => count($updates),
                        'ok' => $okCount,
                        'failed' => $failCount,
                    ],
                ];
            };

            $payload = $atomic ? DB::transaction(fn () => $run()) : $run();

            return ToolResult::success($payload);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Update der CTAs: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'bulk',
            'tags' => ['brands', 'ctas', 'bulk', 'batch', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}

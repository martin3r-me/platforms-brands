<?php

namespace Platform\Brands\Tools;

use Illuminate\Support\Facades\DB;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

/**
 * Bulk Create: mehrere CTAs in einem Call anlegen.
 *
 * Sinn: reduziert Toolcalls/Iterationen (LLM kann 10-50 CTAs in einem Schritt erstellen).
 * REST-Idee: POST /brands/ctas/bulk
 */
class BulkCreateCtasTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.ctas.bulk.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/ctas/bulk - Erstellt mehrere CTAs (Call-to-Actions) in einem Request. Body MUSS {ctas:[{brand_id,label,type,funnel_stage,...}], defaults?} enthalten. Nützlich um z.B. alle CTAs einer Brand auf einmal anzulegen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'atomic' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Wenn true, werden alle Creates in einer DB-Transaktion ausgeführt (bei einem Fehler wird alles zurückgerollt). Standard: false.',
                ],
                'defaults' => [
                    'type' => 'object',
                    'description' => 'Optional: Default-Werte, die auf jedes Item angewendet werden (können pro Item überschrieben werden).',
                    'properties' => [
                        'brand_id' => ['type' => 'integer'],
                        'type' => ['type' => 'string', 'enum' => ['primary', 'secondary', 'micro']],
                        'funnel_stage' => ['type' => 'string', 'enum' => ['awareness', 'consideration', 'decision']],
                        'is_active' => ['type' => 'boolean'],
                    ],
                ],
                'ctas' => [
                    'type' => 'array',
                    'description' => 'Liste von CTAs. Jedes Element entspricht den Parametern von brands.ctas.POST (mindestens brand_id, label, type, funnel_stage).',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'brand_id' => ['type' => 'integer'],
                            'label' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'type' => ['type' => 'string', 'enum' => ['primary', 'secondary', 'micro']],
                            'funnel_stage' => ['type' => 'string', 'enum' => ['awareness', 'consideration', 'decision']],
                            'target_page_id' => ['type' => 'integer'],
                            'target_url' => ['type' => 'string'],
                            'is_active' => ['type' => 'boolean'],
                        ],
                        'required' => ['label'],
                    ],
                ],
            ],
            'required' => ['ctas'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $ctas = $arguments['ctas'] ?? null;
            if (!is_array($ctas) || empty($ctas)) {
                return ToolResult::error('INVALID_ARGUMENT', 'ctas muss ein nicht-leeres Array sein.');
            }

            $defaults = $arguments['defaults'] ?? [];
            if (!is_array($defaults)) {
                $defaults = [];
            }

            $atomic = (bool) ($arguments['atomic'] ?? false);
            $singleTool = new CreateCtaTool();

            $run = function () use ($ctas, $defaults, $singleTool, $context) {
                $results = [];
                $okCount = 0;
                $failCount = 0;

                foreach ($ctas as $idx => $cta) {
                    if (!is_array($cta)) {
                        $failCount++;
                        $results[] = [
                            'index' => $idx,
                            'ok' => false,
                            'error' => ['code' => 'INVALID_ITEM', 'message' => 'CTA-Item muss ein Objekt sein.'],
                        ];
                        continue;
                    }

                    // Defaults anwenden, ohne explizite Werte zu überschreiben
                    $payload = $defaults;
                    foreach ($cta as $k => $v) {
                        $payload[$k] = $v;
                    }

                    $res = $singleTool->execute($payload, $context);
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
                        'requested' => count($ctas),
                        'ok' => $okCount,
                        'failed' => $failCount,
                    ],
                ];
            };

            $payload = $atomic ? DB::transaction(fn () => $run()) : $run();

            return ToolResult::success($payload);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Create der CTAs: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'bulk',
            'tags' => ['brands', 'ctas', 'bulk', 'batch', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}

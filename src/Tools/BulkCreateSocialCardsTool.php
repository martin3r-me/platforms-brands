<?php

namespace Platform\Brands\Tools;

use Illuminate\Support\Facades\DB;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

/**
 * Bulk Create: mehrere Social Cards in einem Call anlegen.
 *
 * Sinn: reduziert Toolcalls/Iterationen (LLM kann 10-50 Social Cards in einem Schritt erstellen).
 * REST-Idee: POST /brands/social_cards/bulk
 */
class BulkCreateSocialCardsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.social_cards.bulk.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/social_cards/bulk - Body MUSS {social_cards:[{social_board_id,social_board_slot_id,title,body_md?}], defaults?} enthalten. Erstellt viele Social Cards (z.B. für mehrere Posts/Captions).';
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
                        'social_board_id' => ['type' => 'integer'],
                        'social_board_slot_id' => ['type' => 'integer'],
                        'description' => ['type' => 'string'],
                    ],
                    'required' => [],
                ],
                'social_cards' => [
                    'type' => 'array',
                    'description' => 'Liste von Social Cards. Jedes Element entspricht den Parametern von brands.social_cards.POST (mindestens social_board_id, social_board_slot_id, title).',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'social_board_id' => ['type' => 'integer'],
                            'social_board_slot_id' => ['type' => 'integer'],
                            'title' => ['type' => 'string'],
                            'body_md' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                        ],
                        'required' => ['social_board_id', 'social_board_slot_id', 'title'],
                    ],
                ],
            ],
            'required' => ['social_cards'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $socialCards = $arguments['social_cards'] ?? null;
            if (!is_array($socialCards) || empty($socialCards)) {
                return ToolResult::error('INVALID_ARGUMENT', 'social_cards muss ein nicht-leeres Array sein.');
            }

            $defaults = $arguments['defaults'] ?? [];
            if (!is_array($defaults)) {
                $defaults = [];
            }

            $atomic = (bool)($arguments['atomic'] ?? false);
            $singleTool = new CreateSocialCardTool();

            $run = function() use ($socialCards, $defaults, $singleTool, $context) {
                $results = [];
                $okCount = 0;
                $failCount = 0;

                foreach ($socialCards as $idx => $sc) {
                    if (!is_array($sc)) {
                        $failCount++;
                        $results[] = [
                            'index' => $idx,
                            'ok' => false,
                            'error' => ['code' => 'INVALID_ITEM', 'message' => 'Social Card-Item muss ein Objekt sein.'],
                        ];
                        continue;
                    }

                    // Defaults anwenden, ohne explizite Werte zu überschreiben
                    $payload = $defaults;
                    foreach ($sc as $k => $v) {
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
                        'requested' => count($socialCards),
                        'ok' => $okCount,
                        'failed' => $failCount,
                    ],
                ];
            };

            $payload = $atomic ? DB::transaction(fn() => $run()) : $run();

            return ToolResult::success($payload);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Create der Social Cards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'bulk',
            'tags' => ['brands', 'social_cards', 'bulk', 'batch', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}

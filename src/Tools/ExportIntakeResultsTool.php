<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsIntakeBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Exportieren/Aggregieren von Intake Board Ergebnissen
 */
class ExportIntakeResultsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.intake_results.export';
    }

    public function getDescription(): string
    {
        return 'GET /brands/intake_boards/{intake_board_id}/export - Exportiert und aggregiert Ergebnisse eines Intake Boards. Liefert Board-Info, verwendete Block-Definitionen, Session-Statistiken und Antwort-Zusammenfassung pro Block. REST-Parameter: intake_board_id (required, integer). format (optional, string, default "summary").';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'intake_board_id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID des Intake Boards. Nutze "brands.intake_boards.GET" um Intake Boards zu finden.'
                ],
                'format' => [
                    'type' => 'string',
                    'description' => 'Optional: Export-Format. Standard: "summary". Aktuell unterstuetzt: "summary".',
                    'enum' => ['summary'],
                ],
            ],
            'required' => ['intake_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $intakeBoardId = $arguments['intake_board_id'] ?? null;
            if (!$intakeBoardId) {
                return ToolResult::error('VALIDATION_ERROR', 'intake_board_id ist erforderlich.');
            }

            $board = BrandsIntakeBoard::with([
                'sessions.steps.blockDefinition',
                'sessions.steps.boardBlock.blockDefinition',
                'boardBlocks.blockDefinition',
            ])->find($intakeBoardId);

            if (!$board) {
                return ToolResult::error('INTAKE_BOARD_NOT_FOUND', 'Das angegebene Intake Board wurde nicht gefunden.');
            }

            // Policy pruefen
            try {
                Gate::forUser($context->user)->authorize('view', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Intake Board (Policy).');
            }

            $format = $arguments['format'] ?? 'summary';

            // Board Info
            $boardInfo = [
                'id' => $board->id,
                'uuid' => $board->uuid,
                'name' => $board->name,
                'description' => $board->description,
                'status' => $board->status,
                'brand_id' => $board->brand_id,
                'started_at' => $board->started_at?->toIso8601String(),
                'completed_at' => $board->completed_at?->toIso8601String(),
            ];

            // Block-Definitionen die im Board verwendet werden
            $blockDefinitions = $board->boardBlocks->map(function ($boardBlock) {
                $def = $boardBlock->blockDefinition;
                return [
                    'board_block_id' => $boardBlock->id,
                    'block_definition_id' => $def?->id,
                    'name' => $def?->name,
                    'block_type' => $def?->block_type,
                    'block_type_label' => $def?->getBlockTypeLabel(),
                    'sort_order' => $boardBlock->sort_order,
                    'is_required' => $boardBlock->is_required,
                ];
            })->values()->toArray();

            // Session Statistiken
            $sessions = $board->sessions;
            $totalSessions = $sessions->count();
            $completedSessions = $sessions->filter(function ($s) {
                return $s->completed_at !== null;
            })->count();
            $completionRate = $totalSessions > 0
                ? round(($completedSessions / $totalSessions) * 100, 1)
                : 0;

            // Antwort-Zusammenfassung pro Block
            $answersSummary = [];
            foreach ($board->boardBlocks as $boardBlock) {
                $def = $boardBlock->blockDefinition;
                if (!$def) {
                    continue;
                }

                $blockSteps = [];
                foreach ($sessions as $session) {
                    foreach ($session->steps as $step) {
                        $stepBlockDefId = $step->blockDefinition?->id
                            ?? $step->boardBlock?->blockDefinition?->id;

                        if ($stepBlockDefId === $def->id) {
                            $blockSteps[] = $step;
                        }
                    }
                }

                $totalResponses = count($blockSteps);
                $completedResponses = collect($blockSteps)->filter(fn ($s) => $s->is_completed)->count();
                $avgConfidence = $totalResponses > 0
                    ? round(collect($blockSteps)->avg('ai_confidence'), 2)
                    : null;
                $clarificationNeeded = collect($blockSteps)->filter(fn ($s) => $s->user_clarification_needed)->count();

                // Antworten sammeln
                $answers = collect($blockSteps)
                    ->filter(fn ($s) => $s->answers !== null)
                    ->map(fn ($s) => $s->answers)
                    ->values()
                    ->toArray();

                $answersSummary[] = [
                    'block_definition_id' => $def->id,
                    'block_definition_name' => $def->name,
                    'block_type' => $def->block_type,
                    'sort_order' => $boardBlock->sort_order,
                    'total_responses' => $totalResponses,
                    'completed_responses' => $completedResponses,
                    'avg_ai_confidence' => $avgConfidence,
                    'clarification_needed_count' => $clarificationNeeded,
                    'answers' => $answers,
                ];
            }

            return ToolResult::success([
                'board' => $boardInfo,
                'block_definitions' => $blockDefinitions,
                'session_statistics' => [
                    'total_sessions' => $totalSessions,
                    'completed_sessions' => $completedSessions,
                    'completion_rate_percent' => $completionRate,
                ],
                'answers_summary' => $answersSummary,
                'format' => $format,
                'message' => "Export fuer Intake Board \"{$board->name}\": {$totalSessions} Session(s), {$completionRate}% Completion Rate."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Exportieren der Intake Ergebnisse: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'intake', 'export', 'results', 'summary'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}

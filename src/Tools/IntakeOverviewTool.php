<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;

/**
 * Tool das eine Uebersicht ueber das Intake Board System liefert
 */
class IntakeOverviewTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.intake.overview';
    }

    public function getDescription(): string
    {
        return 'Gibt eine Uebersicht ueber das Intake Board System: Konzepte (IntakeBoards, BlockDefinitions, Sessions, Steps), verfuegbare Tools und typische Workflows. Keine Parameter erforderlich.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            return ToolResult::success([
                'system_description' => 'Das Intake Board System ermoeglicht es, strukturierte Datenerhebungen (Intake Formulare) fuer Marken zu erstellen und auszuwerten. Es besteht aus vier Hauptkomponenten.',
                'concepts' => [
                    'IntakeBoard' => [
                        'description' => 'Ein Intake Board ist eine Erhebung/Fragebogen, der einer Marke (Brand) zugeordnet ist. Es hat einen Status (draft, published, closed), kann KI-Persoenlichkeit und Branchenkontext enthalten, und generiert bei Veroeffentlichung einen oeffentlichen Link.',
                        'key_fields' => ['name', 'description', 'status', 'ai_personality', 'industry_context', 'ai_instructions', 'public_token'],
                        'statuses' => [
                            'draft' => 'Erhebung wird vorbereitet, nicht oeffentlich zugaenglich',
                            'published' => 'Erhebung ist live und nimmt Antworten entgegen',
                            'closed' => 'Erhebung ist beendet/archiviert',
                        ],
                    ],
                    'IntakeBlockDefinition' => [
                        'description' => 'Eine Block-Definition ist eine team-weite Vorlage fuer einen Frage-/Eingabeblock. Sie definiert den Typ (text, email, select, etc.), KI-Prompts, Validierungsregeln und Fallback-Fragen. Block-Definitionen koennen in mehreren Boards wiederverwendet werden.',
                        'key_fields' => ['name', 'block_type', 'ai_prompt', 'validation_rules', 'fallback_questions', 'response_format'],
                        'block_types' => ['text', 'long_text', 'email', 'phone', 'url', 'select', 'multi_select', 'number', 'scale', 'date', 'boolean', 'file', 'rating', 'location', 'info', 'custom'],
                    ],
                    'IntakeSession' => [
                        'description' => 'Eine Session repraesentiert eine einzelne Beantwortung/Durchfuehrung eines Intake Boards. Jeder Respondent erhaelt eine eigene Session mit einem eindeutigen Token (XXXX-XXXX Format). Sessions enthalten verschluesselte Antworten und Respondent-Daten.',
                        'key_fields' => ['session_token', 'status', 'respondent_name', 'respondent_email', 'answers', 'current_step', 'started_at', 'completed_at'],
                        'note' => 'Respondent-Daten (Name, Email, Antworten) sind verschluesselt gespeichert und werden bei Abfrage automatisch entschluesselt.',
                    ],
                    'IntakeStep' => [
                        'description' => 'Ein Step repraesentiert die Beantwortung eines einzelnen Blocks innerhalb einer Session. Er enthaelt die Antworten, KI-Interpretation, Konfidenz-Score und ggf. Klaerungsbedarf.',
                        'key_fields' => ['answers', 'ai_interpretation', 'ai_confidence', 'ai_suggestions', 'user_clarification_needed', 'is_completed', 'message_count'],
                    ],
                ],
                'available_tools' => [
                    'boards' => [
                        'brands.intake_boards.GET' => 'Listet Intake Boards einer Marke auf',
                        'brands.intake_board.GET' => 'Ruft ein einzelnes Intake Board ab',
                        'brands.intake_boards.POST' => 'Erstellt ein neues Intake Board',
                        'brands.intake_boards.PUT' => 'Aktualisiert ein Intake Board',
                        'brands.intake_boards.DELETE' => 'Loescht ein Intake Board',
                    ],
                    'block_definitions' => [
                        'brands.intake_block_definitions.GET' => 'Listet Block-Definitionen auf',
                        'brands.intake_block_definitions.POST' => 'Erstellt eine Block-Definition',
                        'brands.intake_block_definitions.PUT' => 'Aktualisiert eine Block-Definition',
                        'brands.intake_block_definitions.DELETE' => 'Loescht eine Block-Definition',
                        'brands.intake_block_definitions.BULK_POST' => 'Erstellt mehrere Block-Definitionen auf einmal',
                        'brands.intake_block_definitions.BULK_PUT' => 'Aktualisiert mehrere Block-Definitionen auf einmal',
                        'brands.intake_block_definitions.BULK_DELETE' => 'Loescht mehrere Block-Definitionen auf einmal',
                    ],
                    'sessions' => [
                        'brands.intake_sessions.GET' => 'Listet Sessions eines Intake Boards auf',
                        'brands.intake_session.GET' => 'Ruft eine einzelne Session ab inkl. Steps und entschluesselten Antworten',
                    ],
                    'steps' => [
                        'brands.intake_steps.GET' => 'Listet Steps einer Session auf',
                        'brands.intake_step.GET' => 'Ruft einen einzelnen Step ab',
                    ],
                    'export' => [
                        'brands.intake_results.export' => 'Exportiert/aggregiert Ergebnisse eines Intake Boards',
                    ],
                ],
                'typical_workflow' => [
                    '1. Board erstellen' => 'brands.intake_boards.POST mit brand_id, name, ai_personality',
                    '2. Block-Definitionen erstellen' => 'brands.intake_block_definitions.POST oder .BULK_POST fuer die Fragen/Eingabefelder',
                    '3. Blocks dem Board zuordnen' => 'brands.intake_board_blocks.POST um Block-Definitionen mit dem Board zu verknuepfen',
                    '4. Board veroeffentlichen' => 'brands.intake_boards.PUT mit status=published',
                    '5. Sessions einsehen' => 'brands.intake_sessions.GET um eingegangene Antworten zu sehen',
                    '6. Ergebnisse exportieren' => 'brands.intake_results.export fuer eine aggregierte Auswertung',
                ],
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Intake Uebersicht: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'intake', 'overview', 'help'],
            'read_only' => true,
            'requires_auth' => false,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}

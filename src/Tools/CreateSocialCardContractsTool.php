<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSocialCard;
use Platform\Brands\Models\BrandsSocialCardContract;
use Platform\Brands\Models\BrandsSocialPlatformFormat;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Tool zum Generieren von Contracts für eine Social Card.
 *
 * ## Worker-Workflow
 *
 * 1. Social Card Master-Content laden (title, body_md, description)
 * 2. Selektierte Platform-Formate laden (inkl. Output-Schema, Rules, Personas, Tone of Voice)
 * 3. Contract pro Format generieren und gegen das Output-Schema validieren
 * 4. Contracts als brand_social_card_contracts speichern (status: ready)
 *
 * ## Contract-Generierung
 *
 * Der Worker nimmt den Master-Content der Social Card und transformiert ihn
 * gemäß dem Output-Schema des jeweiligen Platform-Formats:
 *
 * - **Output-Schema**: Definiert welche Felder der Contract haben muss (text, image_url, hashtags, etc.)
 *   mit Typ, max_length, required-Flag
 * - **Rules**: Soft-Constraints wie allows_links, hashtag_style, tone_adjustment, max_duration_seconds
 * - **Personas**: Zielgruppen-Kontext (demographics, pain_points, goals, behaviors, channels)
 *   für Ton und Ansprache
 *
 * ## Validierung
 *
 * Jeder generierte Contract wird gegen das Output-Schema validiert:
 * - Required-Felder müssen vorhanden sein
 * - String-Felder dürfen max_length nicht überschreiten
 * - Array-Felder müssen min_items/max_items einhalten
 * - Felder mit allowed=false dürfen nicht befüllt sein
 */
class CreateSocialCardContractsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.social_card_contracts.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/social_cards/{id}/contracts - Generiert Contracts für eine Social Card. '
            . 'Nimmt den Master-Content der Social Card und generiert pro selektiertem Platform-Format einen Contract. '
            . 'Der Contract wird gegen das Output-Schema des Formats validiert. '
            . 'REST-Parameter: social_card_id (required, integer) - Social Card-ID. '
            . 'platform_format_ids (required, array of integers) - IDs der Platform-Formate. '
            . 'contracts (required, array) - Array von Contract-Objekten, je eines pro platform_format_id. '
            . 'Jedes Contract-Objekt muss dem Output-Schema des jeweiligen Formats entsprechen. '
            . 'Workflow: 1) Social Card laden, 2) Formate mit Output-Schema + Rules + Personas laden via "brands.social_platform_format.GET", '
            . '3) Contracts generieren gemäß Schema, 4) Dieses Tool aufrufen um zu validieren und zu speichern.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'social_card_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Social Card (ERFORDERLICH). Nutze "brands.social_cards.GET" um Social Cards zu finden.'
                ],
                'platform_format_ids' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                    'description' => 'Array von Platform-Format-IDs (ERFORDERLICH). Nutze "brands.social_platform_formats.GET" um Formate zu finden.'
                ],
                'contracts' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'platform_format_id' => [
                                'type' => 'integer',
                                'description' => 'Platform-Format-ID für diesen Contract.'
                            ],
                            'contract' => [
                                'type' => 'object',
                                'description' => 'Der generierte Contract gemäß Output-Schema des Formats.'
                            ],
                        ],
                        'required' => ['platform_format_id', 'contract']
                    ],
                    'description' => 'Array von Contract-Objekten, eines pro Platform-Format. Jeder Contract muss dem Output-Schema des Formats entsprechen.'
                ],
            ],
            'required' => ['social_card_id', 'platform_format_ids', 'contracts']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            // Social Card laden
            $socialCardId = $arguments['social_card_id'] ?? null;
            if (!$socialCardId) {
                return ToolResult::error('VALIDATION_ERROR', 'social_card_id ist erforderlich.');
            }

            $socialCard = BrandsSocialCard::with(['socialBoard'])->find($socialCardId);
            if (!$socialCard) {
                return ToolResult::error('SOCIAL_CARD_NOT_FOUND', 'Die angegebene Social Card wurde nicht gefunden.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $socialCard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Contracts für diese Social Card erstellen (Policy).');
            }

            $formatIds = $arguments['platform_format_ids'] ?? [];
            $contractsInput = $arguments['contracts'] ?? [];

            if (empty($formatIds)) {
                return ToolResult::error('VALIDATION_ERROR', 'platform_format_ids darf nicht leer sein.');
            }

            if (empty($contractsInput)) {
                return ToolResult::error('VALIDATION_ERROR', 'contracts darf nicht leer sein. Pro Format muss ein Contract-Objekt übergeben werden.');
            }

            // Formate laden
            $formats = BrandsSocialPlatformFormat::with(['platform'])->whereIn('id', $formatIds)->get();
            if ($formats->count() !== count($formatIds)) {
                $foundIds = $formats->pluck('id')->toArray();
                $missingIds = array_diff($formatIds, $foundIds);
                return ToolResult::error('FORMAT_NOT_FOUND', 'Folgende Platform-Format-IDs wurden nicht gefunden: ' . implode(', ', $missingIds));
            }

            // Contracts-Input als Map aufbauen
            $contractMap = [];
            foreach ($contractsInput as $c) {
                $contractMap[$c['platform_format_id']] = $c['contract'];
            }

            // Validierung + Speicherung in Transaction
            $results = [];
            $errors = [];

            DB::beginTransaction();
            try {
                foreach ($formats as $format) {
                    $contractData = $contractMap[$format->id] ?? null;
                    if ($contractData === null) {
                        $errors[] = [
                            'platform_format_id' => $format->id,
                            'format_name' => $format->name,
                            'error' => 'Kein Contract-Objekt für dieses Format übergeben.',
                        ];
                        continue;
                    }

                    // Gegen Output-Schema validieren
                    $validationErrors = $this->validateContractAgainstSchema($contractData, $format->output_schema ?? []);
                    if (!empty($validationErrors)) {
                        $errors[] = [
                            'platform_format_id' => $format->id,
                            'format_name' => $format->name,
                            'platform' => $format->platform->name ?? null,
                            'validation_errors' => $validationErrors,
                        ];
                        continue;
                    }

                    // Existierenden Contract updaten oder neuen erstellen
                    $contract = BrandsSocialCardContract::updateOrCreate(
                        [
                            'social_card_id' => $socialCard->id,
                            'platform_format_id' => $format->id,
                        ],
                        [
                            'contract' => $contractData,
                            'status' => BrandsSocialCardContract::STATUS_READY,
                            'error_message' => null,
                            'team_id' => $socialCard->team_id,
                        ]
                    );

                    $contract->load(['platformFormat.platform']);

                    $results[] = [
                        'contract_id' => $contract->id,
                        'uuid' => $contract->uuid,
                        'social_card_id' => $contract->social_card_id,
                        'platform_format_id' => $contract->platform_format_id,
                        'platform_name' => $contract->platformFormat->platform->name ?? null,
                        'format_name' => $contract->platformFormat->name ?? null,
                        'format_key' => $contract->platformFormat->key ?? null,
                        'status' => $contract->status,
                        'contract' => $contract->contract,
                        'created_at' => $contract->created_at->toIso8601String(),
                    ];
                }

                if (!empty($errors) && empty($results)) {
                    DB::rollBack();
                    return ToolResult::error('VALIDATION_ERROR', json_encode([
                        'message' => 'Alle Contracts haben Validierungsfehler.',
                        'errors' => $errors,
                    ]));
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            return ToolResult::success([
                'contracts' => $results,
                'errors' => $errors,
                'created_count' => count($results),
                'error_count' => count($errors),
                'social_card_id' => $socialCard->id,
                'message' => count($results) . ' Contract(s) erfolgreich erstellt/aktualisiert.'
                    . (count($errors) > 0 ? ' ' . count($errors) . ' Contract(s) mit Validierungsfehlern.' : ''),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der Contracts: ' . $e->getMessage());
        }
    }

    /**
     * Validiert einen Contract gegen das Output-Schema eines Platform-Formats.
     */
    private function validateContractAgainstSchema(array $contractData, array $schema): array
    {
        $errors = [];

        if (empty($schema)) {
            return $errors;
        }

        foreach ($schema as $fieldName => $fieldDef) {
            if (!is_array($fieldDef)) {
                continue;
            }

            $isRequired = $fieldDef['required'] ?? false;
            $isAllowed = $fieldDef['allowed'] ?? true;
            $value = $contractData[$fieldName] ?? null;

            // Prüfe allowed=false
            if ($isAllowed === false && $value !== null && $value !== '') {
                $errors[] = "Feld '{$fieldName}' ist auf dieser Plattform nicht erlaubt (allowed=false).";
                continue;
            }

            // Prüfe required
            if ($isRequired && ($value === null || $value === '')) {
                $errors[] = "Pflichtfeld '{$fieldName}' fehlt oder ist leer.";
                continue;
            }

            if ($value === null) {
                continue;
            }

            $type = $fieldDef['type'] ?? 'string';

            // Typ-spezifische Validierung
            if ($type === 'string' && is_string($value)) {
                $maxLength = $fieldDef['max_length'] ?? null;
                if ($maxLength !== null && mb_strlen($value) > $maxLength) {
                    $errors[] = "Feld '{$fieldName}' überschreitet max_length von {$maxLength} (aktuell: " . mb_strlen($value) . ").";
                }
            }

            if ($type === 'array' && is_array($value)) {
                $minItems = $fieldDef['min_items'] ?? null;
                $maxItems = $fieldDef['max_items'] ?? null;

                if ($minItems !== null && count($value) < $minItems) {
                    $errors[] = "Feld '{$fieldName}' hat zu wenige Einträge (min: {$minItems}, aktuell: " . count($value) . ").";
                }
                if ($maxItems !== null && count($value) > $maxItems) {
                    $errors[] = "Feld '{$fieldName}' hat zu viele Einträge (max: {$maxItems}, aktuell: " . count($value) . ").";
                }
            }
        }

        return $errors;
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'social_card', 'contract', 'create', 'generate'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => true,
            'side_effects' => ['creates', 'updates'],
        ];
    }
}

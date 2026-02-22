<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsSocialCardContract;
use Platform\Brands\Models\BrandsSocialPlatformFormat;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum manuellen Anpassen eines Social Card Contracts.
 *
 * Erlaubt das Überschreiben des generierten Contract-Inhalts.
 * Der Contract wird erneut gegen das Output-Schema validiert.
 */
class UpdateSocialCardContractTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.social_card_contracts.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/social_card_contracts/{id} - Aktualisiert einen Social Card Contract manuell. '
            . 'Erlaubt das Anpassen des generierten Inhalts vor dem Publishing. '
            . 'Der Contract wird erneut gegen das Output-Schema validiert. '
            . 'REST-Parameter: contract_id (required, integer) - Contract-ID. '
            . 'contract (optional, object) - Neuer Contract-Inhalt. '
            . 'status (optional, string) - Neuer Status: draft|ready.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'contract_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Contracts (ERFORDERLICH). Nutze "brands.social_card_contracts.GET" um Contracts zu finden.'
                ],
                'contract' => [
                    'type' => 'object',
                    'description' => 'Optional: Neuer Contract-Inhalt gemäß Output-Schema des Formats.'
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['draft', 'ready'],
                    'description' => 'Optional: Contract-Status auf draft oder ready setzen.'
                ],
            ],
            'required' => ['contract_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Nutze standardisierte ID-Validierung
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'contract_id',
                BrandsSocialCardContract::class,
                'CONTRACT_NOT_FOUND',
                'Der angegebene Contract wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $contract = $validation['model'];

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $contract);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diesen Contract nicht bearbeiten (Policy).');
            }

            // Bereits published Contracts dürfen nicht mehr geändert werden
            if ($contract->status === BrandsSocialCardContract::STATUS_PUBLISHED) {
                return ToolResult::error('VALIDATION_ERROR', 'Ein bereits veröffentlichter Contract kann nicht mehr geändert werden.');
            }

            $updateData = [];

            if (isset($arguments['contract'])) {
                // Gegen Output-Schema validieren
                $format = BrandsSocialPlatformFormat::find($contract->platform_format_id);
                if ($format && !empty($format->output_schema)) {
                    $validationErrors = $this->validateContractAgainstSchema($arguments['contract'], $format->output_schema);
                    if (!empty($validationErrors)) {
                        return ToolResult::error('VALIDATION_ERROR', json_encode([
                            'message' => 'Contract entspricht nicht dem Output-Schema.',
                            'validation_errors' => $validationErrors,
                        ]));
                    }
                }
                $updateData['contract'] = $arguments['contract'];
            }

            if (isset($arguments['status'])) {
                $updateData['status'] = $arguments['status'];
            }

            if (!empty($updateData)) {
                // Bei Contract-Änderung Error-Message zurücksetzen
                if (isset($updateData['contract'])) {
                    $updateData['error_message'] = null;
                }
                $contract->update($updateData);
            }

            $contract->refresh();
            $contract->load(['platformFormat.platform', 'socialCard']);

            return ToolResult::success([
                'contract_id' => $contract->id,
                'uuid' => $contract->uuid,
                'social_card_id' => $contract->social_card_id,
                'social_card_title' => $contract->socialCard->title ?? null,
                'platform_format_id' => $contract->platform_format_id,
                'platform_name' => $contract->platformFormat->platform->name ?? null,
                'format_name' => $contract->platformFormat->name ?? null,
                'contract' => $contract->contract,
                'status' => $contract->status,
                'updated_at' => $contract->updated_at->toIso8601String(),
                'message' => "Contract erfolgreich aktualisiert.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Contracts: ' . $e->getMessage());
        }
    }

    /**
     * Validiert einen Contract gegen das Output-Schema eines Platform-Formats.
     */
    private function validateContractAgainstSchema(array $contractData, array $schema): array
    {
        $errors = [];

        foreach ($schema as $fieldName => $fieldDef) {
            if (!is_array($fieldDef)) {
                continue;
            }

            $isRequired = $fieldDef['required'] ?? false;
            $isAllowed = $fieldDef['allowed'] ?? true;
            $value = $contractData[$fieldName] ?? null;

            if ($isAllowed === false && $value !== null && $value !== '') {
                $errors[] = "Feld '{$fieldName}' ist auf dieser Plattform nicht erlaubt (allowed=false).";
                continue;
            }

            if ($isRequired && ($value === null || $value === '')) {
                $errors[] = "Pflichtfeld '{$fieldName}' fehlt oder ist leer.";
                continue;
            }

            if ($value === null) {
                continue;
            }

            $type = $fieldDef['type'] ?? 'string';

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
            'tags' => ['brands', 'social_card', 'contract', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => true,
            'side_effects' => ['updates'],
        ];
    }
}

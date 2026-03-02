<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsLookup;
use Platform\Brands\Models\BrandsLookupValue;

class UpdateBrandsLookupValueTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.lookup_values.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/lookup_values/{id} - Aktualisiert einen Lookup-Wert. Parameter: lookup_value_id (required, integer). label, order, is_active, meta (alle optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'lookup_value_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Lookup-Werts (ERFORDERLICH).',
                ],
                'label' => [
                    'type' => 'string',
                    'description' => 'Neuer Anzeigename.',
                ],
                'order' => [
                    'type' => 'integer',
                    'description' => 'Neue Sortierung.',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Aktiv/Inaktiv setzen.',
                ],
                'meta' => [
                    'type' => 'object',
                    'description' => 'Neue Metadaten (überschreibt komplett).',
                ],
            ],
            'required' => ['lookup_value_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $teamId = $context->team?->id ?? $context->user->current_team_id;
            if (!$teamId) {
                return ToolResult::error('TEAM_ERROR', 'Kein Team-Kontext gefunden.');
            }

            $valueId = $arguments['lookup_value_id'] ?? null;
            if (!$valueId) {
                return ToolResult::error('VALIDATION_ERROR', 'lookup_value_id ist erforderlich.');
            }

            $lookupValue = BrandsLookupValue::find($valueId);
            if (!$lookupValue) {
                return ToolResult::error('NOT_FOUND', 'Lookup-Wert nicht gefunden.');
            }

            // Team-Check: Lookup muss zum Team gehören
            $lookup = $lookupValue->lookup;
            if ($lookup->team_id !== $teamId) {
                return ToolResult::error('ACCESS_DENIED', 'Lookup-Wert gehört nicht zu diesem Team.');
            }

            $updateData = [];
            foreach (['label', 'order', 'is_active', 'meta'] as $field) {
                if (array_key_exists($field, $arguments)) {
                    $updateData[$field] = $arguments[$field];
                }
            }

            if (!empty($updateData)) {
                $lookupValue->update($updateData);
            }

            $lookupValue->refresh();

            return ToolResult::success([
                'id' => $lookupValue->id,
                'lookup_id' => $lookup->id,
                'lookup_name' => $lookup->name,
                'value' => $lookupValue->value,
                'label' => $lookupValue->label,
                'order' => $lookupValue->order,
                'is_active' => $lookupValue->is_active,
                'meta' => $lookupValue->meta,
                'message' => "Lookup-Wert '{$lookupValue->label}' erfolgreich aktualisiert.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Lookup-Werts: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'lookups', 'values', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'write',
            'idempotent' => true,
        ];
    }
}

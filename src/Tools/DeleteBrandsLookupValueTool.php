<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsLookupValue;

class DeleteBrandsLookupValueTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.lookup_values.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/lookup_values/{id} - Löscht einen Lookup-Wert. Parameter: lookup_value_id (required, integer). Alternativ: Nutze brands.lookup_values.PUT mit is_active=false um den Wert zu deaktivieren statt zu löschen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'lookup_value_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden Lookup-Werts.',
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

            // Team-Check
            $lookup = $lookupValue->lookup;
            if ($lookup->team_id !== $teamId) {
                return ToolResult::error('ACCESS_DENIED', 'Lookup-Wert gehört nicht zu diesem Team.');
            }

            $label = $lookupValue->label;
            $lookupName = $lookup->label;
            $lookupValue->delete();

            return ToolResult::success([
                'deleted' => true,
                'message' => "Lookup-Wert '{$label}' aus '{$lookupName}' gelöscht.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Lookup-Werts: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'lookups', 'values', 'delete'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'write',
            'idempotent' => true,
        ];
    }
}

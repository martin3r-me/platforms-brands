<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsLookup;
use Platform\Brands\Models\BrandsLookupValue;

class CreateBrandsLookupValueTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.lookup_values.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/lookups/{name}/values - Fügt einen neuen Wert zu einer Lookup-Tabelle hinzu. Parameter: name (required) - Lookup-Name, value (required) - Slug/Key, label (required) - Anzeigename, order (optional, integer), meta (optional, object).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Name/Slug der Lookup-Tabelle (z.B. "content_type").',
                ],
                'value' => [
                    'type' => 'string',
                    'description' => 'Slug/Key des neuen Werts (z.B. "podcast").',
                ],
                'label' => [
                    'type' => 'string',
                    'description' => 'Anzeigename (z.B. "Podcast").',
                ],
                'order' => [
                    'type' => 'integer',
                    'description' => 'Sortierung. Standard: nächste freie Position.',
                ],
                'meta' => [
                    'type' => 'object',
                    'description' => 'Optionale Metadaten als JSON-Objekt.',
                ],
            ],
            'required' => ['name', 'value', 'label'],
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

            $name = $arguments['name'] ?? null;
            $value = $arguments['value'] ?? null;
            $label = $arguments['label'] ?? null;

            if (!$name || !$value || !$label) {
                return ToolResult::error('VALIDATION_ERROR', 'name, value und label sind erforderlich.');
            }

            $lookup = BrandsLookup::resolve($name, $teamId);
            if (!$lookup) {
                return ToolResult::error('LOOKUP_NOT_FOUND', "Lookup '{$name}' wurde für dieses Team nicht gefunden.");
            }

            // Prüfe ob Wert schon existiert
            if ($lookup->values()->where('value', $value)->exists()) {
                return ToolResult::error('DUPLICATE_VALUE', "Wert '{$value}' existiert bereits in Lookup '{$name}'.");
            }

            $order = $arguments['order'] ?? ($lookup->values()->max('order') ?? 0) + 1;

            $lookupValue = BrandsLookupValue::create([
                'lookup_id' => $lookup->id,
                'value' => $value,
                'label' => $label,
                'order' => $order,
                'is_active' => true,
                'meta' => $arguments['meta'] ?? null,
            ]);

            return ToolResult::success([
                'id' => $lookupValue->id,
                'lookup_id' => $lookup->id,
                'lookup_name' => $lookup->name,
                'value' => $lookupValue->value,
                'label' => $lookupValue->label,
                'order' => $lookupValue->order,
                'is_active' => $lookupValue->is_active,
                'meta' => $lookupValue->meta,
                'message' => "Lookup-Wert '{$label}' erfolgreich zu '{$lookup->label}' hinzugefügt.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Lookup-Werts: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'lookups', 'values', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'write',
            'idempotent' => false,
        ];
    }
}

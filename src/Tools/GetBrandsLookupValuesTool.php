<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsLookup;

class GetBrandsLookupValuesTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.lookup_values.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/lookups/{name}/values - Gibt alle Werte einer Lookup-Tabelle zurück. Parameter: name (required, string) - Name der Lookup (z.B. "content_type", "search_intent", "content_brief_status"). include_inactive (optional, boolean) - auch inaktive Werte anzeigen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Name/Slug der Lookup-Tabelle (z.B. "content_type", "search_intent", "content_brief_status").',
                ],
                'include_inactive' => [
                    'type' => 'boolean',
                    'description' => 'Auch inaktive Werte anzeigen. Standard: false.',
                ],
            ],
            'required' => ['name'],
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
            if (!$name) {
                return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');
            }

            $lookup = BrandsLookup::resolve($name, $teamId);
            if (!$lookup) {
                return ToolResult::error('LOOKUP_NOT_FOUND', "Lookup '{$name}' wurde für dieses Team nicht gefunden.");
            }

            $includeInactive = (bool) ($arguments['include_inactive'] ?? false);

            $valuesQuery = $includeInactive ? $lookup->values() : $lookup->activeValues();
            $values = $valuesQuery->get();

            return ToolResult::success([
                'lookup_id' => $lookup->id,
                'lookup_name' => $lookup->name,
                'lookup_label' => $lookup->label,
                'is_system' => $lookup->is_system,
                'values' => $values->map(fn($v) => [
                    'id' => $v->id,
                    'value' => $v->value,
                    'label' => $v->label,
                    'order' => $v->order,
                    'is_active' => $v->is_active,
                    'meta' => $v->meta,
                ])->toArray(),
                'total' => $values->count(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Lookup-Werte: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'lookups', 'values', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'read',
            'idempotent' => true,
        ];
    }
}

<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsLookup;

class ListBrandsLookupsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.lookups.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/lookups - Listet alle Lookup-Tabellen (content_type, search_intent, content_brief_status etc.) für das aktuelle Team auf. Optional mit include_values=true werden auch die Werte je Lookup zurückgegeben.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'include_values' => [
                    'type' => 'boolean',
                    'description' => 'Wenn true, werden die Werte je Lookup mitgeliefert. Standard: false.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Filtert auf eine bestimmte Lookup (z.B. "content_type").',
                ],
            ],
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

            $query = BrandsLookup::forTeam($teamId);

            if (!empty($arguments['name'])) {
                $query->where('name', $arguments['name']);
            }

            $includeValues = (bool) ($arguments['include_values'] ?? false);

            if ($includeValues) {
                $query->with('activeValues');
            }

            $lookups = $query->orderBy('name')->get();

            $data = $lookups->map(function ($lookup) use ($includeValues) {
                $item = [
                    'id' => $lookup->id,
                    'name' => $lookup->name,
                    'label' => $lookup->label,
                    'description' => $lookup->description,
                    'is_system' => $lookup->is_system,
                    'values_count' => $lookup->activeValues()->count(),
                ];

                if ($includeValues) {
                    $item['values'] = $lookup->activeValues->map(fn($v) => [
                        'id' => $v->id,
                        'value' => $v->value,
                        'label' => $v->label,
                        'order' => $v->order,
                        'is_active' => $v->is_active,
                        'meta' => $v->meta,
                    ])->toArray();
                }

                return $item;
            })->toArray();

            return ToolResult::success([
                'lookups' => $data,
                'total' => count($data),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Lookups: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'lookups', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'read',
            'idempotent' => true,
        ];
    }
}

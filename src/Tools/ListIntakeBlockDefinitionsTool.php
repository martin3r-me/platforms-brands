<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsIntakeBlockDefinition;
use Illuminate\Support\Facades\Gate;

/**
 * Tool zum Auflisten von Intake Block-Definitionen (team-weit)
 */
class ListIntakeBlockDefinitionsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.intake_block_definitions.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/intake_block_definitions?team_id={id}&filters=[...]&search=...&sort=[...] - Listet Intake Block-Definitionen auf (team-weit, nicht brand-spezifisch). REST-Parameter: team_id (optional, integer) - Filter nach Team-ID. Wenn nicht angegeben, wird automatisch das aktuelle Team aus dem Kontext verwendet. filters (optional, array) - Filter-Array (z.B. block_type, is_active). search (optional, string) - Suchbegriff. sort (optional, array) - Sortierung. limit/offset (optional) - Pagination.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'team_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (optional): Filter nach Team-ID. Wenn nicht angegeben, wird automatisch das aktuelle Team aus dem Kontext verwendet.'
                    ],
                    'block_type' => [
                        'type' => 'string',
                        'description' => 'REST-Parameter (optional): Filter nach Block-Typ. Erlaubte Werte: text, long_text, email, phone, url, select, multi_select, number, scale, date, boolean, file, rating, location, info, custom.',
                        'enum' => BrandsIntakeBlockDefinition::BLOCK_TYPES
                    ],
                    'is_active' => [
                        'type' => 'boolean',
                        'description' => 'REST-Parameter (optional): Filter nach Aktiv-Status. true = nur aktive, false = nur inaktive.'
                    ],
                ]
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            // Team-Filter bestimmen
            $teamIdArg = $arguments['team_id'] ?? null;
            if ($teamIdArg === 0 || $teamIdArg === '0') {
                $teamIdArg = null;
            }

            if ($teamIdArg === null) {
                $teamIdArg = $context->team?->id;
            }

            if (!$teamIdArg) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team angegeben und kein Team im Kontext gefunden.');
            }

            // Pruefen, ob User Zugriff auf dieses Team hat
            $userHasAccess = $context->user->teams()->where('teams.id', $teamIdArg)->exists();
            if (!$userHasAccess) {
                return ToolResult::error('ACCESS_DENIED', "Du hast keinen Zugriff auf Team-ID {$teamIdArg}.");
            }

            // Query aufbauen
            $query = BrandsIntakeBlockDefinition::query()
                ->where('team_id', $teamIdArg)
                ->with(['user', 'team']);

            // Optionaler Filter nach block_type
            if (isset($arguments['block_type'])) {
                $query->where('block_type', $arguments['block_type']);
            }

            // Optionaler Filter nach is_active
            if (isset($arguments['is_active'])) {
                $query->where('is_active', (bool) $arguments['is_active']);
            }

            // Standard-Operationen anwenden
            $this->applyStandardFilters($query, $arguments, [
                'name', 'description', 'block_type', 'is_active', 'created_at', 'updated_at'
            ]);

            // Standard-Suche anwenden
            $this->applyStandardSearch($query, $arguments, ['name', 'description']);

            // Standard-Sortierung anwenden
            $this->applyStandardSort($query, $arguments, [
                'name', 'block_type', 'is_active', 'created_at', 'updated_at'
            ], 'name', 'asc');

            // Standard-Pagination anwenden
            $this->applyStandardPagination($query, $arguments);

            // Block-Definitionen holen und per Policy filtern
            $blockDefinitions = $query->get()->filter(function ($blockDefinition) use ($context) {
                try {
                    return Gate::forUser($context->user)->allows('view', $blockDefinition);
                } catch (\Throwable $e) {
                    return false;
                }
            })->values();

            // Block-Definitionen formatieren
            $blockDefinitionsList = $blockDefinitions->map(function ($blockDefinition) {
                return [
                    'id' => $blockDefinition->id,
                    'uuid' => $blockDefinition->uuid,
                    'name' => $blockDefinition->name,
                    'block_type' => $blockDefinition->block_type,
                    'block_type_label' => $blockDefinition->getBlockTypeLabel(),
                    'description' => $blockDefinition->description,
                    'is_active' => $blockDefinition->is_active,
                    'team_id' => $blockDefinition->team_id,
                    'user_id' => $blockDefinition->user_id,
                    'created_at' => $blockDefinition->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'intake_block_definitions' => $blockDefinitionsList,
                'count' => count($blockDefinitionsList),
                'team_id' => $teamIdArg,
                'message' => count($blockDefinitionsList) > 0
                    ? count($blockDefinitionsList) . ' Block-Definition(en) gefunden (Team-ID: ' . $teamIdArg . ').'
                    : 'Keine Block-Definitionen gefunden fuer Team-ID: ' . $teamIdArg . '.'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Block-Definitionen: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'intake_block_definition', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}

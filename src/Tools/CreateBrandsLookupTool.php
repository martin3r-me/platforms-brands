<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsLookup;

class CreateBrandsLookupTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.lookups.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/lookups - Erstellt eine neue Lookup-Tabelle. Parameter: name (required, string) - Slug/Key, label (required, string) - Anzeigename, description (optional, string).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Slug/Key der Lookup (z.B. "page_priority", "content_format"). Muss pro Team einzigartig sein.',
                ],
                'label' => [
                    'type' => 'string',
                    'description' => 'Anzeigename (z.B. "Seiten-Priorität").',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optionale Beschreibung.',
                ],
            ],
            'required' => ['name', 'label'],
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
            $label = $arguments['label'] ?? null;

            if (!$name || !$label) {
                return ToolResult::error('VALIDATION_ERROR', 'name und label sind erforderlich.');
            }

            // Prüfe ob Lookup schon existiert
            if (BrandsLookup::where('team_id', $teamId)->where('name', $name)->exists()) {
                return ToolResult::error('DUPLICATE', "Lookup '{$name}' existiert bereits für dieses Team.");
            }

            $lookup = BrandsLookup::create([
                'team_id' => $teamId,
                'created_by_user_id' => $context->user->id,
                'name' => $name,
                'label' => $label,
                'description' => $arguments['description'] ?? null,
                'is_system' => false,
            ]);

            return ToolResult::success([
                'id' => $lookup->id,
                'name' => $lookup->name,
                'label' => $lookup->label,
                'description' => $lookup->description,
                'is_system' => $lookup->is_system,
                'team_id' => $lookup->team_id,
                'message' => "Lookup '{$lookup->label}' erfolgreich erstellt.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der Lookup: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'lookups', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'write',
            'idempotent' => false,
        ];
    }
}

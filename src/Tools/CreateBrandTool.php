<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolDependencyContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsBrand;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Erstellen von Marken im Brands-Modul
 */
class CreateBrandTool implements ToolContract, ToolDependencyContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.brands.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands - Erstellt eine neue Marke. REST-Parameter: name (required, string) - Markenname. team_id (optional, integer) - wenn nicht angegeben, wird aktuelles Team verwendet. description (optional, string) - Beschreibung.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Name der Marke (ERFORDERLICH). Frage den Nutzer explizit nach dem Namen, wenn er nicht angegeben wurde.'
                ],
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: ID des Teams, in dem die Marke erstellt werden soll. Wenn nicht angegeben, wird das aktuelle Team aus dem Kontext verwendet.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Beschreibung der Marke. Frage nach, wenn der Nutzer eine Marke erstellt, aber keine Beschreibung angegeben hat.'
                ],
            ],
            'required' => ['name']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Validierung
            if (empty($arguments['name'])) {
                return ToolResult::error('VALIDATION_ERROR', 'Markenname ist erforderlich');
            }
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            // Team bestimmen: aus Argumenten oder Context
            $teamId = $arguments['team_id'] ?? null;
            if ($teamId === 0 || $teamId === '0') {
                $teamId = null;
            }
            
            $team = null;
            if (!empty($teamId)) {
                $team = $context->user->teams()->find($teamId);
                if (!$team) {
                    return ToolResult::error('TEAM_NOT_FOUND', 'Das angegebene Team wurde nicht gefunden oder du hast keinen Zugriff darauf.');
                }
            } else {
                $team = $context->team;
                if (!$team) {
                    return ToolResult::error('MISSING_TEAM', 'Kein Team angegeben und kein Team im Kontext gefunden. Marken benötigen ein Team.');
                }
            }

            // Policy: Marke erstellen
            try {
                Gate::forUser($context->user)->authorize('create', BrandsBrand::class);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Marken erstellen (Policy).');
            }

            // Marke erstellen
            $brand = BrandsBrand::create([
                'name' => $arguments['name'],
                'description' => $arguments['description'] ?? null,
                'user_id' => $context->user->id,
                'team_id' => $team->id,
            ]);

            return ToolResult::success([
                'id' => $brand->id,
                'uuid' => $brand->uuid,
                'name' => $brand->name,
                'description' => $brand->description,
                'team_id' => $brand->team_id,
                'created_at' => $brand->created_at->toIso8601String(),
                'message' => "Marke '{$brand->name}' erfolgreich erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der Marke: ' . $e->getMessage());
        }
    }

    public function getDependencies(): array
    {
        return [
            'required_fields' => [],
            'dependencies' => []
        ];
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'brand', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}

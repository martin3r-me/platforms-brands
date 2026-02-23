<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsIntakeBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Erstellen von IntakeBoards im Brands-Modul
 */
class CreateIntakeBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.intake_boards.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/{brand_id}/intake_boards - Erstellt ein neues Intake Board (Erhebung) für eine Marke. REST-Parameter: brand_id (required, integer) - Marken-ID. name (optional, string) - Board-Name. description (optional, string) - Beschreibung. ai_personality (optional, string) - KI-Persönlichkeit. industry_context (optional, string) - Branchenkontext. ai_instructions (optional, array) - KI-Anweisungen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'brand_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Marke, zu der das Intake Board gehört (ERFORDERLICH). Nutze "brands.brands.GET" um Marken zu finden.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name des Intake Boards. Wenn nicht angegeben, wird automatisch "Neues Intake Board" verwendet.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Beschreibung des Intake Boards.'
                ],
                'ai_personality' => [
                    'type' => 'string',
                    'description' => 'KI-Persönlichkeit für das Intake Board.'
                ],
                'industry_context' => [
                    'type' => 'string',
                    'description' => 'Branchenkontext für das Intake Board.'
                ],
                'ai_instructions' => [
                    'type' => 'array',
                    'description' => 'KI-Anweisungen für das Intake Board (Array von Strings).'
                ],
            ],
            'required' => ['brand_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            // Brand finden
            $brandId = $arguments['brand_id'] ?? null;
            if (!$brandId) {
                return ToolResult::error('VALIDATION_ERROR', 'brand_id ist erforderlich.');
            }

            $brand = BrandsBrand::find($brandId);
            if (!$brand) {
                return ToolResult::error('BRAND_NOT_FOUND', 'Die angegebene Marke wurde nicht gefunden. Nutze "brands.brands.GET" um Marken zu finden.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $brand);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Intake Boards für diese Marke erstellen (Policy).');
            }

            $name = $arguments['name'] ?? 'Neues Intake Board';

            // IntakeBoard direkt erstellen
            $intakeBoard = BrandsIntakeBoard::create([
                'name' => $name,
                'description' => $arguments['description'] ?? null,
                'ai_personality' => $arguments['ai_personality'] ?? null,
                'industry_context' => $arguments['industry_context'] ?? null,
                'ai_instructions' => $arguments['ai_instructions'] ?? null,
                'user_id' => $context->user->id,
                'team_id' => $brand->team_id,
                'brand_id' => $brand->id,
            ]);

            $intakeBoard->load(['brand', 'user', 'team']);

            return ToolResult::success([
                'id' => $intakeBoard->id,
                'uuid' => $intakeBoard->uuid,
                'name' => $intakeBoard->name,
                'description' => $intakeBoard->description,
                'ai_personality' => $intakeBoard->ai_personality,
                'industry_context' => $intakeBoard->industry_context,
                'ai_instructions' => $intakeBoard->ai_instructions,
                'status' => $intakeBoard->status,
                'brand_id' => $intakeBoard->brand_id,
                'brand_name' => $intakeBoard->brand->name,
                'team_id' => $intakeBoard->team_id,
                'created_at' => $intakeBoard->created_at->toIso8601String(),
                'message' => "Intake Board '{$intakeBoard->name}' erfolgreich für Marke '{$intakeBoard->brand->name}' erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Intake Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'intake_board', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}

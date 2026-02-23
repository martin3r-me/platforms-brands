<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsIntakeBlockDefinition;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteIntakeBlockDefinitionTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.intake_block_definitions.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/intake_block_definitions/{id} - Loescht eine Intake Block-Definition. REST-Parameter: block_definition_id (required, integer) - Block-Definition-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'block_definition_id' => [
                    'type' => 'integer',
                    'description' => 'ID der zu loeschenden Block-Definition (ERFORDERLICH).'
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestaetigung.'
                ]
            ],
            'required' => ['block_definition_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments, $context, 'block_definition_id', BrandsIntakeBlockDefinition::class,
                'BLOCK_DEFINITION_NOT_FOUND', 'Die angegebene Block-Definition wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $blockDefinition = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('delete', $blockDefinition);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diese Block-Definition nicht loeschen (Policy).');
            }

            $blockDefinitionName = $blockDefinition->name;
            $blockDefinitionId = $blockDefinition->id;
            $teamId = $blockDefinition->team_id;

            $blockDefinition->delete();

            return ToolResult::success([
                'block_definition_id' => $blockDefinitionId,
                'block_definition_name' => $blockDefinitionName,
                'team_id' => $teamId,
                'message' => "Block-Definition '{$blockDefinitionName}' wurde erfolgreich geloescht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Loeschen der Block-Definition: ' . $e->getMessage());
        }
    }
}

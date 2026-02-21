<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsPersona;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeletePersonaTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.personas.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/personas/{id} - Löscht eine Persona. REST-Parameter: persona_id (required, integer) - Persona-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'persona_id' => [
                    'type' => 'integer',
                    'description' => 'ID der zu löschenden Persona (ERFORDERLICH). Nutze "brands.personas.GET" um Personas zu finden.'
                ],
            ],
            'required' => ['persona_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'persona_id',
                BrandsPersona::class,
                'PERSONA_NOT_FOUND',
                'Die angegebene Persona wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $persona = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('delete', $persona);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diese Persona nicht löschen.');
            }

            $personaName = $persona->name;
            $personaId = $persona->id;
            $boardId = $persona->persona_board_id;

            $persona->delete();

            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('brands.personas.GET', $context->user->id, $context->team?->id);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'persona_id' => $personaId,
                'persona_name' => $personaName,
                'persona_board_id' => $boardId,
                'message' => "Persona '{$personaName}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen der Persona: ' . $e->getMessage());
        }
    }
}

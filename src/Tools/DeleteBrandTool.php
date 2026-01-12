<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsBrand;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Löschen von Marken im Brands-Modul
 */
class DeleteBrandTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.brands.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/{id} - Löscht eine Marke. REST-Parameter: brand_id (required, integer) - Marken-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'brand_id' => [
                    'type' => 'integer',
                    'description' => 'ID der zu löschenden Marke (ERFORDERLICH). Nutze "brands.brands.GET" um Marken zu finden.'
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestätigung, dass die Marke wirklich gelöscht werden soll.'
                ]
            ],
            'required' => ['brand_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Nutze standardisierte ID-Validierung
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'brand_id',
                BrandsBrand::class,
                'BRAND_NOT_FOUND',
                'Die angegebene Marke wurde nicht gefunden.'
            );
            
            if ($validation['error']) {
                return $validation['error'];
            }
            
            $brand = $validation['model'];
            
            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('delete', $brand);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diese Marke nicht löschen (Policy).');
            }

            $brandName = $brand->name;
            $brandId = $brand->id;
            $teamId = $brand->team_id;

            // Marke löschen
            $brand->delete();

            // Cache invalidieren
            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('brands.brands.GET', $context->user->id, $teamId);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'brand_id' => $brandId,
                'brand_name' => $brandName,
                'message' => "Marke '{$brandName}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen der Marke: ' . $e->getMessage());
        }
    }
}

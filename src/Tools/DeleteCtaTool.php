<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsCta;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Löschen eines CTA (Call-to-Action) im Brands-Modul
 */
class DeleteCtaTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.ctas.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/ctas/{id} - Löscht einen CTA (Call-to-Action). REST-Parameter: cta_id (required, integer) - CTA-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'cta_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden CTA (ERFORDERLICH). Nutze "brands.ctas.GET" um CTAs zu finden.',
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestätigung, dass der CTA wirklich gelöscht werden soll.',
                ],
            ],
            'required' => ['cta_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Nutze standardisierte ID-Validierung
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'cta_id',
                BrandsCta::class,
                'CTA_NOT_FOUND',
                'Der angegebene CTA wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $cta = $validation['model'];

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('delete', $cta);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diesen CTA nicht löschen (Policy).');
            }

            $ctaLabel = $cta->label;
            $ctaId = $cta->id;
            $brandId = $cta->brand_id;
            $teamId = $cta->team_id;

            // CTA löschen
            $cta->delete();

            // Cache invalidieren
            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('brands.ctas.GET', $context->user->id, $teamId);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'cta_id' => $ctaId,
                'cta_label' => $ctaLabel,
                'brand_id' => $brandId,
                'message' => "CTA '{$ctaLabel}' wurde erfolgreich gelöscht.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des CTA: ' . $e->getMessage());
        }
    }
}

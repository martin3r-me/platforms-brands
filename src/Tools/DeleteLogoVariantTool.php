<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsLogoVariant;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteLogoVariantTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.logo_variants.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/logo_variants/{id} - Löscht eine Logo-Variante. REST-Parameter: variant_id (required, integer) - Varianten-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'variant_id' => [
                    'type' => 'integer',
                    'description' => 'ID der zu löschenden Logo-Variante (ERFORDERLICH). Nutze "brands.logo_variants.GET" um Varianten zu finden.'
                ],
            ],
            'required' => ['variant_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'variant_id',
                BrandsLogoVariant::class,
                'VARIANT_NOT_FOUND',
                'Die angegebene Logo-Variante wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $variant = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('delete', $variant);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diese Logo-Variante nicht löschen.');
            }

            $variantName = $variant->name;
            $variantId = $variant->id;
            $boardId = $variant->logo_board_id;

            $variant->delete();

            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('brands.logo_variants.GET', $context->user->id, $context->team?->id);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'variant_id' => $variantId,
                'variant_name' => $variantName,
                'logo_board_id' => $boardId,
                'message' => "Logo-Variante '{$variantName}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen der Logo-Variante: ' . $e->getMessage());
        }
    }
}

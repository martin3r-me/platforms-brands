<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsBrand;
use Platform\Crm\Models\CrmCompany;
use Platform\Crm\Models\CrmCompanyLink;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Verknüpfen einer Marke mit einem CRM-Unternehmen
 */
class LinkBrandCompanyTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.brand_companies.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/{brand_id}/companies - Verknüpft eine Marke mit einem CRM-Unternehmen. Parameter: brand_id (required, integer) - Marken-ID. company_id (required, integer) - CRM-Unternehmen-ID. Nutze "brands.brands.GET" um Marken zu finden und "crm.companies.GET" um Unternehmen zu finden.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'brand_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Marke (ERFORDERLICH). Nutze "brands.brands.GET" um Marken zu finden.'
                ],
                'company_id' => [
                    'type' => 'integer',
                    'description' => 'ID des CRM-Unternehmens (ERFORDERLICH). Nutze "crm.companies.GET" um Unternehmen zu finden.'
                ],
            ],
            'required' => ['brand_id', 'company_id'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            // Marke finden
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
                Gate::forUser($context->user)->authorize('update', $brand);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diese Marke nicht bearbeiten (Policy).');
            }

            $companyId = (int)($arguments['company_id'] ?? 0);
            if ($companyId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'company_id ist erforderlich.');
            }

            // Prüfen ob Company existiert über Resolver (loose coupling)
            $companyResolver = app(\Platform\Core\Contracts\CrmCompanyResolverInterface::class);
            $companyName = $companyResolver->displayName($companyId);
            if (!$companyName) {
                return ToolResult::error('COMPANY_NOT_FOUND', 'CRM-Unternehmen nicht gefunden.');
            }

            // Company über Links-Tabelle verknüpfen (über HasCompanyLinksTrait)
            $link = $brand->companyLinks()->firstOrCreate(
                [
                    'company_id' => $companyId,
                ],
                [
                    'team_id' => $context->team->id,
                    'created_by_user_id' => $context->user->id,
                ]
            );

            return ToolResult::success([
                'brand_id' => $brand->id,
                'brand_name' => $brand->name,
                'company_id' => $companyId,
                'company_name' => $companyName,
                'already_linked' => !$link->wasRecentlyCreated,
                'message' => 'CRM-Unternehmen mit Marke verknüpft.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Verknüpfen: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['brands', 'brand', 'crm', 'company', 'link'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}

<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsBrand;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmContactLink;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Verknüpfen einer Marke mit einem CRM-Kontakt
 */
class LinkBrandContactTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.brand_contacts.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/{brand_id}/contacts - Verknüpft eine Marke mit einem CRM-Kontakt. Parameter: brand_id (required, integer) - Marken-ID. contact_id (required, integer) - CRM-Kontakt-ID. Nutze "brands.brands.GET" um Marken zu finden und "crm.contacts.GET" um Kontakte zu finden.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'brand_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Marke (ERFORDERLICH). Nutze "brands.brands.GET" um Marken zu finden.'
                ],
                'contact_id' => [
                    'type' => 'integer',
                    'description' => 'ID des CRM-Kontakts (ERFORDERLICH). Nutze "crm.contacts.GET" um Kontakte zu finden.'
                ],
            ],
            'required' => ['brand_id', 'contact_id'],
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

            $contactId = (int)($arguments['contact_id'] ?? 0);
            if ($contactId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'contact_id ist erforderlich.');
            }

            // Prüfen ob Contact existiert über Resolver (loose coupling)
            $contactResolver = app(\Platform\Core\Contracts\CrmContactResolverInterface::class);
            $contactName = $contactResolver->displayName($contactId);
            if (!$contactName) {
                return ToolResult::error('CONTACT_NOT_FOUND', 'CRM-Kontakt nicht gefunden.');
            }

            // Contact über Links-Tabelle verknüpfen (über HasEmployeeContact Trait)
            $link = CrmContactLink::firstOrCreate(
                [
                    'contact_id' => $contactId,
                    'linkable_type' => BrandsBrand::class,
                    'linkable_id' => $brand->id,
                ],
                [
                    'team_id' => $context->team->id,
                    'created_by_user_id' => $context->user->id,
                ]
            );

            return ToolResult::success([
                'brand_id' => $brand->id,
                'brand_name' => $brand->name,
                'contact_id' => $contactId,
                'contact_name' => $contactName,
                'already_linked' => !$link->wasRecentlyCreated,
                'message' => 'CRM-Kontakt mit Marke verknüpft.',
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
            'tags' => ['brands', 'brand', 'crm', 'contact', 'link'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}

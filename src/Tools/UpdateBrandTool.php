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
 * Tool zum Bearbeiten von Marken im Brands-Modul
 */
class UpdateBrandTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.brands.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/{id} - Aktualisiert eine bestehende Marke. REST-Parameter: brand_id (required, integer) - Marken-ID. name (optional, string) - Markenname. description (optional, string) - Beschreibung. done (optional, boolean) - Marke als erledigt markieren.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'brand_id' => [
                    'type' => 'integer',
                    'description' => 'ID der zu bearbeitenden Marke (ERFORDERLICH). Nutze "brands.brands.GET" um Marken zu finden.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Name der Marke. Frage nach, wenn der Nutzer den Namen ändern möchte.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue Beschreibung der Marke. Frage nach, wenn der Nutzer die Beschreibung ändern möchte.'
                ],
                'done' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Marke als erledigt markieren. Frage nach, wenn der Nutzer die Marke abschließen möchte.'
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
                Gate::forUser($context->user)->authorize('update', $brand);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diese Marke nicht bearbeiten (Policy).');
            }

            // Update-Daten sammeln
            $updateData = [];

            if (isset($arguments['name'])) {
                $updateData['name'] = $arguments['name'];
            }

            if (isset($arguments['description'])) {
                $updateData['description'] = $arguments['description'];
            }

            if (isset($arguments['done'])) {
                $updateData['done'] = $arguments['done'];
                if ($arguments['done']) {
                    $updateData['done_at'] = now();
                } else {
                    $updateData['done_at'] = null;
                }
            }

            // Marke aktualisieren
            if (!empty($updateData)) {
                $brand->update($updateData);
            }

            $brand->refresh();
            $brand->load(['user', 'team', 'companyLinks.company', 'crmContactLinks.contact']);

            $company = $brand->getCompany();
            $contact = $brand->getContact();
            $companyResolver = app(\Platform\Core\Contracts\CrmCompanyResolverInterface::class);
            $contactResolver = app(\Platform\Core\Contracts\CrmContactResolverInterface::class);

            return ToolResult::success([
                'id' => $brand->id,
                'uuid' => $brand->uuid,
                'name' => $brand->name,
                'description' => $brand->description,
                'team_id' => $brand->team_id,
                'user_id' => $brand->user_id,
                'done' => $brand->done,
                'done_at' => $brand->done_at?->toIso8601String(),
                'updated_at' => $brand->updated_at->toIso8601String(),
                'company_id' => $company?->id,
                'company_name' => $company ? $companyResolver->displayName($company->id) : null,
                'contact_id' => $contact?->id,
                'contact_name' => $contact ? $contactResolver->displayName($contact->id) : null,
                'message' => "Marke '{$brand->name}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der Marke: ' . $e->getMessage());
        }
    }
}

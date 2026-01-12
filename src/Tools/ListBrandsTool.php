<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsBrand;
use Illuminate\Support\Facades\Gate;

/**
 * Tool zum Auflisten von Marken im Brands-Modul
 */
class ListBrandsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.brands.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands?team_id={id}&filters=[...]&search=...&sort=[...] - Listet Marken auf. REST-Parameter: team_id (optional, integer) - Filter nach Team-ID. Wenn nicht angegeben, wird automatisch das aktuelle Team aus dem Kontext verwendet. filters (optional, array) - Filter-Array. search (optional, string) - Suchbegriff. sort (optional, array) - Sortierung. limit/offset (optional) - Pagination.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'team_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (optional): Filter nach Team-ID. Wenn nicht angegeben, wird automatisch das aktuelle Team aus dem Kontext verwendet.'
                    ],
                ]
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            // Team-Filter bestimmen
            $teamIdArg = $arguments['team_id'] ?? null;
            if ($teamIdArg === 0 || $teamIdArg === '0') {
                $teamIdArg = null;
            }
            
            if ($teamIdArg === null) {
                $teamIdArg = $context->team?->id;
            }
            
            if (!$teamIdArg) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team angegeben und kein Team im Kontext gefunden.');
            }
            
            // Prüfe, ob User Zugriff auf dieses Team hat
            $userHasAccess = $context->user->teams()->where('teams.id', $teamIdArg)->exists();
            if (!$userHasAccess) {
                return ToolResult::error('ACCESS_DENIED', "Du hast keinen Zugriff auf Team-ID {$teamIdArg}.");
            }
            
            // Query aufbauen
            $query = BrandsBrand::query()
                ->where('team_id', $teamIdArg)
                ->with(['user', 'team', 'companyLinks.company', 'crmContactLinks.contact']);

            // Standard-Operationen anwenden
            $this->applyStandardFilters($query, $arguments, [
                'name', 'description', 'done', 'created_at', 'updated_at'
            ]);
            
            // Standard-Suche anwenden
            $this->applyStandardSearch($query, $arguments, ['name', 'description']);
            
            // Standard-Sortierung anwenden
            $this->applyStandardSort($query, $arguments, [
                'name', 'created_at', 'updated_at', 'done'
            ], 'name', 'asc');
            
            // Standard-Pagination anwenden
            $this->applyStandardPagination($query, $arguments);

            // Marken holen und per Policy filtern
            $brands = $query->get()->filter(function ($brand) use ($context) {
                try {
                    return Gate::forUser($context->user)->allows('view', $brand);
                } catch (\Throwable $e) {
                    return false;
                }
            })->values();

            // Marken formatieren
            $brandsList = $brands->map(function($brand) {
                $company = $brand->getCompany();
                $contact = $brand->getContact();
                
                return [
                    'id' => $brand->id,
                    'uuid' => $brand->uuid,
                    'name' => $brand->name,
                    'description' => $brand->description,
                    'team_id' => $brand->team_id,
                    'user_id' => $brand->user_id,
                    'done' => $brand->done,
                    'done_at' => $brand->done_at?->toIso8601String(),
                    'created_at' => $brand->created_at->toIso8601String(),
                    'company_id' => $company?->id,
                    'company_name' => $company ? app(\Platform\Core\Contracts\CrmCompanyResolverInterface::class)->displayName($company->id) : null,
                    'contact_id' => $contact?->id,
                    'contact_name' => $contact ? app(\Platform\Core\Contracts\CrmContactResolverInterface::class)->displayName($contact->id) : null,
                ];
            })->values()->toArray();

            return ToolResult::success([
                'brands' => $brandsList,
                'count' => count($brandsList),
                'team_id' => $teamIdArg,
                'message' => count($brandsList) > 0 
                    ? count($brandsList) . ' Marke(n) gefunden (Team-ID: ' . $teamIdArg . ').'
                    : 'Keine Marken gefunden für Team-ID: ' . $teamIdArg . '.'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Marken: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'brand', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}

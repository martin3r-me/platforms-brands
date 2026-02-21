<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsContentBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Erstellen von ContentBoards im Brands-Modul
 */
class CreateContentBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_boards.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/{brand_id}/content_boards - Erstellt ein neues Content Board (= Page / Landing Page) für eine Marke. '
            . 'Ein Content Board repräsentiert eine vollständige Page. Die Blocks innerhalb des Boards sind die Sektionen der Page. '
            . 'REST-Parameter: brand_id (required, integer) - Marken-ID. name (optional, string) - Board-Name. description (optional, string) - Beschreibung. '
            . 'domain (optional, string) - Domain der Page, z.B. "taisteone.de". '
            . 'slug (optional, string) - URL-Pfad, z.B. "/leistungen/arbeitsmedizin". '
            . 'published_url (optional, string) - Vollständige URL nach Deploy.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'brand_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Marke, zu der das Content Board gehört (ERFORDERLICH). Nutze "brands.brands.GET" um Marken zu finden.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name des Content Boards. Wenn nicht angegeben, wird automatisch "Neues Content Board" verwendet.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Beschreibung des Content Boards.'
                ],
                'domain' => [
                    'type' => 'string',
                    'description' => 'Optional: Domain der Page, z.B. "taisteone.de".'
                ],
                'slug' => [
                    'type' => 'string',
                    'description' => 'Optional: URL-Pfad der Page, z.B. "/leistungen/arbeitsmedizin".'
                ],
                'published_url' => [
                    'type' => 'string',
                    'description' => 'Optional: Vollständige URL nach Deploy, z.B. "https://taisteone.de/leistungen/arbeitsmedizin".'
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
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Boards für diese Marke erstellen (Policy).');
            }

            $name = $arguments['name'] ?? 'Neues Content Board';

            // ContentBoard direkt erstellen
            $contentBoard = BrandsContentBoard::create([
                'name' => $name,
                'description' => $arguments['description'] ?? null,
                'domain' => $arguments['domain'] ?? null,
                'slug' => $arguments['slug'] ?? null,
                'published_url' => $arguments['published_url'] ?? null,
                'user_id' => $context->user->id,
                'team_id' => $brand->team_id,
                'brand_id' => $brand->id,
            ]);

            $contentBoard->load(['brand', 'user', 'team']);

            return ToolResult::success([
                'id' => $contentBoard->id,
                'uuid' => $contentBoard->uuid,
                'name' => $contentBoard->name,
                'description' => $contentBoard->description,
                'domain' => $contentBoard->domain,
                'slug' => $contentBoard->slug,
                'published_url' => $contentBoard->published_url,
                'brand_id' => $contentBoard->brand_id,
                'brand_name' => $contentBoard->brand->name,
                'team_id' => $contentBoard->team_id,
                'created_at' => $contentBoard->created_at->toIso8601String(),
                'message' => "Content Board '{$contentBoard->name}' erfolgreich für Marke '{$contentBoard->brand->name}' erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Content Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'content_board', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}

<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsSeoBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateSeoBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.seo_boards.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/{brand_id}/seo_boards - Erstellt ein neues SEO Board für eine Marke. REST-Parameter: brand_id (required, integer) - Marken-ID. name (optional, string) - Board-Name. description (optional, string) - Beschreibung. budget_limit_cents (optional, integer) - Monatliches Budget-Limit in Cents. refresh_interval_days (optional, integer) - Refresh-Intervall in Tagen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'brand_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Marke, zu der das SEO Board gehört (ERFORDERLICH). Nutze "brands.brands.GET" um Marken zu finden.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name des SEO Boards. Wenn nicht angegeben, wird automatisch "Neues SEO Board" verwendet.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Beschreibung des SEO Boards.'
                ],
                'budget_limit_cents' => [
                    'type' => 'integer',
                    'description' => 'Optional: Monatliches Budget-Limit für DataForSEO-API-Kosten in Cents.'
                ],
                'refresh_interval_days' => [
                    'type' => 'integer',
                    'description' => 'Optional: Intervall in Tagen für automatischen Keyword-Refresh (Standard: 30).'
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

            $brandId = $arguments['brand_id'] ?? null;
            if (!$brandId) {
                return ToolResult::error('VALIDATION_ERROR', 'brand_id ist erforderlich.');
            }

            $brand = BrandsBrand::find($brandId);
            if (!$brand) {
                return ToolResult::error('BRAND_NOT_FOUND', 'Die angegebene Marke wurde nicht gefunden. Nutze "brands.brands.GET" um Marken zu finden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $brand);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Boards für diese Marke erstellen (Policy).');
            }

            $seoBoard = BrandsSeoBoard::create([
                'name' => $arguments['name'] ?? 'Neues SEO Board',
                'description' => $arguments['description'] ?? null,
                'user_id' => $context->user->id,
                'team_id' => $brand->team_id,
                'brand_id' => $brand->id,
                'budget_limit_cents' => $arguments['budget_limit_cents'] ?? null,
                'refresh_interval_days' => $arguments['refresh_interval_days'] ?? 30,
            ]);

            $seoBoard->load(['brand', 'user', 'team']);

            return ToolResult::success([
                'id' => $seoBoard->id,
                'uuid' => $seoBoard->uuid,
                'name' => $seoBoard->name,
                'description' => $seoBoard->description,
                'brand_id' => $seoBoard->brand_id,
                'brand_name' => $seoBoard->brand->name,
                'team_id' => $seoBoard->team_id,
                'budget_limit_cents' => $seoBoard->budget_limit_cents,
                'refresh_interval_days' => $seoBoard->refresh_interval_days,
                'created_at' => $seoBoard->created_at->toIso8601String(),
                'message' => "SEO Board '{$seoBoard->name}' erfolgreich für Marke '{$seoBoard->brand->name}' erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des SEO Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'seo_board', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}

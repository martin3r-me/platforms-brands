<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsCta;
use Platform\Brands\Models\BrandsContentBoardBlock;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Erstellen eines CTA (Call-to-Action) im Brands-Modul.
 *
 * Ein CTA ist eine Handlungsaufforderung, die einer Brand zugeordnet wird.
 * CTAs werden nach Typ (primary/secondary/micro) und Funnel-Stage
 * (awareness/consideration/decision) kategorisiert. Optional kann ein CTA
 * auf eine interne Zielseite (Content Board Block) oder eine externe URL verweisen.
 */
class CreateCtaTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.ctas.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/ctas - Erstellt einen neuen CTA (Call-to-Action) für eine Brand. Ein CTA ist eine Handlungsaufforderung (z.B. "Jetzt anfragen", "Mehr erfahren") mit Typ (primary/secondary/micro) und Funnel-Stage (awareness/consideration/decision). Der CTA kann optional auf eine Zielseite (Content Board Block) oder eine externe URL verweisen. REST-Parameter: brand_id (required), label (required), type (required: primary|secondary|micro), funnel_stage (required: awareness|consideration|decision), description (optional), target_page_id (optional, FK auf Content Board Block), target_url (optional), is_active (optional, default true).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'brand_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Brand (ERFORDERLICH). Nutze "brands.brands.GET" um Brands zu finden.',
                ],
                'label' => [
                    'type' => 'string',
                    'description' => 'Die CTA-Formulierung, z.B. "Jetzt anfragen", "Mehr erfahren", "Kostenlos testen" (ERFORDERLICH).',
                ],
                'type' => [
                    'type' => 'string',
                    'description' => 'CTA-Typ (ERFORDERLICH). Mögliche Werte: "primary" (Haupt-CTA), "secondary" (Neben-CTA), "micro" (Micro-CTA, z.B. Link im Text).',
                    'enum' => ['primary', 'secondary', 'micro'],
                ],
                'funnel_stage' => [
                    'type' => 'string',
                    'description' => 'Funnel-Stage (ERFORDERLICH). Gibt an, in welcher Phase der Customer Journey der CTA eingesetzt wird. Mögliche Werte: "awareness" (Aufmerksamkeit), "consideration" (Überlegung), "decision" (Entscheidung).',
                    'enum' => ['awareness', 'consideration', 'decision'],
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Interne Beschreibung/Kontext zum CTA.',
                ],
                'target_page_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: ID eines Content Board Blocks als Zielseite. Nutze "brands.content_board_blocks.GET" um Blocks zu finden. Der CTA gehört semantisch zur Zielseite – er verweist DORTHIN, nicht VON dort.',
                ],
                'target_url' => [
                    'type' => 'string',
                    'description' => 'Optional: Externe Ziel-URL, falls kein interner Content Board Block als Ziel verwendet wird.',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Ob der CTA aktiv ist. Standard: true.',
                ],
            ],
            'required' => ['brand_id', 'label', 'type', 'funnel_stage'],
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
                return ToolResult::error('BRAND_NOT_FOUND', 'Die angegebene Brand wurde nicht gefunden. Nutze "brands.brands.GET" um Brands zu finden.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $brand);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine CTAs für diese Brand erstellen (Policy).');
            }

            // Label validieren
            $label = $arguments['label'] ?? null;
            if (!$label || !is_string($label) || trim($label) === '') {
                return ToolResult::error('VALIDATION_ERROR', 'label ist erforderlich und darf nicht leer sein.');
            }

            // Type validieren
            $type = $arguments['type'] ?? null;
            if (!$type || !in_array($type, BrandsCta::TYPES, true)) {
                return ToolResult::error('VALIDATION_ERROR', 'type ist erforderlich. Erlaubte Werte: ' . implode(', ', BrandsCta::TYPES));
            }

            // Funnel Stage validieren
            $funnelStage = $arguments['funnel_stage'] ?? null;
            if (!$funnelStage || !in_array($funnelStage, BrandsCta::FUNNEL_STAGES, true)) {
                return ToolResult::error('VALIDATION_ERROR', 'funnel_stage ist erforderlich. Erlaubte Werte: ' . implode(', ', BrandsCta::FUNNEL_STAGES));
            }

            // Target Page validieren (falls angegeben)
            $targetPageId = $arguments['target_page_id'] ?? null;
            if ($targetPageId) {
                $targetPage = BrandsContentBoardBlock::find($targetPageId);
                if (!$targetPage) {
                    return ToolResult::error('TARGET_PAGE_NOT_FOUND', 'Der angegebene Content Board Block (target_page_id) wurde nicht gefunden. Nutze "brands.content_board_blocks.GET" um Blocks zu finden.');
                }
            }

            // CTA erstellen
            $cta = BrandsCta::create([
                'brand_id' => $brand->id,
                'label' => trim($label),
                'description' => $arguments['description'] ?? null,
                'type' => $type,
                'funnel_stage' => $funnelStage,
                'target_page_id' => $targetPageId,
                'target_url' => $arguments['target_url'] ?? null,
                'is_active' => $arguments['is_active'] ?? true,
                'user_id' => $context->user->id,
                'team_id' => $brand->team_id,
            ]);

            $cta->load(['brand', 'targetPage', 'user', 'team']);

            $result = [
                'id' => $cta->id,
                'uuid' => $cta->uuid,
                'label' => $cta->label,
                'description' => $cta->description,
                'type' => $cta->type,
                'funnel_stage' => $cta->funnel_stage,
                'target_page_id' => $cta->target_page_id,
                'target_page_name' => $cta->targetPage?->name,
                'target_url' => $cta->target_url,
                'is_active' => $cta->is_active,
                'brand_id' => $cta->brand_id,
                'brand_name' => $cta->brand->name,
                'team_id' => $cta->team_id,
                'created_at' => $cta->created_at->toIso8601String(),
                'message' => "CTA '{$cta->label}' erfolgreich erstellt.",
            ];

            return ToolResult::success($result);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des CTA: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'cta', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}

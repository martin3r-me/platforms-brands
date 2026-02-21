<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsCta;
use Platform\Brands\Models\BrandsContentBoardBlock;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Aktualisieren eines CTA (Call-to-Action)
 */
class UpdateCtaTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.ctas.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/ctas/{id} - Aktualisiert einen CTA (Call-to-Action). REST-Parameter: cta_id (required), label (optional), description (optional), type (optional: primary|secondary|micro), funnel_stage (optional: awareness|consideration|decision), target_page_id (optional, FK auf Content Board Block), target_url (optional), is_active (optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'cta_id' => [
                    'type' => 'integer',
                    'description' => 'ID des CTA (ERFORDERLICH). Nutze "brands.ctas.GET" um CTAs zu finden.',
                ],
                'label' => [
                    'type' => 'string',
                    'description' => 'Optional: Die CTA-Formulierung, z.B. "Jetzt anfragen".',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Interne Beschreibung/Kontext zum CTA.',
                ],
                'type' => [
                    'type' => 'string',
                    'description' => 'Optional: CTA-Typ. Mögliche Werte: "primary", "secondary", "micro".',
                    'enum' => ['primary', 'secondary', 'micro'],
                ],
                'funnel_stage' => [
                    'type' => 'string',
                    'description' => 'Optional: Funnel-Stage. Mögliche Werte: "awareness", "consideration", "decision".',
                    'enum' => ['awareness', 'consideration', 'decision'],
                ],
                'target_page_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: ID eines Content Board Blocks als Zielseite. Nutze "brands.content_board_blocks.GET" um Blocks zu finden.',
                ],
                'target_url' => [
                    'type' => 'string',
                    'description' => 'Optional: Externe Ziel-URL.',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Ob der CTA aktiv ist.',
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
                Gate::forUser($context->user)->authorize('update', $cta);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diesen CTA nicht bearbeiten (Policy).');
            }

            // Update-Daten sammeln
            $updateData = [];

            if (isset($arguments['label'])) {
                $label = trim($arguments['label']);
                if ($label === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'label darf nicht leer sein.');
                }
                $updateData['label'] = $label;
            }

            if (array_key_exists('description', $arguments)) {
                $updateData['description'] = $arguments['description'];
            }

            if (isset($arguments['type'])) {
                if (!in_array($arguments['type'], BrandsCta::TYPES, true)) {
                    return ToolResult::error('VALIDATION_ERROR', 'Ungültiger type. Erlaubte Werte: ' . implode(', ', BrandsCta::TYPES));
                }
                $updateData['type'] = $arguments['type'];
            }

            if (isset($arguments['funnel_stage'])) {
                if (!in_array($arguments['funnel_stage'], BrandsCta::FUNNEL_STAGES, true)) {
                    return ToolResult::error('VALIDATION_ERROR', 'Ungültiger funnel_stage. Erlaubte Werte: ' . implode(', ', BrandsCta::FUNNEL_STAGES));
                }
                $updateData['funnel_stage'] = $arguments['funnel_stage'];
            }

            if (array_key_exists('target_page_id', $arguments)) {
                $targetPageId = $arguments['target_page_id'];
                if ($targetPageId !== null) {
                    $targetPage = BrandsContentBoardBlock::find($targetPageId);
                    if (!$targetPage) {
                        return ToolResult::error('TARGET_PAGE_NOT_FOUND', 'Der angegebene Content Board Block (target_page_id) wurde nicht gefunden.');
                    }
                }
                $updateData['target_page_id'] = $targetPageId;
            }

            if (array_key_exists('target_url', $arguments)) {
                $updateData['target_url'] = $arguments['target_url'];
            }

            if (isset($arguments['is_active'])) {
                $updateData['is_active'] = (bool) $arguments['is_active'];
            }

            // CTA aktualisieren
            if (!empty($updateData)) {
                $cta->update($updateData);
            }

            $cta->refresh();
            $cta->load(['brand', 'targetPage', 'user', 'team']);

            return ToolResult::success([
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
                'updated_at' => $cta->updated_at->toIso8601String(),
                'message' => "CTA '{$cta->label}' erfolgreich aktualisiert.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des CTA: ' . $e->getMessage());
        }
    }
}

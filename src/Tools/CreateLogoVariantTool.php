<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsLogoBoard;
use Platform\Brands\Models\BrandsLogoVariant;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateLogoVariantTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.logo_variants.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/logo_boards/{logo_board_id}/variants - Erstellt eine neue Logo-Variante. REST-Parameter: logo_board_id (required), name (required), type (required: primary, secondary, monochrome, favicon, icon, wordmark, pictorial_mark, combination_mark). Optional: description, usage_guidelines, clearspace_factor, min_width_px, min_width_mm, background_color, dos, donts.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'logo_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Logo Boards (ERFORDERLICH). Nutze "brands.logo_boards.GET" um Boards zu finden.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name der Logo-Variante (ERFORDERLICH), z.B. "Primary Logo", "Favicon", "Wortmarke".'
                ],
                'type' => [
                    'type' => 'string',
                    'description' => 'Typ der Logo-Variante (ERFORDERLICH).',
                    'enum' => ['primary', 'secondary', 'monochrome', 'favicon', 'icon', 'wordmark', 'pictorial_mark', 'combination_mark']
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Beschreibung der Logo-Variante.'
                ],
                'usage_guidelines' => [
                    'type' => 'string',
                    'description' => 'Verwendungsrichtlinien für diese Variante.'
                ],
                'clearspace_factor' => [
                    'type' => 'number',
                    'description' => 'Schutzzone als Faktor der Logohöhe (z.B. 0.5 = halbe Logohöhe).'
                ],
                'min_width_px' => [
                    'type' => 'integer',
                    'description' => 'Mindestbreite in Pixel (digital).'
                ],
                'min_width_mm' => [
                    'type' => 'integer',
                    'description' => 'Mindestbreite in Millimeter (print).'
                ],
                'background_color' => [
                    'type' => 'string',
                    'description' => 'Bevorzugte Hintergrundfarbe als Hex-Wert (z.B. #FFFFFF).'
                ],
                'dos' => [
                    'type' => 'array',
                    'description' => 'Liste von Do\'s (richtige Verwendung). Jedes Element: {text: string}.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'text' => ['type' => 'string', 'description' => 'Beschreibung der richtigen Verwendung.']
                        ]
                    ]
                ],
                'donts' => [
                    'type' => 'array',
                    'description' => 'Liste von Don\'ts (falsche Verwendung). Jedes Element: {text: string}.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'text' => ['type' => 'string', 'description' => 'Beschreibung der falschen Verwendung.']
                        ]
                    ]
                ],
            ],
            'required' => ['logo_board_id', 'name', 'type']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $boardId = $arguments['logo_board_id'] ?? null;
            if (!$boardId) {
                return ToolResult::error('VALIDATION_ERROR', 'logo_board_id ist erforderlich.');
            }

            $board = BrandsLogoBoard::find($boardId);
            if (!$board) {
                return ToolResult::error('LOGO_BOARD_NOT_FOUND', 'Das angegebene Logo Board wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Varianten für dieses Logo Board erstellen.');
            }

            $name = $arguments['name'] ?? null;
            if (!$name) {
                return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');
            }

            $type = $arguments['type'] ?? null;
            if (!$type || !array_key_exists($type, BrandsLogoVariant::TYPES)) {
                return ToolResult::error('VALIDATION_ERROR', 'type ist erforderlich und muss ein gültiger Typ sein: ' . implode(', ', array_keys(BrandsLogoVariant::TYPES)));
            }

            $variant = BrandsLogoVariant::create([
                'logo_board_id' => $board->id,
                'name' => $name,
                'type' => $type,
                'description' => $arguments['description'] ?? null,
                'usage_guidelines' => $arguments['usage_guidelines'] ?? null,
                'clearspace_factor' => $arguments['clearspace_factor'] ?? null,
                'min_width_px' => $arguments['min_width_px'] ?? null,
                'min_width_mm' => $arguments['min_width_mm'] ?? null,
                'background_color' => $arguments['background_color'] ?? null,
                'dos' => $arguments['dos'] ?? null,
                'donts' => $arguments['donts'] ?? null,
            ]);

            $variant->load('logoBoard');

            return ToolResult::success([
                'id' => $variant->id,
                'uuid' => $variant->uuid,
                'name' => $variant->name,
                'type' => $variant->type,
                'type_label' => $variant->type_label,
                'description' => $variant->description,
                'usage_guidelines' => $variant->usage_guidelines,
                'clearspace_factor' => $variant->clearspace_factor,
                'min_width_px' => $variant->min_width_px,
                'min_width_mm' => $variant->min_width_mm,
                'background_color' => $variant->background_color,
                'dos' => $variant->dos,
                'donts' => $variant->donts,
                'order' => $variant->order,
                'logo_board_id' => $variant->logo_board_id,
                'created_at' => $variant->created_at->toIso8601String(),
                'message' => "Logo-Variante '{$variant->name}' ({$variant->type_label}) erfolgreich erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der Logo-Variante: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'logo_variant', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}

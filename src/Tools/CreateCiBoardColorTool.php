<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsCiBoard;
use Platform\Brands\Models\BrandsCiBoardColor;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateCiBoardColorTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.ci_board_colors.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/ci_boards/{ci_board_id}/colors - Erstellt eine neue Farbe für ein CI Board. REST-Parameter: ci_board_id (required, integer) - CI Board-ID. title (required, string) - Titel der Farbe. color (optional, string) - Hex-Farbwert (z.B. "#FF0000"). description (optional, string) - Beschreibung.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'ci_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des CI Boards, zu dem die Farbe gehört (ERFORDERLICH). Nutze "brands.ci_boards.GET" um CI Boards zu finden.'
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Titel der Farbe (ERFORDERLICH).'
                ],
                'color' => [
                    'type' => 'string',
                    'description' => 'Hex-Farbwert (z.B. "#FF0000").'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Beschreibung der Farbe.'
                ],
            ],
            'required' => ['ci_board_id', 'title']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $ciBoardId = $arguments['ci_board_id'] ?? null;
            if (!$ciBoardId) {
                return ToolResult::error('VALIDATION_ERROR', 'ci_board_id ist erforderlich.');
            }

            $ciBoard = BrandsCiBoard::find($ciBoardId);
            if (!$ciBoard) {
                return ToolResult::error('CI_BOARD_NOT_FOUND', 'Das angegebene CI Board wurde nicht gefunden.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $ciBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Farben für dieses CI Board erstellen.');
            }

            $title = $arguments['title'] ?? null;
            if (!$title) {
                return ToolResult::error('VALIDATION_ERROR', 'title ist erforderlich.');
            }

            // Farbwert normalisieren
            $color = $arguments['color'] ?? null;
            if ($color) {
                $colorValue = ltrim($color, '#');
                if (strlen($colorValue) === 3) {
                    $colorValue = $colorValue[0] . $colorValue[0] . $colorValue[1] . $colorValue[1] . $colorValue[2] . $colorValue[2];
                }
                $color = '#' . $colorValue;
            }

            $colorModel = BrandsCiBoardColor::create([
                'brand_ci_board_id' => $ciBoard->id,
                'title' => $title,
                'color' => $color,
                'description' => $arguments['description'] ?? null,
            ]);

            $colorModel->load('ciBoard');

            return ToolResult::success([
                'id' => $colorModel->id,
                'uuid' => $colorModel->uuid,
                'title' => $colorModel->title,
                'color' => $colorModel->color,
                'description' => $colorModel->description,
                'order' => $colorModel->order,
                'ci_board_id' => $colorModel->brand_ci_board_id,
                'created_at' => $colorModel->created_at->toIso8601String(),
                'message' => "Farbe '{$colorModel->title}' erfolgreich für CI Board '{$ciBoard->name}' erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der Farbe: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'ci_board_color', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}

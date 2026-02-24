<?php

namespace Platform\Brands\Services;

use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsCiBoard;
use Platform\Brands\Models\BrandsContentBoard;
use Platform\Brands\Models\BrandsSocialBoard;
use Platform\Brands\Models\BrandsKanbanBoard;
use Platform\Brands\Models\BrandsMultiContentBoard;
use Platform\Brands\Models\BrandsTypographyBoard;
use Platform\Brands\Models\BrandsToneOfVoiceBoard;
use Platform\Brands\Models\BrandsGuidelineBoard;
use Platform\Brands\Services\Export\ExportFormatInterface;
use Platform\Brands\Services\Export\JsonExportFormat;
use Platform\Brands\Services\Export\PdfExportFormat;

class BrandsExportService
{
    /** @var ExportFormatInterface[] */
    protected array $formats = [];

    public function __construct()
    {
        $this->registerFormat(new JsonExportFormat());
        $this->registerFormat(new PdfExportFormat());
    }

    public function registerFormat(ExportFormatInterface $format): void
    {
        $this->formats[$format->getKey()] = $format;
    }

    /**
     * @return ExportFormatInterface[]
     */
    public function getAvailableFormats(): array
    {
        return $this->formats;
    }

    public function getFormat(string $key): ?ExportFormatInterface
    {
        return $this->formats[$key] ?? null;
    }

    // ──────────────────────────────────────────────
    //  Brand Export
    // ──────────────────────────────────────────────

    public function exportBrand(BrandsBrand $brand, string $formatKey): array
    {
        $format = $this->getFormat($formatKey);
        if (!$format) {
            throw new \InvalidArgumentException("Export-Format '{$formatKey}' nicht verfügbar.");
        }

        $data = $this->collectBrandData($brand);
        $content = $format->exportBrand($data);
        $filename = $this->sanitizeFilename($brand->name) . '_brand-book.' . $format->getFileExtension();

        return [
            'content' => $content,
            'filename' => $filename,
            'mime_type' => $format->getMimeType(),
        ];
    }

    // ──────────────────────────────────────────────
    //  Board Export (polymorphic)
    // ──────────────────────────────────────────────

    public function exportBoard(object $board, string $formatKey): array
    {
        $format = $this->getFormat($formatKey);
        if (!$format) {
            throw new \InvalidArgumentException("Export-Format '{$formatKey}' nicht verfügbar.");
        }

        $boardData = $this->collectBoardData($board);
        $brandContext = $this->collectBrandContext($board);
        $content = $format->exportBoard($boardData, $brandContext);
        $typeSuffix = $boardData['type'] ?? 'board';
        $filename = $this->sanitizeFilename($board->name ?? 'board') . '_' . $typeSuffix . '.' . $format->getFileExtension();

        return [
            'content' => $content,
            'filename' => $filename,
            'mime_type' => $format->getMimeType(),
        ];
    }

    // ──────────────────────────────────────────────
    //  Data Collection: Full Brand
    // ──────────────────────────────────────────────

    public function collectBrandData(BrandsBrand $brand): array
    {
        $brand->load([
            'ciBoards.colors',
            'contentBoards.blocks.content',
            'socialBoards.slots.cards',
            'kanbanBoards.slots.cards',
            'multiContentBoards.slots.contentBoards.blocks.content',
            'typographyBoards.entries',
            'toneOfVoiceBoards.entries',
            'toneOfVoiceBoards.dimensions',
            'guidelineBoards.chapters.entries',
        ]);

        // Extract brand-level CI settings from the first CI board (if any)
        $firstCi = $brand->ciBoards->first();
        $settings = [
            'primary_color' => $firstCi?->primary_color,
            'secondary_color' => $firstCi?->secondary_color,
            'accent_color' => $firstCi?->accent_color,
        ];

        return [
            'id' => $brand->id,
            'uuid' => $brand->uuid,
            'name' => $brand->name,
            'description' => $brand->description,
            'created_at' => $brand->created_at?->toIso8601String(),
            'settings' => $settings,
            'ci_boards' => $brand->ciBoards->map(fn ($b) => $this->collectCiBoardData($b))->toArray(),
            'content_boards' => $brand->contentBoards->map(fn ($b) => $this->collectContentBoardData($b))->toArray(),
            'social_boards' => $brand->socialBoards->map(fn ($b) => $this->collectSocialBoardData($b))->toArray(),
            'kanban_boards' => $brand->kanbanBoards->map(fn ($b) => $this->collectKanbanBoardData($b))->toArray(),
            'multi_content_boards' => $brand->multiContentBoards->map(fn ($b) => $this->collectMultiContentBoardData($b))->toArray(),
            'typography_boards' => $brand->typographyBoards->map(fn ($b) => $this->collectTypographyBoardData($b))->toArray(),
            'tone_of_voice_boards' => $brand->toneOfVoiceBoards->map(fn ($b) => $this->collectToneOfVoiceBoardData($b))->toArray(),
            'guideline_boards' => $brand->guidelineBoards->map(fn ($b) => $this->collectGuidelineBoardData($b))->toArray(),
        ];
    }

    // ──────────────────────────────────────────────
    //  Data Collection: Individual Boards
    // ──────────────────────────────────────────────

    public function collectBoardData(object $board): array
    {
        return match (true) {
            $board instanceof BrandsCiBoard => $this->collectCiBoardData($board),
            $board instanceof BrandsContentBoard => $this->collectContentBoardData($board),
            $board instanceof BrandsSocialBoard => $this->collectSocialBoardData($board),
            $board instanceof BrandsKanbanBoard => $this->collectKanbanBoardData($board),
            $board instanceof BrandsMultiContentBoard => $this->collectMultiContentBoardData($board),
            $board instanceof BrandsTypographyBoard => $this->collectTypographyBoardData($board),
            $board instanceof BrandsToneOfVoiceBoard => $this->collectToneOfVoiceBoardData($board),
            $board instanceof BrandsGuidelineBoard => $this->collectGuidelineBoardData($board),
            default => throw new \InvalidArgumentException('Unbekannter Board-Typ: ' . get_class($board)),
        };
    }

    public function collectBrandContext(object $board): array
    {
        $brand = $board->brand;
        if (!$brand) {
            return ['name' => 'Unbekannt'];
        }

        $brand->load('ciBoards');
        $firstCi = $brand->ciBoards->first();

        return [
            'id' => $brand->id,
            'uuid' => $brand->uuid,
            'name' => $brand->name,
            'primary_color' => $firstCi?->primary_color,
            'secondary_color' => $firstCi?->secondary_color,
            'accent_color' => $firstCi?->accent_color,
        ];
    }

    protected function collectCiBoardData(BrandsCiBoard $board): array
    {
        $board->loadMissing('colors');

        return [
            'id' => $board->id,
            'uuid' => $board->uuid,
            'type' => 'ci',
            'name' => $board->name,
            'description' => $board->description,
            'primary_color' => $board->primary_color,
            'secondary_color' => $board->secondary_color,
            'accent_color' => $board->accent_color,
            'slogan' => $board->slogan,
            'tagline' => $board->tagline,
            'font_family' => $board->font_family,
            'colors' => $board->colors->map(fn ($c) => [
                'id' => $c->id,
                'uuid' => $c->uuid,
                'title' => $c->title,
                'color' => $c->color,
                'description' => $c->description,
                'order' => $c->order,
            ])->toArray(),
            'created_at' => $board->created_at?->toIso8601String(),
        ];
    }

    protected function collectContentBoardData(BrandsContentBoard $board): array
    {
        $board->loadMissing('blocks.content');

        return [
            'id' => $board->id,
            'uuid' => $board->uuid,
            'type' => 'content',
            'name' => $board->name,
            'description' => $board->description,
            'blocks' => $board->blocks->map(fn ($block) => [
                'id' => $block->id,
                'uuid' => $block->uuid,
                'name' => $block->name,
                'description' => $block->description,
                'order' => $block->order,
                'content_type' => $block->content_type,
                'content' => $this->collectBlockContent($block),
            ])->toArray(),
            'created_at' => $board->created_at?->toIso8601String(),
        ];
    }

    protected function collectBlockContent($block): ?array
    {
        $content = $block->content;
        if (!$content) {
            return null;
        }

        // Polymorphic: currently only 'text'
        if ($block->content_type === 'text') {
            return [
                'type' => 'text',
                'text' => $content->content,
            ];
        }

        return [
            'type' => $block->content_type,
            'id' => $content->id ?? null,
        ];
    }

    protected function collectSocialBoardData(BrandsSocialBoard $board): array
    {
        $board->loadMissing('slots.cards');

        return [
            'id' => $board->id,
            'uuid' => $board->uuid,
            'type' => 'social',
            'name' => $board->name,
            'description' => $board->description,
            'slots' => $board->slots->map(fn ($slot) => [
                'id' => $slot->id,
                'uuid' => $slot->uuid,
                'name' => $slot->name,
                'order' => $slot->order,
                'cards' => $slot->cards->map(fn ($card) => [
                    'id' => $card->id,
                    'uuid' => $card->uuid,
                    'title' => $card->title,
                    'description' => $card->description,
                    'body_md' => $card->body_md,
                    'order' => $card->order,
                ])->toArray(),
            ])->toArray(),
            'created_at' => $board->created_at?->toIso8601String(),
        ];
    }

    protected function collectKanbanBoardData(BrandsKanbanBoard $board): array
    {
        $board->loadMissing('slots.cards');

        return [
            'id' => $board->id,
            'uuid' => $board->uuid,
            'type' => 'kanban',
            'name' => $board->name,
            'description' => $board->description,
            'slots' => $board->slots->map(fn ($slot) => [
                'id' => $slot->id,
                'uuid' => $slot->uuid,
                'name' => $slot->name,
                'order' => $slot->order,
                'cards' => $slot->cards->map(fn ($card) => [
                    'id' => $card->id,
                    'uuid' => $card->uuid,
                    'title' => $card->title,
                    'description' => $card->description,
                    'order' => $card->order,
                ])->toArray(),
            ])->toArray(),
            'created_at' => $board->created_at?->toIso8601String(),
        ];
    }

    protected function collectMultiContentBoardData(BrandsMultiContentBoard $board): array
    {
        $board->loadMissing('slots.contentBoards.blocks.content');

        return [
            'id' => $board->id,
            'uuid' => $board->uuid,
            'type' => 'multi_content',
            'name' => $board->name,
            'description' => $board->description,
            'slots' => $board->slots->map(fn ($slot) => [
                'id' => $slot->id,
                'uuid' => $slot->uuid,
                'name' => $slot->name,
                'order' => $slot->order,
                'content_boards' => $slot->contentBoards->map(fn ($cb) => $this->collectContentBoardData($cb))->toArray(),
            ])->toArray(),
            'created_at' => $board->created_at?->toIso8601String(),
        ];
    }

    protected function collectTypographyBoardData(BrandsTypographyBoard $board): array
    {
        $board->loadMissing('entries');

        return [
            'id' => $board->id,
            'uuid' => $board->uuid,
            'type' => 'typography',
            'name' => $board->name,
            'description' => $board->description,
            'entries' => $board->entries->map(fn ($entry) => [
                'id' => $entry->id,
                'uuid' => $entry->uuid,
                'name' => $entry->name,
                'role' => $entry->role,
                'font_family' => $entry->font_family,
                'font_source' => $entry->font_source,
                'font_weight' => $entry->font_weight,
                'font_style' => $entry->font_style,
                'font_size' => $entry->font_size,
                'line_height' => $entry->line_height,
                'letter_spacing' => $entry->letter_spacing,
                'text_transform' => $entry->text_transform,
                'sample_text' => $entry->sample_text,
                'order' => $entry->order,
                'description' => $entry->description,
            ])->toArray(),
            'created_at' => $board->created_at?->toIso8601String(),
        ];
    }

    protected function collectToneOfVoiceBoardData(BrandsToneOfVoiceBoard $board): array
    {
        $board->loadMissing(['entries', 'dimensions']);

        return [
            'id' => $board->id,
            'uuid' => $board->uuid,
            'type' => 'tone_of_voice',
            'name' => $board->name,
            'description' => $board->description,
            'entries' => $board->entries->map(fn ($entry) => [
                'id' => $entry->id,
                'uuid' => $entry->uuid,
                'name' => $entry->name,
                'type' => $entry->type,
                'type_label' => $entry->type_label,
                'content' => $entry->content,
                'description' => $entry->description,
                'example_positive' => $entry->example_positive,
                'example_negative' => $entry->example_negative,
                'order' => $entry->order,
            ])->toArray(),
            'dimensions' => $board->dimensions->map(fn ($dim) => [
                'id' => $dim->id,
                'uuid' => $dim->uuid,
                'name' => $dim->name,
                'label_left' => $dim->label_left,
                'label_right' => $dim->label_right,
                'value' => $dim->value,
                'description' => $dim->description,
                'order' => $dim->order,
            ])->toArray(),
            'created_at' => $board->created_at?->toIso8601String(),
        ];
    }

    protected function collectGuidelineBoardData(BrandsGuidelineBoard $board): array
    {
        $board->loadMissing('chapters.entries');

        return [
            'id' => $board->id,
            'uuid' => $board->uuid,
            'type' => 'guideline',
            'name' => $board->name,
            'description' => $board->description,
            'chapters' => $board->chapters->map(fn ($chapter) => [
                'id' => $chapter->id,
                'uuid' => $chapter->uuid,
                'title' => $chapter->title,
                'description' => $chapter->description,
                'icon' => $chapter->icon,
                'order' => $chapter->order,
                'entries' => $chapter->entries->map(fn ($entry) => [
                    'id' => $entry->id,
                    'uuid' => $entry->uuid,
                    'title' => $entry->title,
                    'rule_text' => $entry->rule_text,
                    'rationale' => $entry->rationale,
                    'do_example' => $entry->do_example,
                    'dont_example' => $entry->dont_example,
                    'cross_references' => $entry->cross_references,
                    'order' => $entry->order,
                ])->toArray(),
            ])->toArray(),
            'created_at' => $board->created_at?->toIso8601String(),
        ];
    }

    // ──────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────

    protected function sanitizeFilename(string $name): string
    {
        $name = mb_strtolower($name);
        $name = preg_replace('/[^a-z0-9äöüß\-_ ]/u', '', $name);
        $name = preg_replace('/[\s]+/', '-', $name);
        $name = trim($name, '-');

        return $name ?: 'export';
    }
}

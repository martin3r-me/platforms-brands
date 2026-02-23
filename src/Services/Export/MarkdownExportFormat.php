<?php

namespace Platform\Brands\Services\Export;

class MarkdownExportFormat implements ExportFormatInterface
{
    public function getKey(): string
    {
        return 'markdown';
    }

    public function getLabel(): string
    {
        return 'Markdown';
    }

    public function getMimeType(): string
    {
        return 'text/markdown';
    }

    public function getFileExtension(): string
    {
        return 'md';
    }

    public function exportBoard(array $boardData, array $brandContext): string
    {
        $lines = [];
        $lines[] = '# ' . ($boardData['name'] ?? 'Board');
        $lines[] = '';
        $lines[] = '> Brand: **' . ($brandContext['name'] ?? 'Unknown') . '**';
        $lines[] = '';

        if (!empty($boardData['description'])) {
            $lines[] = $boardData['description'];
            $lines[] = '';
        }

        $lines[] = $this->renderBoardContent($boardData);

        return implode("\n", $lines);
    }

    public function exportBrand(array $brandData): string
    {
        $lines = [];
        $lines[] = '# ' . ($brandData['name'] ?? 'Brand') . ' — Brand Book';
        $lines[] = '';

        if (!empty($brandData['description'])) {
            $lines[] = $brandData['description'];
            $lines[] = '';
        }

        $lines[] = '---';
        $lines[] = '';

        // CI Boards
        foreach ($brandData['ci_boards'] ?? [] as $board) {
            $lines[] = $this->renderBoardContent($board);
        }

        // Typography Boards
        foreach ($brandData['typography_boards'] ?? [] as $board) {
            $lines[] = $this->renderBoardContent($board);
        }

        // Tone of Voice Boards
        foreach ($brandData['tone_of_voice_boards'] ?? [] as $board) {
            $lines[] = $this->renderBoardContent($board);
        }

        // Content Boards
        foreach ($brandData['content_boards'] ?? [] as $board) {
            $lines[] = $this->renderBoardContent($board);
        }

        // Multi-Content Boards
        foreach ($brandData['multi_content_boards'] ?? [] as $board) {
            $lines[] = $this->renderBoardContent($board);
        }

        // Social Boards
        foreach ($brandData['social_boards'] ?? [] as $board) {
            $lines[] = $this->renderBoardContent($board);
        }

        // Kanban Boards
        foreach ($brandData['kanban_boards'] ?? [] as $board) {
            $lines[] = $this->renderBoardContent($board);
        }

        // Guideline Boards
        foreach ($brandData['guideline_boards'] ?? [] as $board) {
            $lines[] = $this->renderBoardContent($board);
        }

        // Intake Boards
        foreach ($brandData['intake_boards'] ?? [] as $board) {
            $lines[] = $this->renderBoardContent($board);
        }

        return implode("\n", $lines);
    }

    protected function renderBoardContent(array $data): string
    {
        $type = $data['type'] ?? 'unknown';

        return match ($type) {
            'ci' => $this->renderCiBoard($data),
            'typography' => $this->renderTypographyBoard($data),
            'tone_of_voice' => $this->renderToneOfVoiceBoard($data),
            'content' => $this->renderContentBoard($data),
            'multi_content' => $this->renderMultiContentBoard($data),
            'social' => $this->renderSlotBoard($data, 'Social'),
            'kanban' => $this->renderSlotBoard($data, 'Kanban'),
            'guideline' => $this->renderGuidelineBoard($data),
            'intake' => $this->renderIntakeBoard($data),
            default => '',
        };
    }

    protected function renderCiBoard(array $data): string
    {
        $lines = [];
        $lines[] = '## CI: ' . ($data['name'] ?? 'Corporate Identity');
        $lines[] = '';

        // Core CI fields
        $fields = [
            'slogan' => 'Slogan',
            'tagline' => 'Tagline',
            'font_family' => 'Font Family',
        ];

        foreach ($fields as $key => $label) {
            if (!empty($data[$key])) {
                $lines[] = '- **' . $label . ':** ' . $data[$key];
            }
        }

        // Primary colors
        $colorFields = ['primary_color', 'secondary_color', 'accent_color'];
        foreach ($colorFields as $key) {
            if (!empty($data[$key])) {
                $label = ucwords(str_replace('_', ' ', $key));
                $lines[] = '- **' . $label . ':** `' . $data[$key] . '`';
            }
        }

        $lines[] = '';

        // Color palette as table
        if (!empty($data['colors'])) {
            $lines[] = '### Farbpalette';
            $lines[] = '';
            $lines[] = '| Titel | Hex | Rolle | Beschreibung |';
            $lines[] = '|-------|-----|-------|-------------|';

            foreach ($data['colors'] as $color) {
                $title = $color['title'] ?? '';
                $hex = !empty($color['color']) ? '`' . $color['color'] . '`' : '—';
                $role = !empty($color['role']) ? '`' . $color['role'] . '`' : '—';
                $desc = $color['description'] ?? '—';
                $lines[] = '| ' . $title . ' | ' . $hex . ' | ' . $role . ' | ' . $this->inlineText($desc) . ' |';
            }

            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    protected function renderTypographyBoard(array $data): string
    {
        $lines = [];
        $lines[] = '## Typografie: ' . ($data['name'] ?? 'Typography');
        $lines[] = '';

        if (!empty($data['description'])) {
            $lines[] = $data['description'];
            $lines[] = '';
        }

        if (!empty($data['entries'])) {
            $lines[] = '| Name | Rolle | Font | Gewicht | Groesse | Zeilenhoehe | Spacing |';
            $lines[] = '|------|-------|------|---------|---------|-------------|---------|';

            foreach ($data['entries'] as $entry) {
                $name = $entry['name'] ?? '';
                $role = !empty($entry['role']) ? '`' . $entry['role'] . '`' : '—';
                $font = !empty($entry['font_family']) ? '`' . $entry['font_family'] . '`' : '—';
                $weight = $entry['font_weight'] ?? '—';
                $size = !empty($entry['font_size']) ? '`' . $entry['font_size'] . '`' : '—';
                $lh = !empty($entry['line_height']) ? '`' . $entry['line_height'] . '`' : '—';
                $ls = !empty($entry['letter_spacing']) ? '`' . $entry['letter_spacing'] . '`' : '—';
                $lines[] = '| ' . $name . ' | ' . $role . ' | ' . $font . ' | ' . $weight . ' | ' . $size . ' | ' . $lh . ' | ' . $ls . ' |';
            }

            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    protected function renderToneOfVoiceBoard(array $data): string
    {
        $lines = [];
        $lines[] = '## Tone of Voice: ' . ($data['name'] ?? 'Tone of Voice');
        $lines[] = '';

        if (!empty($data['description'])) {
            $lines[] = $data['description'];
            $lines[] = '';
        }

        // Dimensions
        if (!empty($data['dimensions'])) {
            $lines[] = '### Dimensionen';
            $lines[] = '';
            foreach ($data['dimensions'] as $dim) {
                $value = $dim['value'] ?? 50;
                $lines[] = '- **' . ($dim['name'] ?? '') . ':** ' . ($dim['label_left'] ?? '') . ' ←(' . $value . ')→ ' . ($dim['label_right'] ?? '');
            }
            $lines[] = '';
        }

        // Entries
        if (!empty($data['entries'])) {
            $lines[] = '### Messaging';
            $lines[] = '';

            foreach ($data['entries'] as $entry) {
                $typeLabel = $entry['type_label'] ?? $entry['type'] ?? '';
                $lines[] = '#### ' . ($entry['name'] ?? '') . ' `' . $typeLabel . '`';
                $lines[] = '';
                if (!empty($entry['content'])) {
                    $lines[] = $entry['content'];
                    $lines[] = '';
                }
                if (!empty($entry['description'])) {
                    $lines[] = '*' . $entry['description'] . '*';
                    $lines[] = '';
                }
                if (!empty($entry['example_positive'])) {
                    $lines[] = '- **So ja:** ' . $this->inlineText($entry['example_positive']);
                }
                if (!empty($entry['example_negative'])) {
                    $lines[] = '- **So nein:** ' . $this->inlineText($entry['example_negative']);
                }
                $lines[] = '';
            }
        }

        return implode("\n", $lines);
    }

    protected function renderContentBoard(array $data): string
    {
        $lines = [];
        $lines[] = '## Content: ' . ($data['name'] ?? 'Content Board');
        $lines[] = '';

        if (!empty($data['description'])) {
            $lines[] = $data['description'];
            $lines[] = '';
        }

        // SEO metadata
        if (!empty($data['meta_title']) || !empty($data['meta_description'])) {
            $lines[] = '**SEO:**';
            if (!empty($data['meta_title'])) {
                $lines[] = '- Meta Title: ' . $data['meta_title'];
            }
            if (!empty($data['meta_description'])) {
                $lines[] = '- Meta Description: ' . $data['meta_description'];
            }
            $lines[] = '';
        }

        if (empty($data['blocks'])) {
            return implode("\n", $lines);
        }

        foreach ($data['blocks'] as $block) {
            $sectionTag = !empty($block['section_type']) ? ' `' . $block['section_type'] . '`' : '';
            $lines[] = '### ' . ($block['name'] ?? 'Block') . $sectionTag;
            $lines[] = '';

            if (!empty($block['description'])) {
                $lines[] = $block['description'];
                $lines[] = '';
            }

            if (!empty($block['content']['text'])) {
                $lines[] = $block['content']['text'];
                $lines[] = '';
            }
        }

        return implode("\n", $lines);
    }

    protected function renderMultiContentBoard(array $data): string
    {
        $lines = [];
        $lines[] = '## Multi-Content: ' . ($data['name'] ?? 'Multi-Content Board');
        $lines[] = '';

        if (!empty($data['description'])) {
            $lines[] = $data['description'];
            $lines[] = '';
        }

        foreach ($data['slots'] ?? [] as $slot) {
            $lines[] = '### ' . ($slot['name'] ?? 'Slot');
            $lines[] = '';

            foreach ($slot['content_boards'] ?? [] as $cb) {
                $lines[] = $this->renderContentBoard($cb);
            }
        }

        return implode("\n", $lines);
    }

    protected function renderSlotBoard(array $data, string $typeLabel): string
    {
        $lines = [];
        $lines[] = '## ' . $typeLabel . ': ' . ($data['name'] ?? 'Board');
        $lines[] = '';

        if (!empty($data['description'])) {
            $lines[] = $data['description'];
            $lines[] = '';
        }

        foreach ($data['slots'] ?? [] as $slot) {
            $lines[] = '### ' . ($slot['name'] ?? 'Slot');
            $lines[] = '';

            foreach ($slot['cards'] ?? [] as $card) {
                $lines[] = '#### ' . ($card['title'] ?? 'Karte');
                $lines[] = '';
                if (!empty($card['description'])) {
                    $lines[] = $card['description'];
                    $lines[] = '';
                }
                if (!empty($card['body_md'])) {
                    $lines[] = $card['body_md'];
                    $lines[] = '';
                }
            }
        }

        return implode("\n", $lines);
    }

    protected function renderGuidelineBoard(array $data): string
    {
        $lines = [];
        $lines[] = '## Guidelines: ' . ($data['name'] ?? 'Guidelines');
        $lines[] = '';

        if (!empty($data['description'])) {
            $lines[] = $data['description'];
            $lines[] = '';
        }

        foreach ($data['chapters'] ?? [] as $chapter) {
            $lines[] = '### ' . ($chapter['title'] ?? 'Kapitel');
            $lines[] = '';

            if (!empty($chapter['description'])) {
                $lines[] = $chapter['description'];
                $lines[] = '';
            }

            foreach ($chapter['entries'] ?? [] as $entry) {
                $lines[] = '#### ' . ($entry['title'] ?? 'Regel');
                $lines[] = '';

                if (!empty($entry['rule_text'])) {
                    $lines[] = $entry['rule_text'];
                    $lines[] = '';
                }

                if (!empty($entry['rationale'])) {
                    $lines[] = '*' . $this->inlineText($entry['rationale']) . '*';
                    $lines[] = '';
                }

                if (!empty($entry['do_example'])) {
                    $lines[] = '- **Do:** ' . $this->inlineText($entry['do_example']);
                }
                if (!empty($entry['dont_example'])) {
                    $lines[] = '- **Don\'t:** ' . $this->inlineText($entry['dont_example']);
                }
                if (!empty($entry['do_example']) || !empty($entry['dont_example'])) {
                    $lines[] = '';
                }
            }
        }

        return implode("\n", $lines);
    }

    protected function renderIntakeBoard(array $data): string
    {
        $lines = [];
        $lines[] = '## Intake: ' . ($data['name'] ?? 'Intake Board');
        $lines[] = '';

        $status = $data['status'] ?? 'draft';
        $lines[] = '- **Status:** `' . $status . '`';

        if (!empty($data['ai_personality'])) {
            $lines[] = '- **AI-Personality:** ' . $data['ai_personality'];
        }
        if (!empty($data['industry_context'])) {
            $lines[] = '- **Branchenkontext:** ' . $data['industry_context'];
        }

        $lines[] = '- **Sessions:** ' . ($data['session_count'] ?? 0) . ' gesamt, ' . ($data['completed_session_count'] ?? 0) . ' abgeschlossen';
        $lines[] = '';

        if (!empty($data['description'])) {
            $lines[] = $data['description'];
            $lines[] = '';
        }

        if (!empty($data['blocks'])) {
            $lines[] = '### Fragen';
            $lines[] = '';
            $lines[] = '| # | Name | Typ | Pflicht | Beschreibung |';
            $lines[] = '|---|------|-----|---------|-------------|';

            foreach ($data['blocks'] as $i => $block) {
                $def = $block['definition'] ?? [];
                $name = $def['name'] ?? '—';
                $type = !empty($def['block_type']) ? '`' . $def['block_type'] . '`' : '—';
                $required = ($block['is_required'] ?? false) ? 'Ja' : 'Nein';
                $desc = !empty($def['description']) ? $this->inlineText($def['description']) : '—';
                $lines[] = '| ' . ($i + 1) . ' | ' . $name . ' | ' . $type . ' | ' . $required . ' | ' . $desc . ' |';
            }

            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * Collapse multiline text into a single line for table cells / inline usage.
     */
    protected function inlineText(string $text): string
    {
        return str_replace(["\r\n", "\r", "\n"], ' ', trim($text));
    }
}

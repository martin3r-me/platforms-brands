<?php

namespace Platform\Brands\Services\Export;

class PdfExportFormat implements ExportFormatInterface
{
    public function getKey(): string
    {
        return 'pdf';
    }

    public function getLabel(): string
    {
        return 'PDF';
    }

    public function getMimeType(): string
    {
        return 'application/pdf';
    }

    public function getFileExtension(): string
    {
        return 'pdf';
    }

    public function exportBoard(array $boardData, array $brandContext): string
    {
        $html = $this->renderBoardHtml($boardData, $brandContext);
        return $this->htmlToPdf($html);
    }

    public function exportBrand(array $brandData): string
    {
        $html = $this->renderBrandHtml($brandData);
        return $this->htmlToPdf($html);
    }

    protected function htmlToPdf(string $html): string
    {
        // Use DomPDF if available, otherwise fallback to HTML-based PDF
        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            return $dompdf->output();
        }

        // Fallback: Return HTML with PDF-like headers for browser printing
        return $html;
    }

    protected function getPrimaryColor(array $brandContext): string
    {
        return $brandContext['primary_color'] ?? '#1a1a2e';
    }

    protected function getSecondaryColor(array $brandContext): string
    {
        return $brandContext['secondary_color'] ?? '#16213e';
    }

    protected function getAccentColor(array $brandContext): string
    {
        return $brandContext['accent_color'] ?? '#0f3460';
    }

    protected function renderCssVariables(array $brandContext): string
    {
        $primary = $this->getPrimaryColor($brandContext);
        $secondary = $this->getSecondaryColor($brandContext);
        $accent = $this->getAccentColor($brandContext);

        return "
            :root {
                --brand-primary: {$primary};
                --brand-secondary: {$secondary};
                --brand-accent: {$accent};
            }
        ";
    }

    protected function renderBaseStyles(array $brandContext): string
    {
        return '
            ' . $this->renderCssVariables($brandContext) . '
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
                color: #1a1a2e;
                line-height: 1.6;
                font-size: 11pt;
            }
            .page { page-break-after: always; padding: 40px; }
            .page:last-child { page-break-after: auto; }
            .cover {
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                text-align: center;
                background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-secondary) 100%);
                color: white;
                padding: 60px;
            }
            .cover h1 {
                font-size: 36pt;
                font-weight: 700;
                margin-bottom: 12px;
                letter-spacing: -0.5px;
            }
            .cover .subtitle {
                font-size: 14pt;
                opacity: 0.85;
                margin-bottom: 40px;
            }
            .cover .export-date {
                font-size: 10pt;
                opacity: 0.6;
                margin-top: auto;
            }
            .section-header {
                background: var(--brand-primary);
                color: white;
                padding: 16px 24px;
                margin: 0 -40px 24px -40px;
                font-size: 16pt;
                font-weight: 600;
            }
            .board-title {
                font-size: 14pt;
                font-weight: 600;
                color: var(--brand-primary);
                margin-bottom: 8px;
                padding-bottom: 8px;
                border-bottom: 2px solid var(--brand-primary);
            }
            .board-description {
                color: #555;
                margin-bottom: 16px;
                font-size: 10pt;
            }
            .board-meta {
                display: inline-block;
                background: #f0f0f0;
                padding: 3px 10px;
                border-radius: 4px;
                font-size: 9pt;
                color: #666;
                margin-bottom: 16px;
            }
            .entry {
                margin-bottom: 16px;
                padding: 12px 16px;
                border-left: 3px solid var(--brand-primary);
                background: #fafafa;
            }
            .entry-title {
                font-weight: 600;
                font-size: 11pt;
                margin-bottom: 4px;
            }
            .entry-body {
                font-size: 10pt;
                color: #444;
            }
            .slot-header {
                font-size: 12pt;
                font-weight: 600;
                color: #333;
                margin: 16px 0 8px 0;
                padding: 6px 12px;
                background: #eee;
                border-radius: 4px;
            }
            .color-swatch {
                display: inline-block;
                width: 60px;
                height: 30px;
                border-radius: 4px;
                vertical-align: middle;
                margin-right: 8px;
                border: 1px solid #ddd;
            }
            .color-entry {
                margin-bottom: 8px;
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .color-label { font-weight: 600; font-size: 10pt; }
            .color-value { font-size: 9pt; color: #666; font-family: monospace; }
            .ci-field { margin-bottom: 8px; }
            .ci-field-label { font-size: 9pt; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
            .ci-field-value { font-size: 11pt; font-weight: 500; }
            .footer {
                margin-top: 40px;
                padding-top: 16px;
                border-top: 1px solid #ddd;
                font-size: 8pt;
                color: #999;
                text-align: center;
            }
            table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
            th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 10pt; }
            th { background: #f5f5f5; font-weight: 600; color: #333; }
        ';
    }

    protected function renderBoardHtml(array $boardData, array $brandContext): string
    {
        $brandName = e($brandContext['name'] ?? 'Marke');
        $boardName = e($boardData['name'] ?? 'Board');
        $boardType = e($boardData['type'] ?? 'board');
        $boardDescription = e($boardData['description'] ?? '');
        $date = now()->format('d.m.Y H:i');
        $styles = $this->renderBaseStyles($brandContext);
        $content = $this->renderBoardContent($boardData, $brandContext);

        return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>{$boardName} – {$brandName} Export</title>
    <style>{$styles}</style>
</head>
<body>
    <div class="page cover">
        <h1>{$boardName}</h1>
        <div class="subtitle">{$brandName} – {$boardType}</div>
        {$this->renderDescriptionBlock($boardDescription)}
        <div class="export-date">Exportiert am {$date}</div>
    </div>
    <div class="page">
        {$content}
        <div class="footer">Export: {$brandName} – {$boardName} | {$date}</div>
    </div>
</body>
</html>
HTML;
    }

    protected function renderBrandHtml(array $brandData): string
    {
        $brandName = e($brandData['name'] ?? 'Marke');
        $brandDescription = e($brandData['description'] ?? '');
        $date = now()->format('d.m.Y H:i');

        $brandContext = [
            'name' => $brandData['name'] ?? '',
            'primary_color' => $brandData['settings']['primary_color'] ?? '#1a1a2e',
            'secondary_color' => $brandData['settings']['secondary_color'] ?? '#16213e',
            'accent_color' => $brandData['settings']['accent_color'] ?? '#0f3460',
        ];
        $styles = $this->renderBaseStyles($brandContext);

        $pages = '';

        // CI Boards
        foreach ($brandData['ci_boards'] ?? [] as $board) {
            $pages .= '<div class="page">';
            $pages .= '<div class="section-header">Corporate Identity</div>';
            $pages .= $this->renderBoardContent($board, $brandContext);
            $pages .= '<div class="footer">Brand Book: ' . e($brandName) . ' | ' . $date . '</div>';
            $pages .= '</div>';
        }

        // Content Boards
        foreach ($brandData['content_boards'] ?? [] as $board) {
            $pages .= '<div class="page">';
            $pages .= '<div class="section-header">Content</div>';
            $pages .= $this->renderBoardContent($board, $brandContext);
            $pages .= '<div class="footer">Brand Book: ' . e($brandName) . ' | ' . $date . '</div>';
            $pages .= '</div>';
        }

        // Social Boards
        foreach ($brandData['social_boards'] ?? [] as $board) {
            $pages .= '<div class="page">';
            $pages .= '<div class="section-header">Social Media</div>';
            $pages .= $this->renderBoardContent($board, $brandContext);
            $pages .= '<div class="footer">Brand Book: ' . e($brandName) . ' | ' . $date . '</div>';
            $pages .= '</div>';
        }

        // Kanban Boards
        foreach ($brandData['kanban_boards'] ?? [] as $board) {
            $pages .= '<div class="page">';
            $pages .= '<div class="section-header">Kanban</div>';
            $pages .= $this->renderBoardContent($board, $brandContext);
            $pages .= '<div class="footer">Brand Book: ' . e($brandName) . ' | ' . $date . '</div>';
            $pages .= '</div>';
        }

        // Multi-Content Boards
        foreach ($brandData['multi_content_boards'] ?? [] as $board) {
            $pages .= '<div class="page">';
            $pages .= '<div class="section-header">Multi-Content</div>';
            $pages .= $this->renderBoardContent($board, $brandContext);
            $pages .= '<div class="footer">Brand Book: ' . e($brandName) . ' | ' . $date . '</div>';
            $pages .= '</div>';
        }

        // If no boards exist, add an empty page note
        if (empty($pages)) {
            $pages = '<div class="page"><p style="text-align:center;color:#999;margin-top:40px;">Diese Marke enthält noch keine Boards.</p></div>';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>{$brandName} – Brand Book Export</title>
    <style>{$styles}</style>
</head>
<body>
    <div class="page cover">
        <h1>{$brandName}</h1>
        <div class="subtitle">Brand Book</div>
        {$this->renderDescriptionBlock($brandDescription)}
        <div class="export-date">Exportiert am {$date}</div>
    </div>
    {$pages}
</body>
</html>
HTML;
    }

    protected function renderDescriptionBlock(string $description): string
    {
        if (empty($description)) {
            return '';
        }
        return '<div class="subtitle" style="font-size:11pt;opacity:0.7;">' . $description . '</div>';
    }

    protected function renderBoardContent(array $boardData, array $brandContext): string
    {
        $html = '';
        $type = $boardData['type'] ?? 'unknown';
        $html .= '<div class="board-title">' . e($boardData['name'] ?? 'Board') . '</div>';

        if (!empty($boardData['description'])) {
            $html .= '<div class="board-description">' . e($boardData['description']) . '</div>';
        }

        $html .= '<span class="board-meta">' . e(ucfirst($type)) . ' Board</span>';

        switch ($type) {
            case 'ci':
                $html .= $this->renderCiBoardContent($boardData);
                break;
            case 'content':
                $html .= $this->renderContentBoardContent($boardData);
                break;
            case 'social':
                $html .= $this->renderSocialBoardContent($boardData);
                break;
            case 'kanban':
                $html .= $this->renderKanbanBoardContent($boardData);
                break;
            case 'multi_content':
                $html .= $this->renderMultiContentBoardContent($boardData);
                break;
        }

        return $html;
    }

    protected function renderCiBoardContent(array $data): string
    {
        $html = '<div style="margin-top:16px;">';

        // CI fields
        $fields = [
            'slogan' => 'Slogan',
            'tagline' => 'Tagline',
            'font_family' => 'Schriftart',
            'primary_color' => 'Primärfarbe',
            'secondary_color' => 'Sekundärfarbe',
            'accent_color' => 'Akzentfarbe',
        ];

        foreach ($fields as $key => $label) {
            if (!empty($data[$key])) {
                $html .= '<div class="ci-field">';
                $html .= '<div class="ci-field-label">' . e($label) . '</div>';
                if (str_contains($key, 'color')) {
                    $color = e($data[$key]);
                    $html .= '<div class="ci-field-value"><span class="color-swatch" style="background:' . $color . '"></span> ' . $color . '</div>';
                } else {
                    $html .= '<div class="ci-field-value">' . e($data[$key]) . '</div>';
                }
                $html .= '</div>';
            }
        }

        // Color palette
        if (!empty($data['colors'])) {
            $html .= '<h3 style="margin-top:20px;margin-bottom:10px;font-size:12pt;">Farbpalette</h3>';
            foreach ($data['colors'] as $color) {
                $hex = e($color['color'] ?? '#000');
                $html .= '<div class="color-entry">';
                $html .= '<span class="color-swatch" style="background:' . $hex . '"></span>';
                $html .= '<span class="color-label">' . e($color['title'] ?? '') . '</span>';
                $html .= '<span class="color-value">' . $hex . '</span>';
                if (!empty($color['description'])) {
                    $html .= '<span style="font-size:9pt;color:#888;margin-left:8px;">' . e($color['description']) . '</span>';
                }
                $html .= '</div>';
            }
        }

        $html .= '</div>';
        return $html;
    }

    protected function renderContentBoardContent(array $data): string
    {
        $html = '';
        if (empty($data['blocks'])) {
            $html .= '<p style="color:#999;margin-top:16px;">Keine Inhalte vorhanden.</p>';
            return $html;
        }

        foreach ($data['blocks'] as $block) {
            $html .= '<div class="entry">';
            $html .= '<div class="entry-title">' . e($block['name'] ?? 'Block') . '</div>';
            if (!empty($block['description'])) {
                $html .= '<div class="entry-body">' . e($block['description']) . '</div>';
            }
            if (!empty($block['content']['text'])) {
                $html .= '<div class="entry-body" style="margin-top:4px;">' . nl2br(e($block['content']['text'])) . '</div>';
            }
            $html .= '</div>';
        }

        return $html;
    }

    protected function renderSocialBoardContent(array $data): string
    {
        $html = '';
        if (empty($data['slots'])) {
            $html .= '<p style="color:#999;margin-top:16px;">Keine Inhalte vorhanden.</p>';
            return $html;
        }

        foreach ($data['slots'] as $slot) {
            $html .= '<div class="slot-header">' . e($slot['name'] ?? 'Slot') . '</div>';
            if (empty($slot['cards'])) {
                $html .= '<p style="color:#999;font-size:9pt;margin-left:12px;">Keine Karten in diesem Slot.</p>';
                continue;
            }
            foreach ($slot['cards'] as $card) {
                $html .= '<div class="entry">';
                $html .= '<div class="entry-title">' . e($card['title'] ?? 'Karte') . '</div>';
                if (!empty($card['description'])) {
                    $html .= '<div class="entry-body">' . e($card['description']) . '</div>';
                }
                if (!empty($card['body_md'])) {
                    $html .= '<div class="entry-body" style="margin-top:4px;">' . nl2br(e($card['body_md'])) . '</div>';
                }
                $html .= '</div>';
            }
        }

        return $html;
    }

    protected function renderKanbanBoardContent(array $data): string
    {
        $html = '';
        if (empty($data['slots'])) {
            $html .= '<p style="color:#999;margin-top:16px;">Keine Inhalte vorhanden.</p>';
            return $html;
        }

        foreach ($data['slots'] as $slot) {
            $html .= '<div class="slot-header">' . e($slot['name'] ?? 'Slot') . '</div>';
            if (empty($slot['cards'])) {
                $html .= '<p style="color:#999;font-size:9pt;margin-left:12px;">Keine Karten in diesem Slot.</p>';
                continue;
            }
            foreach ($slot['cards'] as $card) {
                $html .= '<div class="entry">';
                $html .= '<div class="entry-title">' . e($card['title'] ?? 'Karte') . '</div>';
                if (!empty($card['description'])) {
                    $html .= '<div class="entry-body">' . e($card['description']) . '</div>';
                }
                $html .= '</div>';
            }
        }

        return $html;
    }

    protected function renderMultiContentBoardContent(array $data): string
    {
        $html = '';
        if (empty($data['slots'])) {
            $html .= '<p style="color:#999;margin-top:16px;">Keine Inhalte vorhanden.</p>';
            return $html;
        }

        foreach ($data['slots'] as $slot) {
            $html .= '<div class="slot-header">' . e($slot['name'] ?? 'Slot') . '</div>';
            if (empty($slot['content_boards'])) {
                $html .= '<p style="color:#999;font-size:9pt;margin-left:12px;">Keine Content Boards in diesem Slot.</p>';
                continue;
            }
            foreach ($slot['content_boards'] as $cb) {
                $html .= '<div style="margin-left:16px;margin-bottom:16px;">';
                $html .= '<div style="font-weight:600;font-size:11pt;margin-bottom:6px;">' . e($cb['name'] ?? 'Content Board') . '</div>';
                $html .= $this->renderContentBoardContent($cb);
                $html .= '</div>';
            }
        }

        return $html;
    }
}

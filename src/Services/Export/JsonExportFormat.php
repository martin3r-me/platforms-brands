<?php

namespace Platform\Brands\Services\Export;

class JsonExportFormat implements ExportFormatInterface
{
    public function getKey(): string
    {
        return 'json';
    }

    public function getLabel(): string
    {
        return 'JSON';
    }

    public function getMimeType(): string
    {
        return 'application/json';
    }

    public function getFileExtension(): string
    {
        return 'json';
    }

    public function exportBoard(array $boardData, array $brandContext): string
    {
        $export = [
            'meta' => [
                'export_type' => 'board',
                'format' => 'json',
                'version' => '1.0',
                'exported_at' => now()->toIso8601String(),
                'brand' => $brandContext,
            ],
            'board' => $boardData,
        ];

        return json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function exportBrand(array $brandData): string
    {
        $export = [
            'meta' => [
                'export_type' => 'brand',
                'format' => 'json',
                'version' => '1.0',
                'exported_at' => now()->toIso8601String(),
            ],
            'brand' => $brandData,
        ];

        return json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

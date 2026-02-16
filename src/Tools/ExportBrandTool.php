<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Services\BrandsExportService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class ExportBrandTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.brand.export';
    }

    public function getDescription(): string
    {
        return 'POST /brands/{id}/export - Exportiert eine komplette Marke mit allen Boards, Einträgen und Einstellungen. REST-Parameter: id (required, integer) - Marken-ID. format (required, string) - Export-Format ("json" oder "pdf"). Gibt die Export-Daten zurück.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID der Marke.'
                ],
                'format' => [
                    'type' => 'string',
                    'description' => 'REST-Parameter (required): Export-Format. Erlaubte Werte: "json", "pdf".',
                    'enum' => ['json', 'pdf'],
                ],
            ],
            'required' => ['id', 'format'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            if (empty($arguments['id'])) {
                return ToolResult::error('VALIDATION_ERROR', 'Marken-ID ist erforderlich.');
            }

            if (empty($arguments['format'])) {
                return ToolResult::error('VALIDATION_ERROR', 'Export-Format ist erforderlich (json oder pdf).');
            }

            $brand = BrandsBrand::find($arguments['id']);
            if (!$brand) {
                return ToolResult::error('BRAND_NOT_FOUND', 'Marke nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('view', $brand);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Kein Zugriff auf diese Marke.');
            }

            $service = app(BrandsExportService::class);
            $result = $service->exportBrand($brand, $arguments['format']);

            if ($arguments['format'] === 'json') {
                return ToolResult::success([
                    'format' => 'json',
                    'filename' => $result['filename'],
                    'data' => json_decode($result['content'], true),
                ]);
            }

            // For PDF, return metadata (actual download via HTTP route)
            return ToolResult::success([
                'format' => 'pdf',
                'filename' => $result['filename'],
                'download_url' => route('brands.export.download-brand', ['brandsBrand' => $brand->id, 'format' => 'pdf']),
                'note' => 'PDF-Download über die angegebene URL verfügbar.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Export: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'export', 'brand'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}

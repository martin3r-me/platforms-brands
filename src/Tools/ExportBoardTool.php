<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsCiBoard;
use Platform\Brands\Models\BrandsContentBoard;
use Platform\Brands\Models\BrandsSocialBoard;
use Platform\Brands\Models\BrandsKanbanBoard;
use Platform\Brands\Models\BrandsMultiContentBoard;
use Platform\Brands\Services\BrandsExportService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class ExportBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.board.export';
    }

    public function getDescription(): string
    {
        return 'POST /brands/boards/{type}/{id}/export - Exportiert ein einzelnes Board. REST-Parameter: type (required, string) - Board-Typ ("ci-board", "content-board", "social-board", "kanban-board", "multi-content-board"). id (required, integer) - Board-ID. format (required, string) - Export-Format ("json" oder "pdf").';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'type' => [
                    'type' => 'string',
                    'description' => 'REST-Parameter (required): Board-Typ.',
                    'enum' => ['ci-board', 'content-board', 'social-board', 'kanban-board', 'multi-content-board'],
                ],
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): Board-ID.',
                ],
                'format' => [
                    'type' => 'string',
                    'description' => 'REST-Parameter (required): Export-Format.',
                    'enum' => ['json', 'pdf'],
                ],
            ],
            'required' => ['type', 'id', 'format'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            if (empty($arguments['type']) || empty($arguments['id']) || empty($arguments['format'])) {
                return ToolResult::error('VALIDATION_ERROR', 'type, id und format sind erforderlich.');
            }

            $board = $this->resolveBoard($arguments['type'], $arguments['id']);
            if (!$board) {
                return ToolResult::error('BOARD_NOT_FOUND', 'Board nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('view', $board->brand);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Kein Zugriff auf dieses Board.');
            }

            $service = app(BrandsExportService::class);
            $result = $service->exportBoard($board, $arguments['format']);

            if ($arguments['format'] === 'json') {
                return ToolResult::success([
                    'format' => 'json',
                    'filename' => $result['filename'],
                    'data' => json_decode($result['content'], true),
                ]);
            }

            return ToolResult::success([
                'format' => 'pdf',
                'filename' => $result['filename'],
                'download_url' => route('brands.export.download-board', [
                    'boardType' => $arguments['type'],
                    'boardId' => $arguments['id'],
                    'format' => 'pdf',
                ]),
                'note' => 'PDF-Download über die angegebene URL verfügbar.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Export: ' . $e->getMessage());
        }
    }

    protected function resolveBoard(string $type, int $id): ?object
    {
        return match ($type) {
            'ci-board' => BrandsCiBoard::with('brand', 'colors')->find($id),
            'content-board' => BrandsContentBoard::with('brand', 'blocks.content')->find($id),
            'social-board' => BrandsSocialBoard::with('brand', 'slots.cards')->find($id),
            'kanban-board' => BrandsKanbanBoard::with('brand', 'slots.cards')->find($id),
            'multi-content-board' => BrandsMultiContentBoard::with('brand', 'slots.contentBoards.blocks.content')->find($id),
            default => null,
        };
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'export', 'board'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}

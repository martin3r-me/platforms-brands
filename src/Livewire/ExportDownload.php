<?php

namespace Platform\Brands\Livewire;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsCiBoard;
use Platform\Brands\Models\BrandsContentBoard;
use Platform\Brands\Models\BrandsSocialBoard;
use Platform\Brands\Models\BrandsKanbanBoard;
use Platform\Brands\Models\BrandsMultiContentBoard;
use Platform\Brands\Models\BrandsTypographyBoard;
use Platform\Brands\Models\BrandsToneOfVoiceBoard;
use Platform\Brands\Services\BrandsExportService;

/**
 * Controller for export downloads (non-Livewire, returns file responses)
 */
class ExportDownload
{
    public function downloadBrand(Request $request, BrandsBrand $brandsBrand, string $format)
    {
        Gate::authorize('view', $brandsBrand);

        $service = app(BrandsExportService::class);
        $result = $service->exportBrand($brandsBrand, $format);

        return response($result['content'], 200, [
            'Content-Type' => $result['mime_type'],
            'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function downloadBoard(Request $request, string $boardType, int $boardId, string $format)
    {
        $board = $this->resolveBoard($boardType, $boardId);

        if (!$board) {
            abort(404, 'Board nicht gefunden.');
        }

        // Authorize via the board's brand
        Gate::authorize('view', $board->brand);

        $service = app(BrandsExportService::class);
        $result = $service->exportBoard($board, $format);

        return response($result['content'], 200, [
            'Content-Type' => $result['mime_type'],
            'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"',
            'Cache-Control' => 'no-store',
        ]);
    }

    protected function resolveBoard(string $type, int $id): ?object
    {
        return match ($type) {
            'ci-board' => BrandsCiBoard::with('brand', 'colors')->find($id),
            'content-board' => BrandsContentBoard::with('brand', 'blocks.content')->find($id),
            'social-board' => BrandsSocialBoard::with('brand', 'slots.cards')->find($id),
            'kanban-board' => BrandsKanbanBoard::with('brand', 'slots.cards')->find($id),
            'multi-content-board' => BrandsMultiContentBoard::with('brand', 'slots.contentBoards.blocks.content')->find($id),
            'typography-board' => BrandsTypographyBoard::with('brand', 'entries')->find($id),
            'tone-of-voice-board' => BrandsToneOfVoiceBoard::with('brand', 'entries', 'dimensions')->find($id),
            default => null,
        };
    }
}

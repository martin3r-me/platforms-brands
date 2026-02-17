<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsAssetBoard;
use Platform\Brands\Models\BrandsAsset;
use Platform\Brands\Models\BrandsAssetVersion;
use Livewire\Attributes\On;

class AssetBoard extends Component
{
    use WithFileUploads;

    public BrandsAssetBoard $assetBoard;
    public $newFiles = [];
    public $filterType = '';
    public $filterTag = '';

    public function mount(BrandsAssetBoard $brandsAssetBoard)
    {
        $this->assetBoard = $brandsAssetBoard->fresh()->load(['assets']);
        $this->authorize('view', $this->assetBoard);
    }

    #[On('updateAssetBoard')]
    public function updateAssetBoard()
    {
        $this->assetBoard->refresh();
        $this->assetBoard->load(['assets']);
    }

    public function uploadFiles()
    {
        $this->authorize('update', $this->assetBoard);

        $this->validate([
            'newFiles.*' => 'file|max:51200', // Max 50MB pro Datei
        ]);

        $user = Auth::user();

        foreach ($this->newFiles as $file) {
            $path = $file->store('brands/assets/' . $this->assetBoard->id, 'public');
            $originalName = $file->getClientOriginalName();

            $asset = BrandsAsset::create([
                'asset_board_id' => $this->assetBoard->id,
                'name' => pathinfo($originalName, PATHINFO_FILENAME),
                'file_path' => $path,
                'file_name' => $originalName,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'asset_type' => 'other',
                'current_version' => 1,
            ]);

            // Erste Version anlegen
            BrandsAssetVersion::create([
                'asset_id' => $asset->id,
                'version_number' => 1,
                'file_path' => $path,
                'file_name' => $originalName,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'change_note' => 'Erstversion',
                'user_id' => $user->id,
            ]);
        }

        $this->newFiles = [];
        $this->assetBoard->refresh();
        $this->assetBoard->load(['assets']);
    }

    public function deleteAsset($assetId)
    {
        $this->authorize('update', $this->assetBoard);

        $asset = BrandsAsset::findOrFail($assetId);
        $asset->delete();

        $this->assetBoard->refresh();
        $this->assetBoard->load(['assets']);
    }

    public function updateAssetOrder($groups)
    {
        $this->authorize('update', $this->assetBoard);

        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                $asset = BrandsAsset::find($item['value']);
                if ($asset) {
                    $asset->order = $item['order'];
                    $asset->save();
                }
            }
        }

        $this->assetBoard->refresh();
        $this->assetBoard->load(['assets']);
    }

    public function render()
    {
        $user = Auth::user();

        $query = $this->assetBoard->assets()->orderBy('order');

        if ($this->filterType) {
            $query->where('asset_type', $this->filterType);
        }

        if ($this->filterTag) {
            $query->whereJsonContains('tags', $this->filterTag);
        }

        $assets = $query->get();
        $allAssets = $this->assetBoard->assets()->orderBy('order')->get();

        // Sammle alle Tags für die Sidebar
        $allTags = $allAssets->pluck('tags')->filter()->flatten()->countBy();

        // Asset-Typ Zähler
        $typeCounts = $allAssets->groupBy('asset_type')->map->count();

        return view('brands::livewire.asset-board', [
            'user' => $user,
            'assets' => $assets,
            'allAssets' => $allAssets,
            'allTags' => $allTags,
            'typeCounts' => $typeCounts,
        ])->layout('platform::layouts.app');
    }
}

<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsAssetBoard;
use Platform\Brands\Models\BrandsAsset;
use Platform\Brands\Models\BrandsAssetVersion;
use Livewire\Attributes\On;

class AssetModal extends Component
{
    use WithFileUploads;

    public $modalShow = false;
    public $asset;
    public $assetBoardId;

    // Asset fields
    public $assetName = '';
    public $assetDescription = '';
    public $assetType = 'other';
    public $assetTags = [];
    public $assetFormats = [];
    public $assetFile = null;
    public $changeNote = '';

    // Available tags and types
    public $availableTags = ['Instagram', 'LinkedIn', 'Facebook', 'Twitter', 'Print', 'Web', 'E-Mail', 'Intern'];
    public $availableTypes = [
        'sm_template' => 'Social Media Template',
        'letterhead' => 'Briefkopf',
        'signature' => 'E-Mail-Signatur',
        'banner' => 'Banner',
        'presentation' => 'Präsentation',
        'other' => 'Sonstiges',
    ];
    public $availableFormats = ['png', 'jpg', 'svg', 'pdf', 'psd', 'ai', 'eps', 'docx', 'pptx'];

    #[On('open-modal-asset')]
    public function openModal($assetBoardId, $assetId = null)
    {
        $this->assetBoardId = $assetBoardId;
        $this->resetFields();

        if ($assetId) {
            $this->asset = BrandsAsset::findOrFail($assetId);
            $this->fillFromAsset();
        } else {
            $this->asset = null;
        }

        $this->modalShow = true;
    }

    protected function resetFields()
    {
        $this->assetName = '';
        $this->assetDescription = '';
        $this->assetType = 'other';
        $this->assetTags = [];
        $this->assetFormats = [];
        $this->assetFile = null;
        $this->changeNote = '';
    }

    protected function fillFromAsset()
    {
        $this->assetName = $this->asset->name ?? '';
        $this->assetDescription = $this->asset->description ?? '';
        $this->assetType = $this->asset->asset_type ?? 'other';
        $this->assetTags = $this->asset->tags ?? [];
        $this->assetFormats = $this->asset->available_formats ?? [];
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function toggleTag($tag)
    {
        if (in_array($tag, $this->assetTags)) {
            $this->assetTags = array_values(array_diff($this->assetTags, [$tag]));
        } else {
            $this->assetTags[] = $tag;
        }
    }

    public function toggleFormat($format)
    {
        if (in_array($format, $this->assetFormats)) {
            $this->assetFormats = array_values(array_diff($this->assetFormats, [$format]));
        } else {
            $this->assetFormats[] = $format;
        }
    }

    public function rules(): array
    {
        $rules = [
            'assetName' => 'required|string|max:255',
            'assetType' => 'required|in:sm_template,letterhead,signature,banner,presentation,other',
        ];

        if (!$this->asset) {
            $rules['assetFile'] = 'required|file|max:51200';
        }

        return $rules;
    }

    public function save()
    {
        $this->validate();

        $board = BrandsAssetBoard::findOrFail($this->assetBoardId);
        $this->authorize('update', $board);

        $user = Auth::user();

        $data = [
            'name' => $this->assetName,
            'description' => $this->assetDescription ?: null,
            'asset_type' => $this->assetType,
            'tags' => !empty($this->assetTags) ? $this->assetTags : null,
            'available_formats' => !empty($this->assetFormats) ? $this->assetFormats : null,
        ];

        if ($this->asset) {
            // Update
            if ($this->assetFile) {
                $path = $this->assetFile->store('brands/assets/' . $this->assetBoardId, 'public');
                $originalName = $this->assetFile->getClientOriginalName();

                // Neue Version erstellen
                $newVersion = $this->asset->current_version + 1;
                BrandsAssetVersion::create([
                    'asset_id' => $this->asset->id,
                    'version_number' => $newVersion,
                    'file_path' => $path,
                    'file_name' => $originalName,
                    'mime_type' => $this->assetFile->getMimeType(),
                    'file_size' => $this->assetFile->getSize(),
                    'change_note' => $this->changeNote ?: null,
                    'user_id' => $user->id,
                ]);

                $data['file_path'] = $path;
                $data['file_name'] = $originalName;
                $data['mime_type'] = $this->assetFile->getMimeType();
                $data['file_size'] = $this->assetFile->getSize();
                $data['current_version'] = $newVersion;
            }
            $this->asset->update($data);
        } else {
            // Create
            $path = $this->assetFile->store('brands/assets/' . $this->assetBoardId, 'public');
            $originalName = $this->assetFile->getClientOriginalName();

            $data['asset_board_id'] = $this->assetBoardId;
            $data['file_path'] = $path;
            $data['file_name'] = $originalName;
            $data['mime_type'] = $this->assetFile->getMimeType();
            $data['file_size'] = $this->assetFile->getSize();
            $data['current_version'] = 1;

            $asset = BrandsAsset::create($data);

            // Erste Version anlegen
            BrandsAssetVersion::create([
                'asset_id' => $asset->id,
                'version_number' => 1,
                'file_path' => $path,
                'file_name' => $originalName,
                'mime_type' => $this->assetFile->getMimeType(),
                'file_size' => $this->assetFile->getSize(),
                'change_note' => 'Erstversion',
                'user_id' => $user->id,
            ]);
        }

        $this->dispatch('updateAssetBoard');
        $this->closeModal();
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        $versions = [];
        if ($this->asset) {
            $versions = $this->asset->versions()->orderByDesc('version_number')->get();
        }

        return view('brands::livewire.asset-modal', [
            'versions' => $versions,
        ])->layout('platform::layouts.app');
    }
}

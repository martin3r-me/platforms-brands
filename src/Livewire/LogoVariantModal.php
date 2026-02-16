<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Platform\Brands\Models\BrandsLogoBoard;
use Platform\Brands\Models\BrandsLogoVariant;
use Livewire\Attributes\On;

class LogoVariantModal extends Component
{
    use WithFileUploads;

    public $modalShow = false;
    public $variant;
    public $logoBoardId;
    public $logoUpload;

    // Variant fields
    public $variantName = '';
    public $variantType = 'primary';
    public $variantDescription = '';
    public $variantUsageGuidelines = '';
    public $variantBackgroundColor = '';
    public $variantClearspaceFactor = null;
    public $variantMinWidthPx = null;
    public $variantMinWidthMm = null;

    // Do's & Don'ts
    public $dosList = [];
    public $dontsList = [];

    #[On('open-modal-logo-variant')]
    public function openModal($logoBoardId, $variantId = null)
    {
        $this->logoBoardId = $logoBoardId;
        $this->resetFields();

        if ($variantId) {
            $this->variant = BrandsLogoVariant::findOrFail($variantId);
            $this->fillFromVariant();
        } else {
            $this->variant = null;
        }

        $this->modalShow = true;
    }

    protected function resetFields()
    {
        $this->variantName = '';
        $this->variantType = 'primary';
        $this->variantDescription = '';
        $this->variantUsageGuidelines = '';
        $this->variantBackgroundColor = '';
        $this->variantClearspaceFactor = null;
        $this->variantMinWidthPx = null;
        $this->variantMinWidthMm = null;
        $this->dosList = [];
        $this->dontsList = [];
        $this->logoUpload = null;
    }

    protected function fillFromVariant()
    {
        $this->variantName = $this->variant->name;
        $this->variantType = $this->variant->type;
        $this->variantDescription = $this->variant->description ?? '';
        $this->variantUsageGuidelines = $this->variant->usage_guidelines ?? '';
        $this->variantBackgroundColor = $this->variant->background_color ?? '';
        $this->variantClearspaceFactor = $this->variant->clearspace_factor;
        $this->variantMinWidthPx = $this->variant->min_width_px;
        $this->variantMinWidthMm = $this->variant->min_width_mm;
        $this->dosList = $this->variant->dos ?? [];
        $this->dontsList = $this->variant->donts ?? [];
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'variantName' => 'required|string|max:255',
            'variantType' => 'required|string|in:' . implode(',', array_keys(BrandsLogoVariant::TYPES)),
            'logoUpload' => 'nullable|file|max:10240|mimes:svg,png,pdf,jpg,jpeg,webp,eps,ai',
        ];
    }

    public function addDo()
    {
        $this->dosList[] = ['text' => ''];
    }

    public function removeDo($index)
    {
        unset($this->dosList[$index]);
        $this->dosList = array_values($this->dosList);
    }

    public function addDont()
    {
        $this->dontsList[] = ['text' => ''];
    }

    public function removeDont($index)
    {
        unset($this->dontsList[$index]);
        $this->dontsList = array_values($this->dontsList);
    }

    public function save()
    {
        $this->validate();

        $board = BrandsLogoBoard::findOrFail($this->logoBoardId);
        $this->authorize('update', $board);

        $data = [
            'name' => $this->variantName,
            'type' => $this->variantType,
            'description' => $this->variantDescription ?: null,
            'usage_guidelines' => $this->variantUsageGuidelines ?: null,
            'background_color' => $this->variantBackgroundColor ?: null,
            'clearspace_factor' => $this->variantClearspaceFactor !== null && $this->variantClearspaceFactor !== '' ? (float) $this->variantClearspaceFactor : null,
            'min_width_px' => $this->variantMinWidthPx !== null && $this->variantMinWidthPx !== '' ? (int) $this->variantMinWidthPx : null,
            'min_width_mm' => $this->variantMinWidthMm !== null && $this->variantMinWidthMm !== '' ? (int) $this->variantMinWidthMm : null,
            'dos' => !empty($this->dosList) ? array_values(array_filter($this->dosList, fn($d) => !empty($d['text']))) : null,
            'donts' => !empty($this->dontsList) ? array_values(array_filter($this->dontsList, fn($d) => !empty($d['text']))) : null,
        ];

        // Handle logo file upload
        if ($this->logoUpload) {
            $path = $this->logoUpload->store('brands/logos', 'public');
            $data['file_path'] = $path;
            $data['file_name'] = $this->logoUpload->getClientOriginalName();
            $data['file_format'] = strtolower($this->logoUpload->getClientOriginalExtension());
        }

        if ($this->variant) {
            // Update existing variant - delete old file if replacing
            if ($this->logoUpload && $this->variant->file_path) {
                if (Storage::disk('public')->exists($this->variant->file_path)) {
                    Storage::disk('public')->delete($this->variant->file_path);
                }
            }
            $this->variant->update($data);
        } else {
            $data['logo_board_id'] = $this->logoBoardId;
            BrandsLogoVariant::create($data);
        }

        $this->dispatch('updateLogoBoard');
        $this->closeModal();
    }

    public function closeModal()
    {
        $this->modalShow = false;
        $this->logoUpload = null;
    }

    public function render()
    {
        return view('brands::livewire.logo-variant-modal')->layout('platform::layouts.app');
    }
}

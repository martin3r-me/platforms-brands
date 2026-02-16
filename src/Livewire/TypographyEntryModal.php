<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Platform\Brands\Models\BrandsTypographyBoard;
use Platform\Brands\Models\BrandsTypographyEntry;
use Livewire\Attributes\On;

class TypographyEntryModal extends Component
{
    use WithFileUploads;

    public $modalShow = false;
    public $entry;
    public $typographyBoardId;
    public $fontUpload;
    public $googleFontSearch = '';
    public $googleFontResults = [];
    public $fontSourceTab = 'system';

    // Entry fields
    public $entryName = '';
    public $entryRole = '';
    public $entryFontFamily = 'Inter';
    public $entryFontSource = 'system';
    public $entryFontWeight = 400;
    public $entryFontStyle = 'normal';
    public $entryFontSize = 16;
    public $entryLineHeight = 1.5;
    public $entryLetterSpacing = null;
    public $entryTextTransform = '';
    public $entrySampleText = '';
    public $entryDescription = '';

    #[On('open-modal-typography-entry')]
    public function openModal($typographyBoardId, $entryId = null)
    {
        $this->typographyBoardId = $typographyBoardId;
        $this->resetFields();

        if ($entryId) {
            $this->entry = BrandsTypographyEntry::findOrFail($entryId);
            $this->fillFromEntry();
        } else {
            $this->entry = null;
        }

        $this->modalShow = true;
    }

    protected function resetFields()
    {
        $this->entryName = '';
        $this->entryRole = '';
        $this->entryFontFamily = 'Inter';
        $this->entryFontSource = 'system';
        $this->entryFontWeight = 400;
        $this->entryFontStyle = 'normal';
        $this->entryFontSize = 16;
        $this->entryLineHeight = 1.5;
        $this->entryLetterSpacing = null;
        $this->entryTextTransform = '';
        $this->entrySampleText = '';
        $this->entryDescription = '';
        $this->fontUpload = null;
        $this->googleFontSearch = '';
        $this->googleFontResults = [];
        $this->fontSourceTab = 'system';
    }

    protected function fillFromEntry()
    {
        $this->entryName = $this->entry->name;
        $this->entryRole = $this->entry->role ?? '';
        $this->entryFontFamily = $this->entry->font_family;
        $this->entryFontSource = $this->entry->font_source;
        $this->entryFontWeight = $this->entry->font_weight;
        $this->entryFontStyle = $this->entry->font_style;
        $this->entryFontSize = $this->entry->font_size;
        $this->entryLineHeight = $this->entry->line_height;
        $this->entryLetterSpacing = $this->entry->letter_spacing;
        $this->entryTextTransform = $this->entry->text_transform ?? '';
        $this->entrySampleText = $this->entry->sample_text ?? '';
        $this->entryDescription = $this->entry->description ?? '';
        $this->fontSourceTab = $this->entry->font_source;
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'entryName' => 'required|string|max:255',
            'entryFontFamily' => 'required|string|max:255',
            'entryFontWeight' => 'required|integer|min:100|max:900',
            'entryFontSize' => 'required|numeric|min:1|max:999',
            'fontUpload' => 'nullable|file|max:10240|mimes:woff2,ttf,otf,woff',
        ];
    }

    public function updatedFontSourceTab($value)
    {
        $this->entryFontSource = $value;
    }

    public function searchGoogleFonts()
    {
        if (strlen($this->googleFontSearch) < 2) {
            $this->googleFontResults = [];
            return;
        }

        // Curated list of popular Google Fonts for quick selection
        $popularFonts = [
            'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins',
            'Inter', 'Raleway', 'Nunito', 'Playfair Display', 'Merriweather',
            'Source Sans Pro', 'Ubuntu', 'Oswald', 'Noto Sans', 'Roboto Slab',
            'PT Sans', 'Rubik', 'Work Sans', 'Quicksand', 'Barlow',
            'Mulish', 'Fira Sans', 'DM Sans', 'Manrope', 'Space Grotesk',
            'Archivo', 'Karla', 'Libre Baskerville', 'Crimson Text', 'Bitter',
            'Josefin Sans', 'Cabin', 'Libre Franklin', 'Exo 2', 'Titillium Web',
            'Hind', 'Overpass', 'Catamaran', 'Oxygen', 'Asap',
            'IBM Plex Sans', 'IBM Plex Serif', 'IBM Plex Mono',
            'JetBrains Mono', 'Fira Code', 'Source Code Pro', 'Inconsolata',
            'PT Serif', 'Lora', 'EB Garamond', 'Cormorant Garamond',
            'Abril Fatface', 'Bebas Neue', 'Dancing Script', 'Pacifico',
        ];

        $search = strtolower($this->googleFontSearch);
        $this->googleFontResults = array_values(array_filter($popularFonts, function ($font) use ($search) {
            return str_contains(strtolower($font), $search);
        }));
    }

    public function selectGoogleFont($fontName)
    {
        $this->entryFontFamily = $fontName;
        $this->entryFontSource = 'google';
        $this->googleFontSearch = '';
        $this->googleFontResults = [];
    }

    public function save()
    {
        $this->validate();

        $board = BrandsTypographyBoard::findOrFail($this->typographyBoardId);
        $this->authorize('update', $board);

        $data = [
            'name' => $this->entryName,
            'role' => $this->entryRole ?: null,
            'font_family' => $this->entryFontFamily,
            'font_source' => $this->entryFontSource,
            'font_weight' => (int) $this->entryFontWeight,
            'font_style' => $this->entryFontStyle,
            'font_size' => (float) $this->entryFontSize,
            'line_height' => $this->entryLineHeight ? (float) $this->entryLineHeight : null,
            'letter_spacing' => $this->entryLetterSpacing !== null && $this->entryLetterSpacing !== '' ? (float) $this->entryLetterSpacing : null,
            'text_transform' => $this->entryTextTransform ?: null,
            'sample_text' => $this->entrySampleText ?: null,
            'description' => $this->entryDescription ?: null,
        ];

        // Handle font upload
        if ($this->fontUpload) {
            $path = $this->fontUpload->store('brands/fonts', 'public');
            $data['font_file_path'] = $path;
            $data['font_file_name'] = $this->fontUpload->getClientOriginalName();
            $data['font_source'] = 'custom';
            $data['font_family'] = pathinfo($this->fontUpload->getClientOriginalName(), PATHINFO_FILENAME);
        }

        if ($this->entry) {
            // Update existing entry - delete old font file if replacing
            if ($this->fontUpload && $this->entry->font_file_path) {
                if (Storage::disk('public')->exists($this->entry->font_file_path)) {
                    Storage::disk('public')->delete($this->entry->font_file_path);
                }
            }
            $this->entry->update($data);
        } else {
            $data['typography_board_id'] = $this->typographyBoardId;
            BrandsTypographyEntry::create($data);
        }

        $this->dispatch('updateTypographyBoard');
        $this->closeModal();
    }

    public function closeModal()
    {
        $this->modalShow = false;
        $this->fontUpload = null;
    }

    public function render()
    {
        return view('brands::livewire.typography-entry-modal')->layout('platform::layouts.app');
    }
}

<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Platform\Brands\Models\BrandsLogoBoard;
use Platform\Brands\Models\BrandsLogoVariant;
use Livewire\Attributes\On;

class LogoBoard extends Component
{
    use WithFileUploads;

    public BrandsLogoBoard $logoBoard;

    public function mount(BrandsLogoBoard $brandsLogoBoard)
    {
        $this->logoBoard = $brandsLogoBoard->fresh()->load('variants');
        $this->authorize('view', $this->logoBoard);
    }

    #[On('updateLogoBoard')]
    public function updateLogoBoard()
    {
        $this->logoBoard->refresh();
        $this->logoBoard->load('variants');
    }

    public function createVariant()
    {
        $this->authorize('update', $this->logoBoard);

        $user = Auth::user();
        $team = $user->currentTeam;

        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        BrandsLogoVariant::create([
            'logo_board_id' => $this->logoBoard->id,
            'name' => 'Neue Logo-Variante',
            'type' => 'primary',
        ]);

        $this->logoBoard->refresh();
        $this->logoBoard->load('variants');
    }

    public function deleteVariant($variantId)
    {
        $this->authorize('update', $this->logoBoard);

        $variant = BrandsLogoVariant::findOrFail($variantId);

        // Logo-Dateien löschen, falls vorhanden
        if ($variant->file_path && Storage::disk('public')->exists($variant->file_path)) {
            Storage::disk('public')->delete($variant->file_path);
        }

        // Zusätzliche Formate löschen
        if ($variant->additional_formats) {
            foreach ($variant->additional_formats as $format) {
                if (isset($format['file_path']) && Storage::disk('public')->exists($format['file_path'])) {
                    Storage::disk('public')->delete($format['file_path']);
                }
            }
        }

        $variant->delete();

        $this->logoBoard->refresh();
        $this->logoBoard->load('variants');
    }

    public function updateVariantOrder($groups)
    {
        $this->authorize('update', $this->logoBoard);

        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                $variant = BrandsLogoVariant::find($item['value']);
                if ($variant) {
                    $variant->order = $item['order'];
                    $variant->save();
                }
            }
        }

        $this->logoBoard->refresh();
        $this->logoBoard->load('variants');
    }

    public function render()
    {
        $user = Auth::user();
        $variants = $this->logoBoard->variants()->orderBy('order')->get();

        return view('brands::livewire.logo-board', [
            'user' => $user,
            'variants' => $variants,
        ])->layout('platform::layouts.app');
    }
}

<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsReferenceBoard;
use Platform\Brands\Models\BrandsReference;
use Livewire\Attributes\On;

class ReferenceBoard extends Component
{
    use \Platform\Brands\Concerns\DispatchesBoardContext;

    public BrandsReferenceBoard $referenceBoard;

    public function mount(BrandsReferenceBoard $brandsReferenceBoard)
    {
        $this->referenceBoard = $brandsReferenceBoard->fresh()->load(['references']);
        $this->authorize('view', $this->referenceBoard);
    }

    #[On('updateReferenceBoard')]
    public function updateReferenceBoard()
    {
        $this->referenceBoard->refresh()->load(['references']);
    }

    public function deleteReference($referenceId)
    {
        $this->authorize('update', $this->referenceBoard);

        $reference = BrandsReference::findOrFail($referenceId);
        if ($reference->reference_board_id === $this->referenceBoard->id) {
            $reference->delete();
        }

        $this->referenceBoard->refresh()->load(['references']);
    }

    public function render()
    {
        $references = $this->referenceBoard->references()->orderBy('order')->get();

        return view('brands::livewire.reference-board', [
            'user' => Auth::user(),
            'liked' => $references->where('verdict', 'like')->values(),
            'disliked' => $references->where('verdict', 'dislike')->values(),
            'neutral' => $references->where('verdict', 'neutral')->values(),
            'total' => $references->count(),
            'aspectLabels' => config('brands.reference_aspects', []),
        ])->layout('platform::layouts.app');
    }
}

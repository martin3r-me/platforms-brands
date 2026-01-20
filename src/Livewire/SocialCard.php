<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsSocialCard;
use Livewire\Attributes\On;

class SocialCard extends Component
{
    public BrandsSocialCard $card;
    public string $title = '';
    public string $bodyMd = '';
    public string $description = '';

    public function mount(BrandsSocialCard $brandsSocialCard)
    {
        $this->card = $brandsSocialCard->fresh()->load('slot', 'socialBoard.brand');
        
        // Berechtigung prüfen
        $this->authorize('view', $this->card);
        
        $this->title = $this->card->title ?? '';
        $this->bodyMd = $this->card->body_md ?? '';
        $this->description = $this->card->description ?? '';
    }

    #[On('updateSocialCard')] 
    public function updateSocialCard()
    {
        $this->card->refresh();
        $this->title = $this->card->title ?? '';
        $this->bodyMd = $this->card->body_md ?? '';
        $this->description = $this->card->description ?? '';

        // Editor sync (wire:ignore)
        $this->dispatch('brands-sync-editor', [
            'cardId' => $this->card->id,
            'title' => $this->title,
            'bodyMd' => $this->bodyMd,
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'bodyMd' => 'nullable|string',
            'description' => 'nullable|string',
        ];
    }

    public function save()
    {
        $this->validate();
        $this->authorize('update', $this->card);
        
        $this->card->update([
            'title' => $this->title,
            'body_md' => $this->bodyMd,
            'description' => $this->description,
        ]);
        
        $this->card->refresh();

        // Editor sync (wire:ignore) + UI can show "saved"
        $this->dispatch('brands-saved', [
            'cardId' => $this->card->id,
            'savedAt' => now()->toIso8601String(),
        ]);
    }

    public function render()
    {
        $user = Auth::user();

        return view('brands::livewire.social-card', [
            'user' => $user,
        ])->layout('platform::layouts.app');
    }
}

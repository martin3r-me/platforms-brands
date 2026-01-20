<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsSocialBoardSlot;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class SocialBoardSlotSettingsModal extends Component
{
    public $modalShow = false;
    public $slot;

    #[On('open-modal-social-board-slot-settings')] 
    public function openModalSocialBoardSlotSettings(...$args)
    {
        // Payload kann als ID kommen oder als Array/Objekt { slotId: X }
        $payload = $args[0] ?? null;
        $id = is_array($payload)
            ? ($payload['slotId'] ?? $payload['id'] ?? null)
            : (is_object($payload) ? ($payload->slotId ?? $payload->id ?? null) : $payload);

        if(!$id){
            return; // kein valides Payload, still ignorieren
        }

        $this->slot = BrandsSocialBoardSlot::findOrFail($id);
        
        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->slot->socialBoard);
        
        $this->modalShow = true;
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'slot.name' => 'required|string|max:255',
        ];
    }

    public function save()
    {
        $this->validate();
        
        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->slot->socialBoard);
        
        $this->slot->save();
        
        $this->dispatch('updateSocialBoard');
        $this->closeModal();
    }

    public function deleteSlot()
    {
        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->slot->socialBoard);
        
        $this->slot->delete();
        
        $this->dispatch('updateSocialBoard');
        $this->closeModal();
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('brands::livewire.social-board-slot-settings-modal')->layout('platform::layouts.app');
    }
}

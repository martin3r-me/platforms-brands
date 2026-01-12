<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsBrand;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Platform\Core\Contracts\CrmCompanyOptionsProviderInterface;
use Platform\Core\Contracts\CrmContactOptionsProviderInterface;
use Platform\Core\Contracts\CrmCompanyResolverInterface;
use Platform\Core\Contracts\CrmContactResolverInterface;
use Platform\Crm\Contracts\CompanyInterface;
use Platform\Crm\Contracts\ContactInterface;

class BrandSettingsModal extends Component
{
    public $modalShow = false;
    public $brand;
    public $selectedCompanyId = null;
    public $selectedContactId = null;

    #[On('open-modal-brand-settings')] 
    public function openModalBrandSettings($brandId)
    {
        $this->brand = BrandsBrand::with(['companyLinks.company', 'crmContactLinks.contact'])->findOrFail($brandId);
        
        // Policy-Berechtigung prüfen - Settings erfordert view-Rechte
        $this->authorize('settings', $this->brand);
        
        // Aktuelle Verknüpfungen laden
        $this->selectedCompanyId = $this->brand->getCompany()?->id;
        $this->selectedContactId = $this->brand->getContact()?->id;
        
        $this->modalShow = true;
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'brand.name' => 'required|string|max:255',
            'brand.description' => 'nullable|string',
            'selectedCompanyId' => 'nullable|integer|exists:crm_companies,id',
            'selectedContactId' => 'nullable|integer|exists:crm_contacts,id',
        ];
    }

    public function getCompanyOptionsProperty()
    {
        /** @var CrmCompanyOptionsProviderInterface $provider */
        $provider = app(CrmCompanyOptionsProviderInterface::class);
        $options = $provider->options(null, 50);
        return collect($options);
    }

    public function getContactOptionsProperty()
    {
        /** @var CrmContactOptionsProviderInterface $provider */
        $provider = app(CrmContactOptionsProviderInterface::class);
        $options = $provider->options(null, 50);
        return collect($options);
    }

    public function save()
    {
        $this->validate();
        
        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->brand);

        $this->brand->save();
        
        // Company-Verknüpfung aktualisieren über Links-Tabelle (loose coupling)
        if ($this->selectedCompanyId) {
            // Alte Links entfernen
            $this->brand->detachAllCompanies();
            // Neue Company über Links-Tabelle verknüpfen (über HasCompanyLinksTrait)
            // Prüfen ob Company existiert über Resolver
            $companyResolver = app(CrmCompanyResolverInterface::class);
            $companyName = $companyResolver->displayName($this->selectedCompanyId);
            if ($companyName) {
                // Company über Links-Tabelle verknüpfen
                $this->brand->companyLinks()->create([
                    'company_id' => $this->selectedCompanyId,
                    'team_id' => Auth::user()->currentTeam->id,
                    'created_by_user_id' => Auth::id(),
                ]);
            }
        } else {
            // Alle Company-Links entfernen
            $this->brand->detachAllCompanies();
        }
        
        // Contact-Verknüpfung aktualisieren über Links-Tabelle (loose coupling)
        if ($this->selectedContactId) {
            // Alte Links entfernen
            $this->brand->crmContactLinks()->delete();
            // Neuen Contact über Links-Tabelle verknüpfen
            // Prüfen ob Contact existiert über Resolver
            $contactResolver = app(CrmContactResolverInterface::class);
            $contactName = $contactResolver->displayName($this->selectedContactId);
            if ($contactName) {
                // Contact über Links-Tabelle verknüpfen (über HasEmployeeContact Trait)
                $this->brand->crmContactLinks()->create([
                    'contact_id' => $this->selectedContactId,
                    'linkable_id' => $this->brand->id,
                    'linkable_type' => get_class($this->brand),
                    'team_id' => Auth::user()->currentTeam->id,
                    'created_by_user_id' => Auth::id(),
                ]);
            }
        } else {
            // Alle Contact-Links entfernen
            $this->brand->crmContactLinks()->delete();
        }
        
        $this->brand->refresh();
        
        $this->dispatch('updateSidebar');
        $this->dispatch('updateBrand');
        $this->dispatch('updateDashboard');

        $this->dispatch('notifications:store', [
            'title' => 'Marke gespeichert',
            'message' => 'Die Marke wurde erfolgreich aktualisiert.',
            'notice_type' => 'success',
            'noticable_type' => get_class($this->brand),
            'noticable_id'   => $this->brand->getKey(),
        ]);

        $this->reset('brand', 'selectedCompanyId', 'selectedContactId');
        $this->closeModal();
    }

    public function markAsDone()
    {
        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->brand);
        
        $this->brand->done = true;
        $this->brand->done_at = now();
        $this->brand->save();
        
        $this->dispatch('updateSidebar');
        $this->dispatch('updateBrand');
        $this->dispatch('updateDashboard');
        
        $this->dispatch('notifications:store', [
            'title' => 'Marke abgeschlossen',
            'message' => 'Die Marke wurde erfolgreich als abgeschlossen markiert.',
            'notice_type' => 'success',
            'noticable_type' => get_class($this->brand),
            'noticable_id'   => $this->brand->getKey(),
        ]);
        
        $this->brand->refresh();
    }

    public function deleteBrand()
    {
        // Policy-Berechtigung prüfen
        $this->authorize('delete', $this->brand);
        
        $this->brand->delete();
        // Nach Brands-Dashboard leiten
        $this->redirect(route('brands.dashboard'), navigate: true);
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('brands::livewire.brand-settings-modal')->layout('platform::layouts.app');
    }
}

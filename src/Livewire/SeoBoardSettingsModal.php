<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsSeoBoard;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class SeoBoardSettingsModal extends Component
{
    public $modalShow = false;
    public $seoBoard;

    // DataForSEO Config
    public $configLocationCode = '';
    public $configLanguageName = '';
    public $configConnectionId = '';

    // Refresh
    public $refreshIntervalDays = null;

    // Budget
    public $budgetLimitEuro = '';

    #[On('open-modal-seo-board-settings')]
    public function openModalSeoBoardSettings($seoBoardId)
    {
        $this->seoBoard = BrandsSeoBoard::findOrFail($seoBoardId);

        $this->authorize('update', $this->seoBoard);

        // DataForSEO Config laden
        $config = $this->seoBoard->dataforseo_config ?? [];
        $this->configLocationCode = $config['location_code'] ?? '';
        $this->configLanguageName = $config['language_name'] ?? '';
        $this->configConnectionId = $config['connection_id'] ?? '';

        // Refresh Intervall
        $this->refreshIntervalDays = $this->seoBoard->refresh_interval_days;

        // Budget
        $this->budgetLimitEuro = $this->seoBoard->budget_limit_cents !== null
            ? number_format($this->seoBoard->budget_limit_cents / 100, 2, '.', '')
            : '';

        $this->modalShow = true;
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'seoBoard.name' => 'required|string|max:255',
            'seoBoard.description' => 'nullable|string',
            'configLocationCode' => 'nullable|integer',
            'configLanguageName' => 'nullable|string|max:50',
            'configConnectionId' => 'nullable|integer',
            'refreshIntervalDays' => 'nullable|integer|min:1|max:365',
            'budgetLimitEuro' => 'nullable|numeric|min:0|max:99999',
        ];
    }

    public function getRefreshIntervalOptionsProperty(): array
    {
        return [
            ['value' => 7, 'label' => 'Wöchentlich (7 Tage)'],
            ['value' => 14, 'label' => 'Alle 2 Wochen'],
            ['value' => 30, 'label' => 'Monatlich (30 Tage)'],
            ['value' => 60, 'label' => 'Alle 2 Monate'],
            ['value' => 90, 'label' => 'Quartalsweise (90 Tage)'],
        ];
    }

    public function save()
    {
        $this->validate();

        $this->authorize('update', $this->seoBoard);

        // DataForSEO Config zusammenbauen
        $config = $this->seoBoard->dataforseo_config ?? [];

        if ($this->configLocationCode !== '' && $this->configLocationCode !== null) {
            $config['location_code'] = (int) $this->configLocationCode;
        } else {
            unset($config['location_code']);
        }

        if ($this->configLanguageName !== '' && $this->configLanguageName !== null) {
            $config['language_name'] = $this->configLanguageName;
        } else {
            unset($config['language_name']);
        }

        if ($this->configConnectionId !== '' && $this->configConnectionId !== null) {
            $config['connection_id'] = (int) $this->configConnectionId;
        } else {
            unset($config['connection_id']);
        }

        $this->seoBoard->dataforseo_config = !empty($config) ? $config : null;

        // Refresh Intervall
        $this->seoBoard->refresh_interval_days = $this->refreshIntervalDays ?: null;

        // Budget
        $this->seoBoard->budget_limit_cents = ($this->budgetLimitEuro !== '' && $this->budgetLimitEuro !== null)
            ? (int) round((float) $this->budgetLimitEuro * 100)
            : null;

        $this->seoBoard->save();
        $this->seoBoard->refresh();

        $this->dispatch('updateSidebar');
        $this->dispatch('updateSeoBoard');

        $this->dispatch('notifications:store', [
            'title' => 'SEO Board gespeichert',
            'message' => 'Die Einstellungen wurden erfolgreich aktualisiert.',
            'notice_type' => 'success',
            'noticable_type' => get_class($this->seoBoard),
            'noticable_id' => $this->seoBoard->getKey(),
        ]);

        $this->closeModal();
    }

    public function resetBudget()
    {
        $this->authorize('update', $this->seoBoard);

        $this->seoBoard->update([
            'budget_spent_cents' => 0,
            'budget_reset_at' => now(),
        ]);

        $this->dispatch('notifications:store', [
            'title' => 'Budget zurückgesetzt',
            'message' => 'Das API-Budget wurde auf 0 zurückgesetzt.',
            'notice_type' => 'success',
        ]);

        $this->dispatch('updateSeoBoard');
    }

    public function deleteSeoBoard()
    {
        $this->authorize('delete', $this->seoBoard);

        $brand = $this->seoBoard->brand;
        $this->seoBoard->delete();

        $this->redirect(route('brands.brands.show', $brand), navigate: true);
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('brands::livewire.seo-board-settings-modal', [
            'refreshIntervalOptions' => $this->refreshIntervalOptions,
        ])->layout('platform::layouts.app');
    }
}

<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsCtaBoard;
use Platform\Brands\Models\BrandsCta;
use Livewire\Attributes\On;

class CtaBoard extends Component
{
    public BrandsCtaBoard $ctaBoard;

    public string $groupBy = 'target_page';
    public string $filterType = '';
    public string $filterFunnelStage = '';
    public string $filterIsActive = '';

    // Inline-Editing
    public ?int $editingCtaId = null;
    public string $editingLabel = '';
    public string $editingDescription = '';

    public function mount(BrandsCtaBoard $brandsCtaBoard)
    {
        $this->ctaBoard = $brandsCtaBoard->fresh()->load('ctas.targetPage');

        $this->authorize('view', $this->ctaBoard);
    }

    #[On('updateCtaBoard')]
    public function updateCtaBoard()
    {
        $this->ctaBoard->refresh();
        $this->ctaBoard->load('ctas.targetPage');
    }

    public function setGroupBy(string $groupBy)
    {
        if (in_array($groupBy, ['target_page', 'funnel_stage'])) {
            $this->groupBy = $groupBy;
        }
    }

    public function startEditing(int $ctaId)
    {
        $cta = BrandsCta::find($ctaId);
        if (!$cta || $cta->cta_board_id !== $this->ctaBoard->id) {
            return;
        }

        $this->authorize('update', $this->ctaBoard);

        $this->editingCtaId = $ctaId;
        $this->editingLabel = $cta->label;
        $this->editingDescription = $cta->description ?? '';
    }

    public function saveEditing()
    {
        if (!$this->editingCtaId) {
            return;
        }

        $this->authorize('update', $this->ctaBoard);

        $cta = BrandsCta::find($this->editingCtaId);
        if (!$cta || $cta->cta_board_id !== $this->ctaBoard->id) {
            return;
        }

        $this->validate([
            'editingLabel' => 'required|string|max:255',
            'editingDescription' => 'nullable|string',
        ]);

        $cta->update([
            'label' => $this->editingLabel,
            'description' => $this->editingDescription,
        ]);

        $this->cancelEditing();
        $this->updateCtaBoard();

        $this->dispatch('notifications:store', [
            'title' => 'CTA aktualisiert',
            'message' => "CTA '{$cta->label}' wurde gespeichert.",
            'notice_type' => 'success',
            'noticable_type' => get_class($cta),
            'noticable_id' => $cta->getKey(),
        ]);
    }

    public function cancelEditing()
    {
        $this->editingCtaId = null;
        $this->editingLabel = '';
        $this->editingDescription = '';
    }

    protected function getFilteredCtas()
    {
        $query = $this->ctaBoard->ctas()->with('targetPage');

        if ($this->filterType !== '') {
            $query->where('type', $this->filterType);
        }

        if ($this->filterFunnelStage !== '') {
            $query->where('funnel_stage', $this->filterFunnelStage);
        }

        if ($this->filterIsActive !== '') {
            $query->where('is_active', $this->filterIsActive === '1');
        }

        return $query->orderBy('order')->get();
    }

    public function render()
    {
        $user = Auth::user();
        $ctas = $this->getFilteredCtas();

        if ($this->groupBy === 'funnel_stage') {
            $groups = $ctas->groupBy('funnel_stage');
            // Ensure all funnel stages are present
            $orderedGroups = collect();
            foreach (BrandsCta::FUNNEL_STAGES as $stage) {
                $orderedGroups[$stage] = $groups->get($stage, collect());
            }
            $grouped = $orderedGroups;
        } else {
            // Group by target_page
            $grouped = $ctas->groupBy(function ($cta) {
                if ($cta->target_page_id && $cta->targetPage) {
                    return 'page_' . $cta->target_page_id;
                }
                if ($cta->target_url) {
                    return 'url_' . $cta->target_url;
                }
                return 'no_target';
            });
        }

        return view('brands::livewire.cta-board', [
            'user' => $user,
            'ctas' => $ctas,
            'grouped' => $grouped,
        ])->layout('platform::layouts.app');
    }
}

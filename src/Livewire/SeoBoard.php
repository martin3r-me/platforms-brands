<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsSeoBoard;
use Platform\Brands\Services\SeoBudgetGuardService;
use Livewire\Attributes\On;

class SeoBoard extends Component
{
    public BrandsSeoBoard $seoBoard;

    public function mount(BrandsSeoBoard $brandsSeoBoard)
    {
        $this->seoBoard = $brandsSeoBoard->fresh()->load('keywordClusters.keywords');

        $this->authorize('view', $this->seoBoard);
    }

    #[On('updateSeoBoard')]
    public function updateSeoBoard()
    {
        $this->seoBoard->refresh();
        $this->seoBoard->load('keywordClusters.keywords');
    }

    public function render()
    {
        $user = Auth::user();

        $clusters = $this->seoBoard->keywordClusters()
            ->with(['keywords'])
            ->orderBy('order')
            ->get();

        // Unzugeordnete Keywords (ohne Cluster)
        $unclusteredKeywords = $this->seoBoard->keywords()
            ->whereNull('keyword_cluster_id')
            ->orderBy('order')
            ->get();

        // Budget-Summary
        $budgetGuard = app(SeoBudgetGuardService::class);
        $budgetSummary = $budgetGuard->getBudgetSummary($this->seoBoard);

        return view('brands::livewire.seo-board', [
            'user' => $user,
            'clusters' => $clusters,
            'unclusteredKeywords' => $unclusteredKeywords,
            'budgetSummary' => $budgetSummary,
        ])->layout('platform::layouts.app');
    }
}

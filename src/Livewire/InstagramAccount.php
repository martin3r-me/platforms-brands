<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\InstagramAccount;
use Livewire\Attributes\On;

class InstagramAccount extends Component
{
    public InstagramAccount $instagramAccount;

    public function mount(InstagramAccount $instagramAccount)
    {
        $this->instagramAccount = $instagramAccount->fresh([
            'facebookPage',
            'media' => function ($query) {
                $query->with(['contextFiles', 'latestInsight', 'hashtags'])->orderBy('timestamp', 'desc');
            },
            'latestInsight',
        ]);
        
        // Berechtigung prüfen
        $this->authorize('view', $this->instagramAccount);
    }

    #[On('updateInstagramAccount')] 
    public function updateInstagramAccount()
    {
        $this->instagramAccount->refresh();
    }

    public function render()
    {
        $user = Auth::user();
        
        // Media mit allen Beziehungen laden
        $media = $this->instagramAccount->media()
            ->with(['contextFiles', 'latestInsight', 'hashtags'])
            ->orderBy('timestamp', 'desc')
            ->get();
        
        // Top Hashtags laden
        $topHashtags = $this->instagramAccount->media()
            ->with('hashtags')
            ->get()
            ->pluck('hashtags')
            ->flatten()
            ->groupBy('id')
            ->map(function ($hashtags) {
                $first = $hashtags->first();
                return [
                    'id' => $first->id,
                    'name' => $first->name,
                    'usage_count' => $hashtags->sum('pivot.count'),
                ];
            })
            ->sortByDesc('usage_count')
            ->take(20)
            ->values();

        return view('brands::livewire.instagram-account', [
            'user' => $user,
            'media' => $media,
            'topHashtags' => $topHashtags,
            'latestInsights' => $this->instagramAccount->latestInsight,
        ])->layout('platform::layouts.app');
    }
}

<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Integrations\Models\IntegrationsInstagramAccount;
use Livewire\Attributes\On;

class InstagramAccount extends Component
{
    public IntegrationsInstagramAccount $instagramAccount;

    public function mount(IntegrationsInstagramAccount $instagramAccount)
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

    /**
     * Instagram Media synchronisieren
     */
    public function syncMedia()
    {
        $this->authorize('update', $this->instagramAccount);
        
        try {
            $user = Auth::user();
            $metaConnection = $this->instagramAccount->integrationConnection;
            
            if (!$metaConnection) {
                session()->flash('error', 'Keine Meta-Connection gefunden.');
                return;
            }
            
            if ($metaConnection->status !== 'active') {
                session()->flash('error', 'Meta-Connection ist nicht aktiv.');
                return;
            }
            
            $service = app(\Platform\Brands\Services\InstagramMediaService::class);
            $result = $service->syncMedia($this->instagramAccount, 1000);
            
            $count = count($result);
            session()->flash('success', "✅ {$count} Instagram Media-Item(s) synchronisiert.");
            
            // Refresh, damit neue Media angezeigt werden
            $this->instagramAccount->refresh();
        } catch (\Exception $e) {
            \Log::error('Instagram Media Sync Error', [
                'user_id' => auth()->id(),
                'instagram_account_id' => $this->instagramAccount->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Fehler beim Synchronisieren: ' . $e->getMessage());
        }
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

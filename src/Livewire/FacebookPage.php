<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Integrations\Models\IntegrationsFacebookPage;
use Livewire\Attributes\On;

class FacebookPage extends Component
{
    public IntegrationsFacebookPage $facebookPage;

    public function mount(IntegrationsFacebookPage $facebookPage)
    {
        $this->facebookPage = $facebookPage->fresh([
            'posts' => function ($query) {
                $query->with('contextFiles')->orderBy('published_at', 'desc');
            },
            'instagramAccounts',
        ]);
        
        // Berechtigung prüfen
        $this->authorize('view', $this->facebookPage);
    }

    #[On('updateFacebookPage')] 
    public function updateFacebookPage()
    {
        $this->facebookPage->refresh();
    }

    /**
     * Facebook Page Posts synchronisieren
     */
    public function syncPosts()
    {
        $this->authorize('update', $this->facebookPage);
        
        try {
            $user = Auth::user();
            $metaConnection = $this->facebookPage->integrationConnection;
            
            if (!$metaConnection) {
                session()->flash('error', 'Keine Meta-Connection gefunden.');
                return;
            }
            
            if ($metaConnection->status !== 'active') {
                session()->flash('error', 'Meta-Connection ist nicht aktiv.');
                return;
            }
            
            $service = app(\Platform\Brands\Services\FacebookPageService::class);
            $result = $service->syncFacebookPosts($this->facebookPage);
            
            $count = count($result);
            session()->flash('success', "✅ {$count} Facebook Post(s) synchronisiert.");
            
            // Refresh, damit neue Posts angezeigt werden
            $this->facebookPage->refresh();
        } catch (\Exception $e) {
            \Log::error('Facebook Posts Sync Error', [
                'user_id' => auth()->id(),
                'facebook_page_id' => $this->facebookPage->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Fehler beim Synchronisieren: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $user = Auth::user();
        
        // Posts mit ContextFiles laden
        $posts = $this->facebookPage->posts()
            ->with('contextFiles')
            ->orderBy('published_at', 'desc')
            ->get();

        return view('brands::livewire.facebook-page', [
            'user' => $user,
            'posts' => $posts,
        ])->layout('platform::layouts.app');
    }
}

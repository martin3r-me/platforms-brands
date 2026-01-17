<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\FacebookPage;
use Livewire\Attributes\On;

class FacebookPage extends Component
{
    public FacebookPage $facebookPage;

    public function mount(FacebookPage $facebookPage)
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

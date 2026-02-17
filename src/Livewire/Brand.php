<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsBrand;
use Livewire\Attributes\On;

class Brand extends Component
{
    public BrandsBrand $brand;

    public function mount(BrandsBrand $brandsBrand)
    {
        $this->brand = $brandsBrand;
        
        // Berechtigung prüfen
        $this->authorize('view', $this->brand);
    }

    #[On('updateBrand')] 
    public function updateBrand()
    {
        $this->brand->refresh();
    }

    public function createCiBoard()
    {
        $this->authorize('update', $this->brand);
        
        $user = Auth::user();
        $team = $user->currentTeam;
        
        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $ciBoard = \Platform\Brands\Models\BrandsCiBoard::create([
            'name' => 'Neues CI Board',
            'description' => null,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'brand_id' => $this->brand->id,
        ]);

        $this->brand->refresh();
        
        return $this->redirect(route('brands.ci-boards.show', $ciBoard), navigate: true);
    }

    public function createContentBoard()
    {
        $this->authorize('update', $this->brand);
        
        $user = Auth::user();
        $team = $user->currentTeam;
        
        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $contentBoard = \Platform\Brands\Models\BrandsContentBoard::create([
            'name' => 'Neues Content Board',
            'description' => null,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'brand_id' => $this->brand->id,
        ]);

        $this->brand->refresh();
        
        return $this->redirect(route('brands.content-boards.show', $contentBoard), navigate: true);
    }

    public function createSocialBoard()
    {
        $this->authorize('update', $this->brand);
        
        $user = Auth::user();
        $team = $user->currentTeam;
        
        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $socialBoard = \Platform\Brands\Models\BrandsSocialBoard::create([
            'name' => 'Neues Social Board',
            'description' => null,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'brand_id' => $this->brand->id,
        ]);

        $this->brand->refresh();
        
        return $this->redirect(route('brands.social-boards.show', $socialBoard), navigate: true);
    }

    public function createKanbanBoard()
    {
        $this->authorize('update', $this->brand);

        $user = Auth::user();
        $team = $user->currentTeam;

        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $kanbanBoard = \Platform\Brands\Models\BrandsKanbanBoard::create([
            'name' => 'Neues Kanban Board',
            'description' => null,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'brand_id' => $this->brand->id,
        ]);

        $this->brand->refresh();

        return $this->redirect(route('brands.kanban-boards.show', $kanbanBoard), navigate: true);
    }

    public function createMultiContentBoard()
    {
        $this->authorize('update', $this->brand);
        
        $user = Auth::user();
        $team = $user->currentTeam;
        
        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $multiContentBoard = \Platform\Brands\Models\BrandsMultiContentBoard::create([
            'name' => 'Neues Multi-Content-Board',
            'description' => null,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'brand_id' => $this->brand->id,
        ]);

        $this->brand->refresh();
        
        return $this->redirect(route('brands.multi-content-boards.show', $multiContentBoard), navigate: true);
    }

    public function createTypographyBoard()
    {
        $this->authorize('update', $this->brand);

        $user = Auth::user();
        $team = $user->currentTeam;

        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $typographyBoard = \Platform\Brands\Models\BrandsTypographyBoard::create([
            'name' => 'Neues Typografie Board',
            'description' => null,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'brand_id' => $this->brand->id,
        ]);

        $this->brand->refresh();

        return $this->redirect(route('brands.typography-boards.show', $typographyBoard), navigate: true);
    }

    public function createLogoBoard()
    {
        $this->authorize('update', $this->brand);

        $user = Auth::user();
        $team = $user->currentTeam;

        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $logoBoard = \Platform\Brands\Models\BrandsLogoBoard::create([
            'name' => 'Neues Logo Board',
            'description' => null,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'brand_id' => $this->brand->id,
        ]);

        $this->brand->refresh();

        return $this->redirect(route('brands.logo-boards.show', $logoBoard), navigate: true);
    }

    public function createToneOfVoiceBoard()
    {
        $this->authorize('update', $this->brand);

        $user = Auth::user();
        $team = $user->currentTeam;

        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $toneOfVoiceBoard = \Platform\Brands\Models\BrandsToneOfVoiceBoard::create([
            'name' => 'Neues Tone of Voice Board',
            'description' => null,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'brand_id' => $this->brand->id,
        ]);

        $this->brand->refresh();

        return $this->redirect(route('brands.tone-of-voice-boards.show', $toneOfVoiceBoard), navigate: true);
    }

    public function createPersonaBoard()
    {
        $this->authorize('update', $this->brand);

        $user = Auth::user();
        $team = $user->currentTeam;

        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $personaBoard = \Platform\Brands\Models\BrandsPersonaBoard::create([
            'name' => 'Neues Persona Board',
            'description' => null,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'brand_id' => $this->brand->id,
        ]);

        $this->brand->refresh();

        return $this->redirect(route('brands.persona-boards.show', $personaBoard), navigate: true);
    }

    public function createCompetitorBoard()
    {
        $this->authorize('update', $this->brand);

        $user = Auth::user();
        $team = $user->currentTeam;

        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $competitorBoard = \Platform\Brands\Models\BrandsCompetitorBoard::create([
            'name' => 'Neues Wettbewerber Board',
            'description' => null,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'brand_id' => $this->brand->id,
        ]);

        $this->brand->refresh();

        return $this->redirect(route('brands.competitor-boards.show', $competitorBoard), navigate: true);
    }

    public function createGuidelineBoard()
    {
        $this->authorize('update', $this->brand);

        $user = Auth::user();
        $team = $user->currentTeam;

        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $guidelineBoard = \Platform\Brands\Models\BrandsGuidelineBoard::create([
            'name' => 'Neues Guidelines Board',
            'description' => null,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'brand_id' => $this->brand->id,
        ]);

        $this->brand->refresh();

        return $this->redirect(route('brands.guideline-boards.show', $guidelineBoard), navigate: true);
    }

    public function createMoodboardBoard()
    {
        $this->authorize('update', $this->brand);

        $user = Auth::user();
        $team = $user->currentTeam;

        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $moodboardBoard = \Platform\Brands\Models\BrandsMoodboardBoard::create([
            'name' => 'Neues Moodboard',
            'description' => null,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'brand_id' => $this->brand->id,
        ]);

        $this->brand->refresh();

        return $this->redirect(route('brands.moodboard-boards.show', $moodboardBoard), navigate: true);
    }

    public function createAssetBoard()
    {
        $this->authorize('update', $this->brand);

        $user = Auth::user();
        $team = $user->currentTeam;

        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $assetBoard = \Platform\Brands\Models\BrandsAssetBoard::create([
            'name' => 'Neues Asset Board',
            'description' => null,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'brand_id' => $this->brand->id,
        ]);

        $this->brand->refresh();

        return $this->redirect(route('brands.asset-boards.show', $assetBoard), navigate: true);
    }

    public function rendered()
    {
        $this->dispatch('comms', [
            'model' => get_class($this->brand),
            'modelId' => $this->brand->id,
            'subject' => $this->brand->name,
            'description' => $this->brand->description ?? '',
            'url' => route('brands.brands.show', $this->brand),
            'source' => 'brands.brand.view',
            'recipients' => [],
            'capabilities' => [
                'manage_channels' => true,
                'threads' => false,
            ],
            'meta' => [
                'created_at' => $this->brand->created_at,
            ],
        ]);

        // Organization-Kontext setzen - beides erlauben: Zeiten + Entity-Verknüpfung + Dimensionen
        $this->dispatch('organization', [
            'context_type' => get_class($this->brand),
            'context_id' => $this->brand->id,
            'allow_time_entry' => true,
            'allow_entities' => true,
            'allow_dimensions' => true,
        ]);

        // KeyResult-Kontext setzen - ermöglicht Verknüpfung von KeyResults mit dieser Marke
        $this->dispatch('keyresult', [
            'context_type' => get_class($this->brand),
            'context_id' => $this->brand->id,
        ]);
    }


    /**
     * Facebook Page mit Brand verknüpfen
     */
    public function attachFacebookPage($facebookPageId)
    {
        $this->authorize('update', $this->brand);
        
        $facebookPage = \Platform\Integrations\Models\IntegrationsFacebookPage::findOrFail($facebookPageId);
        $service = app(\Platform\Integrations\Services\IntegrationAccountLinkService::class);
        
        if ($service->linkFacebookPage($facebookPage, $this->brand)) {
            $this->brand->refresh();
            session()->flash('success', 'Facebook Page wurde erfolgreich mit der Marke verknüpft.');
        } else {
            session()->flash('error', 'Facebook Page konnte nicht verknüpft werden.');
        }
    }

    /**
     * Facebook Page von Brand trennen
     */
    public function detachFacebookPage($facebookPageId)
    {
        $this->authorize('update', $this->brand);
        
        $facebookPage = \Platform\Integrations\Models\IntegrationsFacebookPage::findOrFail($facebookPageId);
        $service = app(\Platform\Integrations\Services\IntegrationAccountLinkService::class);
        
        if ($service->unlinkFacebookPage($facebookPage, $this->brand)) {
            $this->brand->refresh();
            session()->flash('success', 'Facebook Page wurde erfolgreich von der Marke getrennt.');
        } else {
            session()->flash('error', 'Facebook Page konnte nicht getrennt werden.');
        }
    }

    /**
     * Instagram Account mit Brand verknüpfen
     */
    public function attachInstagramAccount($instagramAccountId)
    {
        $this->authorize('update', $this->brand);
        
        $instagramAccount = \Platform\Integrations\Models\IntegrationsInstagramAccount::findOrFail($instagramAccountId);
        $service = app(\Platform\Integrations\Services\IntegrationAccountLinkService::class);
        
        if ($service->linkInstagramAccount($instagramAccount, $this->brand)) {
            $this->brand->refresh();
            session()->flash('success', 'Instagram Account wurde erfolgreich mit der Marke verknüpft.');
        } else {
            session()->flash('error', 'Instagram Account konnte nicht verknüpft werden.');
        }
    }

    /**
     * Instagram Account von Brand trennen
     */
    public function detachInstagramAccount($instagramAccountId)
    {
        $this->authorize('update', $this->brand);
        
        $instagramAccount = \Platform\Integrations\Models\IntegrationsInstagramAccount::findOrFail($instagramAccountId);
        $service = app(\Platform\Integrations\Services\IntegrationAccountLinkService::class);
        
        if ($service->unlinkInstagramAccount($instagramAccount, $this->brand)) {
            $this->brand->refresh();
            session()->flash('success', 'Instagram Account wurde erfolgreich von der Marke getrennt.');
        } else {
            session()->flash('error', 'Instagram Account konnte nicht getrennt werden.');
        }
    }

    /**
     * Facebook Pages synchronisieren
     */
    public function syncFacebookPages()
    {
        $this->authorize('update', $this->brand);
        
        try {
            $user = Auth::user();
            $metaConnection = $this->brand->metaConnection();
            
            if (!$metaConnection) {
                session()->flash('error', 'Keine Meta-Connection gefunden. Bitte zuerst mit Meta verbinden.');
                return;
            }
            
            if ($metaConnection->status !== 'active') {
                session()->flash('error', 'Meta-Connection ist nicht aktiv.');
                return;
            }
            
            $service = app(\Platform\Integrations\Services\IntegrationsFacebookPageService::class);
            $result = $service->syncFacebookPagesForUser($metaConnection);
            
            $count = count($result);
            session()->flash('success', "✅ {$count} Facebook Page(s) synchronisiert.");
            
            // Refresh, damit neue Pages angezeigt werden
            $this->brand->refresh();
        } catch (\Exception $e) {
            \Log::error('Facebook Pages Sync Error', [
                'user_id' => auth()->id(),
                'brand_id' => $this->brand->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Fehler beim Synchronisieren: ' . $e->getMessage());
        }
    }

    /**
     * Instagram Accounts synchronisieren
     */
    public function syncInstagramAccounts()
    {
        $this->authorize('update', $this->brand);
        
        try {
            $user = Auth::user();
            $metaConnection = $this->brand->metaConnection();
            
            if (!$metaConnection) {
                session()->flash('error', 'Keine Meta-Connection gefunden. Bitte zuerst mit Meta verbinden.');
                return;
            }
            
            if ($metaConnection->status !== 'active') {
                session()->flash('error', 'Meta-Connection ist nicht aktiv.');
                return;
            }
            
            $service = app(\Platform\Integrations\Services\IntegrationsInstagramAccountService::class);
            $result = $service->syncInstagramAccountsForUser($metaConnection);
            
            $count = count($result);
            session()->flash('success', "✅ {$count} Instagram Account(s) synchronisiert.");
            
            // Refresh, damit neue Accounts angezeigt werden
            $this->brand->refresh();
        } catch (\Exception $e) {
            \Log::error('Instagram Accounts Sync Error', [
                'user_id' => auth()->id(),
                'brand_id' => $this->brand->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Fehler beim Synchronisieren: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $user = Auth::user();
        $team = $user->currentTeam;

        // Alle Board-Typen laden mit Entry-Counts
        $ciBoards = $this->brand->ciBoards;
        $contentBoards = $this->brand->contentBoards;
        $socialBoards = $this->brand->socialBoards;
        $kanbanBoards = $this->brand->kanbanBoards;
        $multiContentBoards = $this->brand->multiContentBoards;
        $typographyBoards = $this->brand->typographyBoards;
        $logoBoards = $this->brand->logoBoards;
        $toneOfVoiceBoards = $this->brand->toneOfVoiceBoards;
        $personaBoards = $this->brand->personaBoards;
        $competitorBoards = $this->brand->competitorBoards;
        $guidelineBoards = $this->brand->guidelineBoards;
        $moodboardBoards = $this->brand->moodboardBoards;
        $assetBoards = $this->brand->assetBoards;

        // Board-Gruppen für tabellarische Darstellung
        $boardGroups = collect([
            [
                'key' => 'ci',
                'label' => 'CI Boards',
                'icon' => 'heroicon-o-paint-brush',
                'color' => 'amber',
                'boards' => $ciBoards,
                'routePrefix' => 'brands.ci-boards.show',
                'boardType' => 'ci-board',
                'entryRelation' => 'colors',
                'entryLabel' => 'Farben',
            ],
            [
                'key' => 'content',
                'label' => 'Content Boards',
                'icon' => 'heroicon-o-document-text',
                'color' => 'blue',
                'boards' => $contentBoards,
                'routePrefix' => 'brands.content-boards.show',
                'boardType' => 'content-board',
                'entryRelation' => 'blocks',
                'entryLabel' => 'Blöcke',
            ],
            [
                'key' => 'social',
                'label' => 'Social Boards',
                'icon' => 'heroicon-o-share',
                'color' => 'purple',
                'boards' => $socialBoards,
                'routePrefix' => 'brands.social-boards.show',
                'boardType' => 'social-board',
                'entryRelation' => 'cards',
                'entryLabel' => 'Cards',
            ],
            [
                'key' => 'kanban',
                'label' => 'Kanban Boards',
                'icon' => 'heroicon-o-view-columns',
                'color' => 'indigo',
                'boards' => $kanbanBoards,
                'routePrefix' => 'brands.kanban-boards.show',
                'boardType' => 'kanban-board',
                'entryRelation' => 'cards',
                'entryLabel' => 'Cards',
            ],
            [
                'key' => 'multi-content',
                'label' => 'Multi-Content-Boards',
                'icon' => 'heroicon-o-squares-2x2',
                'color' => 'green',
                'boards' => $multiContentBoards,
                'routePrefix' => 'brands.multi-content-boards.show',
                'boardType' => 'multi-content-board',
                'entryRelation' => 'slots',
                'entryLabel' => 'Slots',
            ],
            [
                'key' => 'typography',
                'label' => 'Typografie Boards',
                'icon' => 'heroicon-o-language',
                'color' => 'rose',
                'boards' => $typographyBoards,
                'routePrefix' => 'brands.typography-boards.show',
                'boardType' => 'typography-board',
                'entryRelation' => 'entries',
                'entryLabel' => 'Einträge',
            ],
            [
                'key' => 'logo',
                'label' => 'Logo Boards',
                'icon' => 'heroicon-o-photo',
                'color' => 'emerald',
                'boards' => $logoBoards,
                'routePrefix' => 'brands.logo-boards.show',
                'boardType' => 'logo-board',
                'entryRelation' => 'variants',
                'entryLabel' => 'Varianten',
            ],
            [
                'key' => 'tone-of-voice',
                'label' => 'Tone of Voice Boards',
                'icon' => 'heroicon-o-megaphone',
                'color' => 'violet',
                'boards' => $toneOfVoiceBoards,
                'routePrefix' => 'brands.tone-of-voice-boards.show',
                'boardType' => 'tone-of-voice-board',
                'entryRelation' => 'entries',
                'entryLabel' => 'Einträge',
            ],
            [
                'key' => 'persona',
                'label' => 'Persona Boards',
                'icon' => 'heroicon-o-user-group',
                'color' => 'teal',
                'boards' => $personaBoards,
                'routePrefix' => 'brands.persona-boards.show',
                'boardType' => 'persona-board',
                'entryRelation' => 'personas',
                'entryLabel' => 'Personas',
            ],
            [
                'key' => 'competitor',
                'label' => 'Wettbewerber Boards',
                'icon' => 'heroicon-o-scale',
                'color' => 'orange',
                'boards' => $competitorBoards,
                'routePrefix' => 'brands.competitor-boards.show',
                'boardType' => 'competitor-board',
                'entryRelation' => 'competitors',
                'entryLabel' => 'Wettbewerber',
            ],
            [
                'key' => 'guideline',
                'label' => 'Guidelines Boards',
                'icon' => 'heroicon-o-book-open',
                'color' => 'cyan',
                'boards' => $guidelineBoards,
                'routePrefix' => 'brands.guideline-boards.show',
                'boardType' => 'guideline-board',
                'entryRelation' => 'chapters',
                'entryLabel' => 'Kapitel',
            ],
            [
                'key' => 'moodboard',
                'label' => 'Moodboard Boards',
                'icon' => 'heroicon-o-photo',
                'color' => 'rose',
                'boards' => $moodboardBoards,
                'routePrefix' => 'brands.moodboard-boards.show',
                'boardType' => 'moodboard-board',
                'entryRelation' => 'images',
                'entryLabel' => 'Bilder',
            ],
            [
                'key' => 'asset',
                'label' => 'Asset Boards',
                'icon' => 'heroicon-o-folder-open',
                'color' => 'sky',
                'boards' => $assetBoards,
                'routePrefix' => 'brands.asset-boards.show',
                'boardType' => 'asset-board',
                'entryRelation' => 'assets',
                'entryLabel' => 'Assets',
            ],
        ])->filter(fn($group) => $group['boards']->count() > 0);

        // Meta Connection laden
        $metaConnection = $this->brand->metaConnection();

        // Verknüpfte Facebook Pages und Instagram Accounts dieser Marke (über Service)
        $facebookPages = $this->brand->facebookPages();
        $instagramAccounts = $this->brand->instagramAccounts();

        // Verfügbare Facebook Pages und Instagram Accounts des Users (noch nicht verknüpft)
        $availableFacebookPages = collect();
        $availableInstagramAccounts = collect();
        $linkService = app(\Platform\Integrations\Services\IntegrationAccountLinkService::class);

        if ($metaConnection) {
            // Alle Facebook Pages des Users
            $allFacebookPages = \Platform\Integrations\Models\IntegrationsFacebookPage::where('user_id', $user->id)
                ->get();

            // Nur die, die noch nicht verknüpft sind
            $availableFacebookPages = $allFacebookPages->reject(function ($page) use ($linkService) {
                return $linkService->isFacebookPageLinked($page);
            });

            // Alle Instagram Accounts des Users
            $allInstagramAccounts = \Platform\Integrations\Models\IntegrationsInstagramAccount::where('user_id', $user->id)
                ->get();

            // Nur die, die noch nicht verknüpft sind
            $availableInstagramAccounts = $allInstagramAccounts->reject(function ($account) use ($linkService) {
                return $linkService->isInstagramAccountLinked($account);
            });
        }

        return view('brands::livewire.brand', [
            'user' => $user,
            'boardGroups' => $boardGroups,
            'ciBoards' => $ciBoards,
            'contentBoards' => $contentBoards,
            'socialBoards' => $socialBoards,
            'kanbanBoards' => $kanbanBoards,
            'multiContentBoards' => $multiContentBoards,
            'typographyBoards' => $typographyBoards,
            'logoBoards' => $logoBoards,
            'toneOfVoiceBoards' => $toneOfVoiceBoards,
            'personaBoards' => $personaBoards,
            'competitorBoards' => $competitorBoards,
            'guidelineBoards' => $guidelineBoards,
            'moodboardBoards' => $moodboardBoards,
            'assetBoards' => $assetBoards,
            'facebookPages' => $facebookPages,
            'instagramAccounts' => $instagramAccounts,
            'availableFacebookPages' => $availableFacebookPages,
            'availableInstagramAccounts' => $availableInstagramAccounts,
            'metaConnection' => $metaConnection,
        ])->layout('platform::layouts.app');
    }
}

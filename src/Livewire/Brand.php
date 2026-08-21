<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Services\BrandVerortungResolver;
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

    /**
     * Legt ein Board des angegebenen Typs an (Registry: config('brands.board_types')).
     * Ersetzt die früheren 14 nahezu identischen createXBoard()-Methoden.
     */
    public function createBoard(string $type)
    {
        $this->authorize('update', $this->brand);

        $def = config("brands.board_types.$type");
        if (!$def) {
            session()->flash('error', 'Unbekannter Board-Typ.');
            return;
        }

        $user = Auth::user();
        $team = $user->currentTeam;

        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $model = $def['model'];
        $board = $model::create([
            'name' => $def['name'],
            'description' => null,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'brand_id' => $this->brand->id,
        ]);

        $this->brand->refresh();

        return $this->redirect(route($def['route'], $board), navigate: true);
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

        // Alle Board-Typen laden mit Preview-Relationen (großzügig für Magazin-Ansicht)
        $ciBoards = $this->brand->ciBoards()->with(['colors' => fn($q) => $q->limit(8)])->get();
        $socialBoards = $this->brand->socialBoards()->with(['slots' => fn($q) => $q->withCount('cards')])->withCount('cards')->get();
        $kanbanBoards = $this->brand->kanbanBoards()->with(['slots' => fn($q) => $q->withCount('cards')])->withCount('cards')->get();
        $typographyBoards = $this->brand->typographyBoards()->with(['entries' => fn($q) => $q->limit(5)])->get();
        $logoBoards = $this->brand->logoBoards()->with(['variants' => fn($q) => $q->limit(6)])->withCount('variants')->get();
        $toneOfVoiceBoards = $this->brand->toneOfVoiceBoards()->with(['entries' => fn($q) => $q->limit(6), 'dimensions' => fn($q) => $q->limit(6)])->withCount('entries')->get();
        $personaBoards = $this->brand->personaBoards()->with(['personas' => fn($q) => $q->limit(6)])->get();
        $competitorBoards = $this->brand->competitorBoards()->with(['competitors' => fn($q) => $q->limit(6)])->get();
        $referenceBoards = $this->brand->referenceBoards()->with(['references' => fn($q) => $q->limit(8)])->get();
        $guidelineBoards = $this->brand->guidelineBoards()->with(['chapters' => fn($q) => $q->with(['entries' => fn($e) => $e->limit(5)])->withCount('entries')->limit(8)])->get();
        $moodboardBoards = $this->brand->moodboardBoards()->with(['images' => fn($q) => $q->limit(12)])->get();
        $assetBoards = $this->brand->assetBoards()->with(['assets' => fn($q) => $q->limit(6)])->withCount('assets')->get();
        $seoBoards = $this->brand->seoBoards()->with(['keywords' => fn($q) => $q->limit(12), 'keywordClusters' => fn($q) => $q->limit(5)])->withCount('keywords')->get();
        $contentBriefBoards = $this->brand->contentBriefBoards()->with(['sections' => fn($q) => $q->limit(4)])->get();

        // Verortung dieser Marke (Entity + Typ) via Organization-Dimension
        $verortung = $this->resolveVerortung();

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
            'ciBoards' => $ciBoards,
            'socialBoards' => $socialBoards,
            'kanbanBoards' => $kanbanBoards,
            'typographyBoards' => $typographyBoards,
            'logoBoards' => $logoBoards,
            'toneOfVoiceBoards' => $toneOfVoiceBoards,
            'personaBoards' => $personaBoards,
            'competitorBoards' => $competitorBoards,
            'referenceBoards' => $referenceBoards,
            'guidelineBoards' => $guidelineBoards,
            'moodboardBoards' => $moodboardBoards,
            'assetBoards' => $assetBoards,
            'seoBoards' => $seoBoards,
            'contentBriefBoards' => $contentBriefBoards,
            'facebookPages' => $facebookPages,
            'instagramAccounts' => $instagramAccounts,
            'availableFacebookPages' => $availableFacebookPages,
            'availableInstagramAccounts' => $availableInstagramAccounts,
            'metaConnection' => $metaConnection,
            'verortung' => $verortung,
        ])->layout('platform::layouts.app');
    }

    /**
     * Organisatorische Verortung dieser Marke (über engagement_with zum Kunden aufgelöst).
     *
     * @return array{entity: string, type: ?string, sort: int, via: ?string}|null
     */
    protected function resolveVerortung(): ?array
    {
        return app(BrandVerortungResolver::class)->forBrandIds([$this->brand->id])[$this->brand->id] ?? null;
    }
}

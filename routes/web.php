<?php

use Platform\Brands\Livewire\Brand;
use Platform\Brands\Livewire\Dashboard;
use Platform\Brands\Livewire\CiBoard;
// Deprecated: Content Board Livewire-Komponenten entfernt (Ticket #441 – Entfernung 2026-06-01)
// use Platform\Brands\Livewire\ContentBoard;
// use Platform\Brands\Livewire\ContentBoardBlockTextEdit;
use Platform\Brands\Livewire\SocialBoard;
use Platform\Brands\Livewire\EditorialPlanBoard;
use Platform\Brands\Livewire\SocialCard;
use Platform\Brands\Livewire\KanbanBoard;
use Platform\Brands\Livewire\KanbanCard;
use Platform\Brands\Livewire\TypographyBoard;
use Platform\Brands\Livewire\LogoBoard;
use Platform\Brands\Livewire\ToneOfVoiceBoard;
use Platform\Brands\Livewire\PersonaBoard;
use Platform\Brands\Livewire\CompetitorBoard;
use Platform\Brands\Livewire\GuidelineBoard;
use Platform\Brands\Livewire\MoodboardBoard;
use Platform\Brands\Livewire\AssetBoard;
// Deprecated: Multi-Content-Board Livewire-Komponente entfernt (Ticket #441 – Entfernung 2026-06-01)
// use Platform\Brands\Livewire\MultiContentBoard;
use Platform\Brands\Livewire\SeoBoard as SeoBoardComponent;
use Platform\Brands\Livewire\CtaBoard as CtaBoardComponent;
use Platform\Brands\Livewire\ContentBriefBoard as ContentBriefBoardComponent;
use Platform\Brands\Livewire\FacebookPage;
use Platform\Brands\Livewire\InstagramAccount;
use Platform\Brands\Livewire\Export;
use Platform\Brands\Livewire\ExportDownload;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsCiBoard;
// Deprecated: BrandsContentBoard Model entfernt (Ticket #441)
// use Platform\Brands\Models\BrandsContentBoard;
use Platform\Brands\Models\BrandsSocialBoard;
use Platform\Brands\Models\BrandsSocialCard;
use Platform\Brands\Models\BrandsKanbanBoard as BrandsKanbanBoardModel;
use Platform\Brands\Models\BrandsKanbanCard as BrandsKanbanCardModel;
use Platform\Brands\Models\BrandsTypographyBoard as BrandsTypographyBoardModel;
use Platform\Brands\Models\BrandsLogoBoard as BrandsLogoBoardModel;
use Platform\Brands\Models\BrandsToneOfVoiceBoard as BrandsToneOfVoiceBoardModel;
use Platform\Brands\Models\BrandsPersonaBoard as BrandsPersonaBoardModel;
use Platform\Brands\Models\BrandsCompetitorBoard as BrandsCompetitorBoardModel;
use Platform\Brands\Models\BrandsGuidelineBoard as BrandsGuidelineBoardModel;
use Platform\Brands\Models\BrandsMoodboardBoard as BrandsMoodboardBoardModel;
use Platform\Brands\Models\BrandsAssetBoard as BrandsAssetBoardModel;
// Deprecated: BrandsMultiContentBoard Model entfernt (Ticket #441)
// use Platform\Brands\Models\BrandsMultiContentBoard;
use Platform\Brands\Models\BrandsSeoBoard as BrandsSeoBoardModel;
use Platform\Brands\Models\BrandsCtaBoard as BrandsCtaBoardModel;
use Platform\Brands\Models\BrandsContentBriefBoard as BrandsContentBriefBoardModel;
// Deprecated: BrandsContentBoardBlock Model entfernt (Ticket #441)
// use Platform\Brands\Models\BrandsContentBoardBlock;
use Platform\Integrations\Models\IntegrationsFacebookPage;
use Platform\Integrations\Models\IntegrationsInstagramAccount;

Route::get('/', Dashboard::class)->name('brands.dashboard');

// Model-Binding: Parameter == Modelname in camelCase
Route::get('/brands/{brandsBrand}', Brand::class)
    ->name('brands.brands.show');

// CI Board Routes
Route::get('/ci-boards/{brandsCiBoard}', CiBoard::class)
    ->name('brands.ci-boards.show');

// Deprecated: Content Board + Content Board Block Routes entfernt (Ticket #441 – Entfernung 2026-06-01)

// Social Board Routes
Route::get('/social-boards/{brandsSocialBoard}', SocialBoard::class)
    ->name('brands.social-boards.show');

// Editorial Plan (Redaktionsplan) Routes
Route::get('/social-boards/{brandsSocialBoard}/editorial-plan', EditorialPlanBoard::class)
    ->name('brands.social-boards.editorial-plan');

// Social Card Routes
Route::get('/social-cards/{brandsSocialCard}', SocialCard::class)
    ->name('brands.social-cards.show');

// Kanban Board Routes
Route::get('/kanban-boards/{brandsKanbanBoard}', KanbanBoard::class)
    ->name('brands.kanban-boards.show');

// Kanban Card Routes
Route::get('/kanban-cards/{brandsKanbanCard}', KanbanCard::class)
    ->name('brands.kanban-cards.show');

// Typography Board Routes
Route::get('/typography-boards/{brandsTypographyBoard}', TypographyBoard::class)
    ->name('brands.typography-boards.show');

// Logo Board Routes
Route::get('/logo-boards/{brandsLogoBoard}', LogoBoard::class)
    ->name('brands.logo-boards.show');

// Tone of Voice Board Routes
Route::get('/tone-of-voice-boards/{brandsToneOfVoiceBoard}', ToneOfVoiceBoard::class)
    ->name('brands.tone-of-voice-boards.show');

// Persona Board Routes
Route::get('/persona-boards/{brandsPersonaBoard}', PersonaBoard::class)
    ->name('brands.persona-boards.show');

// Competitor Board Routes
Route::get('/competitor-boards/{brandsCompetitorBoard}', CompetitorBoard::class)
    ->name('brands.competitor-boards.show');

// Guideline Board Routes
Route::get('/guideline-boards/{brandsGuidelineBoard}', GuidelineBoard::class)
    ->name('brands.guideline-boards.show');

// Moodboard Board Routes
Route::get('/moodboard-boards/{brandsMoodboardBoard}', MoodboardBoard::class)
    ->name('brands.moodboard-boards.show');

// Asset Board Routes
Route::get('/asset-boards/{brandsAssetBoard}', AssetBoard::class)
    ->name('brands.asset-boards.show');

// Deprecated: Multi-Content-Board Routes entfernt (Ticket #441 – Entfernung 2026-06-01)

// SEO Board Routes
Route::get('/seo-boards/{brandsSeoBoard}', SeoBoardComponent::class)
    ->name('brands.seo-boards.show');

// CTA Board Routes
Route::get('/cta-boards/{brandsCtaBoard}', CtaBoardComponent::class)
    ->name('brands.cta-boards.show');

// Content Brief Board Routes
Route::get('/content-brief-boards/{brandsContentBriefBoard}', ContentBriefBoardComponent::class)
    ->name('brands.content-brief-boards.show');

// Facebook Page Routes
Route::get('/facebook-pages/{facebookPage}', FacebookPage::class)
    ->name('brands.facebook-pages.show');

// Instagram Account Routes
Route::get('/instagram-accounts/{instagramAccount}', InstagramAccount::class)
    ->name('brands.instagram-accounts.show');

// Export Routes
Route::get('/brands/{brandsBrand}/export', Export::class)
    ->name('brands.export.show');

Route::get('/brands/{brandsBrand}/export/download/{format}', [ExportDownload::class, 'downloadBrand'])
    ->name('brands.export.download-brand')
    ->where('format', 'json|pdf');

Route::get('/export/boards/{boardType}/{boardId}/download/{format}', [ExportDownload::class, 'downloadBoard'])
    ->name('brands.export.download-board')
    ->where(['boardType' => 'ci-board|social-board|kanban-board|typography-board|logo-board|tone-of-voice-board|persona-board|competitor-board|guideline-board|moodboard-board|asset-board', 'boardId' => '[0-9]+', 'format' => 'json|pdf']);


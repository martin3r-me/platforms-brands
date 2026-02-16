<?php

use Platform\Brands\Livewire\Brand;
use Platform\Brands\Livewire\Dashboard;
use Platform\Brands\Livewire\CiBoard;
use Platform\Brands\Livewire\ContentBoard;
use Platform\Brands\Livewire\ContentBoardBlockTextEdit;
use Platform\Brands\Livewire\SocialBoard;
use Platform\Brands\Livewire\SocialCard;
use Platform\Brands\Livewire\KanbanBoard;
use Platform\Brands\Livewire\KanbanCard;
use Platform\Brands\Livewire\TypographyBoard;
use Platform\Brands\Livewire\LogoBoard;
use Platform\Brands\Livewire\ToneOfVoiceBoard;
use Platform\Brands\Livewire\MultiContentBoard;
use Platform\Brands\Livewire\FacebookPage;
use Platform\Brands\Livewire\InstagramAccount;
use Platform\Brands\Livewire\Export;
use Platform\Brands\Livewire\ExportDownload;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsCiBoard;
use Platform\Brands\Models\BrandsContentBoard;
use Platform\Brands\Models\BrandsSocialBoard;
use Platform\Brands\Models\BrandsSocialCard;
use Platform\Brands\Models\BrandsKanbanBoard as BrandsKanbanBoardModel;
use Platform\Brands\Models\BrandsKanbanCard as BrandsKanbanCardModel;
use Platform\Brands\Models\BrandsTypographyBoard as BrandsTypographyBoardModel;
use Platform\Brands\Models\BrandsLogoBoard as BrandsLogoBoardModel;
use Platform\Brands\Models\BrandsToneOfVoiceBoard as BrandsToneOfVoiceBoardModel;
use Platform\Brands\Models\BrandsMultiContentBoard;
use Platform\Brands\Models\BrandsContentBoardBlock;
use Platform\Integrations\Models\IntegrationsFacebookPage;
use Platform\Integrations\Models\IntegrationsInstagramAccount;

Route::get('/', Dashboard::class)->name('brands.dashboard');

// Model-Binding: Parameter == Modelname in camelCase
Route::get('/brands/{brandsBrand}', Brand::class)
    ->name('brands.brands.show');

// CI Board Routes
Route::get('/ci-boards/{brandsCiBoard}', CiBoard::class)
    ->name('brands.ci-boards.show');

// Content Board Routes
Route::get('/content-boards/{brandsContentBoard}', ContentBoard::class)
    ->name('brands.content-boards.show');

// Content Board Block Routes
Route::get('/content-board-blocks/{brandsContentBoardBlock}/{type}', ContentBoardBlockTextEdit::class)
    ->name('brands.content-board-blocks.show');

// Social Board Routes
Route::get('/social-boards/{brandsSocialBoard}', SocialBoard::class)
    ->name('brands.social-boards.show');

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

// Multi-Content-Board Routes
Route::get('/multi-content-boards/{brandsMultiContentBoard}', MultiContentBoard::class)
    ->name('brands.multi-content-boards.show');

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
    ->where(['boardType' => 'ci-board|content-board|social-board|kanban-board|multi-content-board|typography-board|logo-board|tone-of-voice-board', 'boardId' => '[0-9]+', 'format' => 'json|pdf']);

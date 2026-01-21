<?php

use Platform\Brands\Livewire\Brand;
use Platform\Brands\Livewire\Dashboard;
use Platform\Brands\Livewire\CiBoard;
use Platform\Brands\Livewire\ContentBoard;
use Platform\Brands\Livewire\ContentBoardSection;
use Platform\Brands\Livewire\ContentBoardBlockTextEdit;
use Platform\Brands\Livewire\SocialBoard;
use Platform\Brands\Livewire\SocialCard;
use Platform\Brands\Livewire\MultiContentBoard;
use Platform\Brands\Livewire\FacebookPage;
use Platform\Brands\Livewire\InstagramAccount;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsCiBoard;
use Platform\Brands\Models\BrandsContentBoard;
use Platform\Brands\Models\BrandsContentBoardSection;
use Platform\Brands\Models\BrandsSocialBoard;
use Platform\Brands\Models\BrandsSocialCard;
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

// Content Board Section Routes
Route::get('/content-board-sections/{brandsContentBoardSection}', ContentBoardSection::class)
    ->name('brands.content-board-sections.show');

// Content Board Block Routes
Route::get('/content-board-blocks/{brandsContentBoardBlock}/{type}', ContentBoardBlockTextEdit::class)
    ->name('brands.content-board-blocks.show');

// Social Board Routes
Route::get('/social-boards/{brandsSocialBoard}', SocialBoard::class)
    ->name('brands.social-boards.show');

// Social Card Routes
Route::get('/social-cards/{brandsSocialCard}', SocialCard::class)
    ->name('brands.social-cards.show');

// Multi-Content-Board Routes
Route::get('/multi-content-boards/{brandsMultiContentBoard}', MultiContentBoard::class)
    ->name('brands.multi-content-boards.show');

// Facebook Page Routes
Route::get('/facebook-pages/{facebookPage}', FacebookPage::class)
    ->name('brands.facebook-pages.show');

// Instagram Account Routes
Route::get('/instagram-accounts/{instagramAccount}', InstagramAccount::class)
    ->name('brands.instagram-accounts.show');

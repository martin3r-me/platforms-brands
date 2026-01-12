<?php

use Platform\Brands\Livewire\Brand;
use Platform\Brands\Livewire\Dashboard;
use Platform\Brands\Livewire\CiBoard;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsCiBoard;

Route::get('/', Dashboard::class)->name('brands.dashboard');

// Model-Binding: Parameter == Modelname in camelCase
Route::get('/brands/{brandsBrand}', Brand::class)
    ->name('brands.brands.show');

// CI Board Routes
Route::get('/ci-boards/{brandsCiBoard}', CiBoard::class)
    ->name('brands.ci-boards.show');

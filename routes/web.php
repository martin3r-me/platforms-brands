<?php

use Platform\Brands\Livewire\Brand;
use Platform\Brands\Livewire\Dashboard;
use Platform\Brands\Models\BrandsBrand;

Route::get('/', Dashboard::class)->name('brands.dashboard');

// Model-Binding: Parameter == Modelname in camelCase
Route::get('/brands/{brandsBrand}', Brand::class)
    ->name('brands.brands.show');

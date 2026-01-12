<?php

use Platform\Brands\Livewire\Brand;
use Platform\Brands\Models\BrandsBrand;

// Model-Binding: Parameter == Modelname in camelCase
Route::get('/brands/{brandsBrand}', Brand::class)
    ->name('brands.brands.show');

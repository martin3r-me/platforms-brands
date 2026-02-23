<?php

use Illuminate\Support\Facades\Route;
use Platform\Brands\Livewire\Public\IntakeStart;
use Platform\Brands\Livewire\Public\IntakeSession;

Route::get('/p/{publicToken}', IntakeStart::class)->name('brands.public.intake-start');
Route::get('/s/{sessionToken}', IntakeSession::class)->name('brands.public.intake-session');

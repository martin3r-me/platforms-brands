<?php

use Illuminate\Support\Facades\Route;

/*
| Öffentliche Auslieferung der kuratierten Katalog-Fonts (self-hosted, kein CDN).
| Dateien liegen im Package unter resources/fonts/*.woff2 – DSGVO-konform.
*/
Route::get('/brands/assets/fonts/{file}', function (string $file) {
    abort_unless(preg_match('/^[a-z0-9\-]+\.woff2$/', $file), 404);

    $path = __DIR__ . '/../resources/fonts/' . $file;
    abort_unless(is_file($path), 404);

    return response()->file($path, [
        'Content-Type' => 'font/woff2',
        'Cache-Control' => 'public, max-age=31536000, immutable',
        'Access-Control-Allow-Origin' => '*',
    ]);
})->name('brands.font');

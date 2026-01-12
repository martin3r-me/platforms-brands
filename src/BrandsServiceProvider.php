<?php

namespace Platform\Brands;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Platform\Core\PlatformCore;
use Platform\Core\Routing\ModuleRouter;

use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Policies\BrandPolicy;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class BrandsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Commands können später hinzugefügt werden
    }

    public function boot(): void
    {
        // Config veröffentlichen & zusammenführen (MUSS VOR registerModule sein!)
        $this->publishes([
            __DIR__.'/../config/brands.php' => config_path('brands.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__.'/../config/brands.php', 'brands');

        // Modul-Registrierung nur, wenn Config & Tabelle vorhanden
        if (
            config()->has('brands.routing') &&
            config()->has('brands.navigation') &&
            Schema::hasTable('modules')
        ) {
            PlatformCore::registerModule([
                'key'        => 'brands',
                'title'      => 'Marken',
                'routing'    => config('brands.routing'),
                'guard'      => config('brands.guard'),
                'navigation' => config('brands.navigation'),
                'sidebar'    => config('brands.sidebar'),
                'billables'  => config('brands.billables', []),
            ]);
        }

        // Routen nur laden, wenn das Modul registriert wurde
        if (PlatformCore::getModule('brands')) {
            ModuleRouter::group('brands', function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            });
        }

        // Migrations, Views, Livewire-Komponenten
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'brands');
        $this->registerLivewireComponents();

        // Policies registrieren
        $this->registerPolicies();
    }
    
    protected function registerLivewireComponents(): void
    {
        $basePath = __DIR__ . '/Livewire';
        $baseNamespace = 'Platform\\Brands\\Livewire';
        $prefix = 'brands';

        if (!is_dir($basePath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $classPath = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $class = $baseNamespace . '\\' . $classPath;

            if (!class_exists($class)) {
                continue;
            }

            $aliasPath = str_replace(['\\', '/'], '.', Str::kebab(str_replace('.php', '', $relativePath)));
            $alias = $prefix . '.' . $aliasPath;

            Livewire::component($alias, $class);
        }
    }

    /**
     * Registriert Policies für das Brands-Modul
     */
    protected function registerPolicies(): void
    {
        $policies = [
            BrandsBrand::class => BrandPolicy::class,
        ];

        foreach ($policies as $model => $policy) {
            if (class_exists($model) && class_exists($policy)) {
                Gate::policy($model, $policy);
            }
        }
    }
}

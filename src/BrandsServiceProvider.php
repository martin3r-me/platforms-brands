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
use Platform\Brands\Models\BrandsCiBoard;
use Platform\Brands\Models\BrandsCiBoardColor;
use Platform\Brands\Models\BrandsContentBoard;
use Platform\Brands\Models\BrandsFacebookPage;
use Platform\Brands\Models\BrandsInstagramAccount;
use Platform\Brands\Policies\BrandPolicy;
use Platform\Brands\Policies\CiBoardPolicy;
use Platform\Brands\Policies\CiBoardColorPolicy;
use Platform\Brands\Policies\ContentBoardPolicy;
use Platform\Brands\Policies\FacebookPagePolicy;
use Platform\Brands\Policies\InstagramAccountPolicy;

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

        // Tools registrieren
        $this->registerTools();
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
            BrandsCiBoard::class => CiBoardPolicy::class,
            BrandsCiBoardColor::class => CiBoardColorPolicy::class,
            BrandsContentBoard::class => ContentBoardPolicy::class,
            BrandsFacebookPage::class => FacebookPagePolicy::class,
            BrandsInstagramAccount::class => InstagramAccountPolicy::class,
        ];

        foreach ($policies as $model => $policy) {
            if (class_exists($model) && class_exists($policy)) {
                Gate::policy($model, $policy);
            }
        }
    }

    /**
     * Registriert Tools für das Brands-Modul
     */
    protected function registerTools(): void
    {
        try {
            $registry = resolve(\Platform\Core\Tools\ToolRegistry::class);
            
            // Brand-Tools
            $registry->register(new \Platform\Brands\Tools\CreateBrandTool());
            $registry->register(new \Platform\Brands\Tools\ListBrandsTool());
            $registry->register(new \Platform\Brands\Tools\GetBrandTool());
            $registry->register(new \Platform\Brands\Tools\UpdateBrandTool());
            $registry->register(new \Platform\Brands\Tools\DeleteBrandTool());
            
            // CRM-Verknüpfungen
            $registry->register(new \Platform\Brands\Tools\LinkBrandCompanyTool());
            $registry->register(new \Platform\Brands\Tools\LinkBrandContactTool());
            
            // CiBoard-Tools
            $registry->register(new \Platform\Brands\Tools\CreateCiBoardTool());
            $registry->register(new \Platform\Brands\Tools\ListCiBoardsTool());
            $registry->register(new \Platform\Brands\Tools\GetCiBoardTool());
            $registry->register(new \Platform\Brands\Tools\UpdateCiBoardTool());
            $registry->register(new \Platform\Brands\Tools\DeleteCiBoardTool());
            
            // CiBoardColor-Tools
            $registry->register(new \Platform\Brands\Tools\CreateCiBoardColorTool());
            $registry->register(new \Platform\Brands\Tools\ListCiBoardColorsTool());
            $registry->register(new \Platform\Brands\Tools\GetCiBoardColorTool());
            $registry->register(new \Platform\Brands\Tools\UpdateCiBoardColorTool());
            $registry->register(new \Platform\Brands\Tools\DeleteCiBoardColorTool());
            
            // ContentBoard-Tools
            $registry->register(new \Platform\Brands\Tools\CreateContentBoardTool());
            $registry->register(new \Platform\Brands\Tools\ListContentBoardsTool());
            $registry->register(new \Platform\Brands\Tools\GetContentBoardTool());
            $registry->register(new \Platform\Brands\Tools\UpdateContentBoardTool());
            $registry->register(new \Platform\Brands\Tools\DeleteContentBoardTool());
        } catch (\Throwable $e) {
            // Silent fail - Tool-Registry könnte nicht verfügbar sein
        }
    }
}

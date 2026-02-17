<?php

namespace Platform\Brands;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Platform\Core\PlatformCore;
use Platform\Core\Routing\ModuleRouter;

use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsCiBoard;
use Platform\Brands\Models\BrandsCiBoardColor;
use Platform\Brands\Models\BrandsContentBoard;
use Platform\Brands\Models\BrandsContentBoardBlockText;
use Platform\Brands\Models\BrandsMultiContentBoard;
use Platform\Brands\Models\BrandsSocialBoard;
use Platform\Brands\Models\BrandsSocialCard;
use Platform\Brands\Models\BrandsKanbanBoard;
use Platform\Brands\Models\BrandsKanbanCard;
use Platform\Brands\Models\BrandsTypographyBoard;
use Platform\Brands\Models\BrandsTypographyEntry;
use Platform\Brands\Models\BrandsLogoBoard;
use Platform\Brands\Models\BrandsLogoVariant;
use Platform\Brands\Models\BrandsToneOfVoiceBoard;
use Platform\Brands\Models\BrandsToneOfVoiceEntry;
use Platform\Brands\Models\BrandsToneOfVoiceDimension;
use Platform\Brands\Models\BrandsPersonaBoard;
use Platform\Brands\Models\BrandsPersona;
use Platform\Brands\Models\BrandsCompetitorBoard;
use Platform\Brands\Models\BrandsCompetitor;
use Platform\Brands\Models\BrandsGuidelineBoard;
use Platform\Brands\Models\BrandsGuidelineChapter;
use Platform\Brands\Models\BrandsGuidelineEntry;
use Platform\Brands\Models\BrandsMoodboardBoard;
use Platform\Brands\Models\BrandsMoodboardImage;
use Platform\Integrations\Models\IntegrationsFacebookPage;
use Platform\Integrations\Models\IntegrationsInstagramAccount;
use Platform\Brands\Policies\BrandPolicy;
use Platform\Brands\Policies\CiBoardPolicy;
use Platform\Brands\Policies\CiBoardColorPolicy;
use Platform\Brands\Policies\ContentBoardPolicy;
use Platform\Brands\Policies\MultiContentBoardPolicy;
use Platform\Brands\Policies\SocialBoardPolicy;
use Platform\Brands\Policies\SocialCardPolicy;
use Platform\Brands\Policies\KanbanBoardPolicy;
use Platform\Brands\Policies\KanbanCardPolicy;
use Platform\Brands\Policies\TypographyBoardPolicy;
use Platform\Brands\Policies\TypographyEntryPolicy;
use Platform\Brands\Policies\LogoBoardPolicy;
use Platform\Brands\Policies\LogoVariantPolicy;
use Platform\Brands\Policies\ToneOfVoiceBoardPolicy;
use Platform\Brands\Policies\ToneOfVoiceEntryPolicy;
use Platform\Brands\Policies\ToneOfVoiceDimensionPolicy;
use Platform\Brands\Policies\PersonaBoardPolicy;
use Platform\Brands\Policies\PersonaPolicy;
use Platform\Brands\Policies\CompetitorBoardPolicy;
use Platform\Brands\Policies\CompetitorPolicy;
use Platform\Brands\Policies\GuidelineBoardPolicy;
use Platform\Brands\Policies\GuidelineChapterPolicy;
use Platform\Brands\Policies\GuidelineEntryPolicy;
use Platform\Brands\Policies\MoodboardBoardPolicy;
use Platform\Brands\Policies\MoodboardImagePolicy;
use Platform\Brands\Policies\FacebookPagePolicy;
use Platform\Brands\Policies\InstagramAccountPolicy;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class BrandsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Commands registrieren
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Platform\Brands\Console\Commands\SyncFacebookPages::class,
                \Platform\Brands\Console\Commands\SyncFacebookPosts::class,
                \Platform\Brands\Console\Commands\SyncInstagramAccounts::class,
                \Platform\Brands\Console\Commands\SyncInstagramMedia::class,
                \Platform\Brands\Console\Commands\SyncInstagramInsights::class,
                \Platform\Brands\Console\Commands\SyncAll::class,
                \Platform\Brands\Console\Commands\TruncateIntegrationsData::class,
            ]);
        }

        // Export-Service als Singleton registrieren
        $this->app->singleton(\Platform\Brands\Services\BrandsExportService::class);
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

        // Morph Map für Content Board Block Types registrieren
        $this->registerMorphMap();

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
            BrandsMultiContentBoard::class => MultiContentBoardPolicy::class,
            BrandsSocialBoard::class => SocialBoardPolicy::class,
            BrandsSocialCard::class => SocialCardPolicy::class,
            BrandsKanbanBoard::class => KanbanBoardPolicy::class,
            BrandsKanbanCard::class => KanbanCardPolicy::class,
            BrandsTypographyBoard::class => TypographyBoardPolicy::class,
            BrandsTypographyEntry::class => TypographyEntryPolicy::class,
            BrandsLogoBoard::class => LogoBoardPolicy::class,
            BrandsLogoVariant::class => LogoVariantPolicy::class,
            BrandsToneOfVoiceBoard::class => ToneOfVoiceBoardPolicy::class,
            BrandsToneOfVoiceEntry::class => ToneOfVoiceEntryPolicy::class,
            BrandsToneOfVoiceDimension::class => ToneOfVoiceDimensionPolicy::class,
            BrandsPersonaBoard::class => PersonaBoardPolicy::class,
            BrandsPersona::class => PersonaPolicy::class,
            BrandsCompetitorBoard::class => CompetitorBoardPolicy::class,
            BrandsCompetitor::class => CompetitorPolicy::class,
            BrandsGuidelineBoard::class => GuidelineBoardPolicy::class,
            BrandsGuidelineChapter::class => GuidelineChapterPolicy::class,
            BrandsGuidelineEntry::class => GuidelineEntryPolicy::class,
            BrandsMoodboardBoard::class => MoodboardBoardPolicy::class,
            BrandsMoodboardImage::class => MoodboardImagePolicy::class,
            IntegrationsFacebookPage::class => FacebookPagePolicy::class,
            IntegrationsInstagramAccount::class => InstagramAccountPolicy::class,
        ];

        foreach ($policies as $model => $policy) {
            if (class_exists($model) && class_exists($policy)) {
                Gate::policy($model, $policy);
            }
        }
    }

    /**
     * Registriert Morph Map für Content Board Block Types
     * Verwendet morphMap() statt enforceMorphMap(), damit nur unsere Content-Typen gemappt werden
     * und andere polymorphe Beziehungen im System nicht betroffen sind.
     */
    protected function registerMorphMap(): void
    {
        Relation::morphMap([
            'text' => BrandsContentBoardBlockText::class,
            // 'image' => BrandsContentBoardBlockImage::class,
        ]);
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
            
            // SocialBoard-Tools
            $registry->register(new \Platform\Brands\Tools\CreateSocialBoardTool());
            $registry->register(new \Platform\Brands\Tools\ListSocialBoardsTool());
            $registry->register(new \Platform\Brands\Tools\GetSocialBoardTool());
            $registry->register(new \Platform\Brands\Tools\UpdateSocialBoardTool());
            $registry->register(new \Platform\Brands\Tools\DeleteSocialBoardTool());
            
            // MultiContentBoard-Tools
            $registry->register(new \Platform\Brands\Tools\CreateMultiContentBoardTool());
            $registry->register(new \Platform\Brands\Tools\ListMultiContentBoardsTool());
            $registry->register(new \Platform\Brands\Tools\GetMultiContentBoardTool());
            $registry->register(new \Platform\Brands\Tools\UpdateMultiContentBoardTool());
            $registry->register(new \Platform\Brands\Tools\DeleteMultiContentBoardTool());
            
            // KanbanBoard-Tools
            $registry->register(new \Platform\Brands\Tools\CreateKanbanBoardTool());
            $registry->register(new \Platform\Brands\Tools\ListKanbanBoardsTool());
            $registry->register(new \Platform\Brands\Tools\GetKanbanBoardTool());
            $registry->register(new \Platform\Brands\Tools\UpdateKanbanBoardTool());
            $registry->register(new \Platform\Brands\Tools\DeleteKanbanBoardTool());

            // TypographyBoard-Tools
            $registry->register(new \Platform\Brands\Tools\CreateTypographyBoardTool());
            $registry->register(new \Platform\Brands\Tools\ListTypographyBoardsTool());
            $registry->register(new \Platform\Brands\Tools\GetTypographyBoardTool());
            $registry->register(new \Platform\Brands\Tools\UpdateTypographyBoardTool());
            $registry->register(new \Platform\Brands\Tools\DeleteTypographyBoardTool());

            // TypographyEntry-Tools
            $registry->register(new \Platform\Brands\Tools\CreateTypographyEntryTool());
            $registry->register(new \Platform\Brands\Tools\ListTypographyEntriesTool());
            $registry->register(new \Platform\Brands\Tools\GetTypographyEntryTool());
            $registry->register(new \Platform\Brands\Tools\UpdateTypographyEntryTool());
            $registry->register(new \Platform\Brands\Tools\DeleteTypographyEntryTool());

            // LogoBoard-Tools
            $registry->register(new \Platform\Brands\Tools\CreateLogoBoardTool());
            $registry->register(new \Platform\Brands\Tools\ListLogoBoardsTool());
            $registry->register(new \Platform\Brands\Tools\GetLogoBoardTool());
            $registry->register(new \Platform\Brands\Tools\UpdateLogoBoardTool());
            $registry->register(new \Platform\Brands\Tools\DeleteLogoBoardTool());

            // LogoVariant-Tools
            $registry->register(new \Platform\Brands\Tools\CreateLogoVariantTool());
            $registry->register(new \Platform\Brands\Tools\ListLogoVariantsTool());
            $registry->register(new \Platform\Brands\Tools\GetLogoVariantTool());
            $registry->register(new \Platform\Brands\Tools\UpdateLogoVariantTool());
            $registry->register(new \Platform\Brands\Tools\DeleteLogoVariantTool());

            // KanbanCard-Tools
            $registry->register(new \Platform\Brands\Tools\CreateKanbanCardTool());
            $registry->register(new \Platform\Brands\Tools\ListKanbanCardsTool());
            $registry->register(new \Platform\Brands\Tools\GetKanbanCardTool());
            $registry->register(new \Platform\Brands\Tools\UpdateKanbanCardTool());
            $registry->register(new \Platform\Brands\Tools\DeleteKanbanCardTool());

            // SocialCard-Tools
            $registry->register(new \Platform\Brands\Tools\CreateSocialCardTool());
            $registry->register(new \Platform\Brands\Tools\ListSocialCardsTool());
            $registry->register(new \Platform\Brands\Tools\GetSocialCardTool());
            $registry->register(new \Platform\Brands\Tools\UpdateSocialCardTool());
            $registry->register(new \Platform\Brands\Tools\DeleteSocialCardTool());
            
            // SocialCard Bulk-Tools
            $registry->register(new \Platform\Brands\Tools\BulkCreateSocialCardsTool());
            $registry->register(new \Platform\Brands\Tools\BulkUpdateSocialCardsTool());
            
            // ContentBoardBlock-Tools
            $registry->register(new \Platform\Brands\Tools\CreateContentBoardBlockTool());
            $registry->register(new \Platform\Brands\Tools\ListContentBoardBlocksTool());
            $registry->register(new \Platform\Brands\Tools\GetContentBoardBlockTool());
            $registry->register(new \Platform\Brands\Tools\UpdateContentBoardBlockTool());
            $registry->register(new \Platform\Brands\Tools\DeleteContentBoardBlockTool());
            
            // ContentBoardBlock Bulk-Tools
            $registry->register(new \Platform\Brands\Tools\BulkCreateContentBoardBlocksTool());
            $registry->register(new \Platform\Brands\Tools\BulkUpdateContentBoardBlocksTool());
            
            // ContentBoardBlockText Tools (CRUD)
            $registry->register(new \Platform\Brands\Tools\CreateContentBoardBlockTextTool());
            $registry->register(new \Platform\Brands\Tools\UpdateContentBoardBlockTextTool());
            $registry->register(new \Platform\Brands\Tools\GetContentBoardBlockTextTool());
            $registry->register(new \Platform\Brands\Tools\DeleteContentBoardBlockTextTool());
            
            // Facebook Pages-Tools
            $registry->register(new \Platform\Brands\Tools\ListFacebookPagesTool());
            $registry->register(new \Platform\Brands\Tools\GetFacebookPageTool());
            
            // Instagram Accounts-Tools
            $registry->register(new \Platform\Brands\Tools\ListInstagramAccountsTool());
            $registry->register(new \Platform\Brands\Tools\GetInstagramAccountTool());
            
            // Facebook Posts-Tools
            $registry->register(new \Platform\Brands\Tools\ListFacebookPostsTool());
            $registry->register(new \Platform\Brands\Tools\GetFacebookPostTool());
            
            // Instagram Media-Tools
            $registry->register(new \Platform\Brands\Tools\ListInstagramMediaTool());
            $registry->register(new \Platform\Brands\Tools\GetInstagramMediaTool());
            
            // Content-Tool (generisch für Inhalte/Captions/Texte)
            $registry->register(new \Platform\Brands\Tools\GetContentTool());

            // ToneOfVoiceBoard-Tools
            $registry->register(new \Platform\Brands\Tools\CreateToneOfVoiceBoardTool());
            $registry->register(new \Platform\Brands\Tools\ListToneOfVoiceBoardsTool());
            $registry->register(new \Platform\Brands\Tools\GetToneOfVoiceBoardTool());
            $registry->register(new \Platform\Brands\Tools\UpdateToneOfVoiceBoardTool());
            $registry->register(new \Platform\Brands\Tools\DeleteToneOfVoiceBoardTool());

            // ToneOfVoiceEntry-Tools
            $registry->register(new \Platform\Brands\Tools\CreateToneOfVoiceEntryTool());
            $registry->register(new \Platform\Brands\Tools\ListToneOfVoiceEntriesTool());
            $registry->register(new \Platform\Brands\Tools\GetToneOfVoiceEntryTool());
            $registry->register(new \Platform\Brands\Tools\UpdateToneOfVoiceEntryTool());
            $registry->register(new \Platform\Brands\Tools\DeleteToneOfVoiceEntryTool());

            // ToneOfVoiceDimension-Tools
            $registry->register(new \Platform\Brands\Tools\CreateToneOfVoiceDimensionTool());
            $registry->register(new \Platform\Brands\Tools\ListToneOfVoiceDimensionsTool());
            $registry->register(new \Platform\Brands\Tools\UpdateToneOfVoiceDimensionTool());
            $registry->register(new \Platform\Brands\Tools\DeleteToneOfVoiceDimensionTool());

            // PersonaBoard-Tools
            $registry->register(new \Platform\Brands\Tools\CreatePersonaBoardTool());
            $registry->register(new \Platform\Brands\Tools\ListPersonaBoardsTool());
            $registry->register(new \Platform\Brands\Tools\GetPersonaBoardTool());
            $registry->register(new \Platform\Brands\Tools\UpdatePersonaBoardTool());
            $registry->register(new \Platform\Brands\Tools\DeletePersonaBoardTool());

            // Persona-Tools
            $registry->register(new \Platform\Brands\Tools\CreatePersonaTool());
            $registry->register(new \Platform\Brands\Tools\ListPersonasTool());
            $registry->register(new \Platform\Brands\Tools\GetPersonaTool());
            $registry->register(new \Platform\Brands\Tools\UpdatePersonaTool());
            $registry->register(new \Platform\Brands\Tools\DeletePersonaTool());

            // CompetitorBoard-Tools
            $registry->register(new \Platform\Brands\Tools\CreateCompetitorBoardTool());
            $registry->register(new \Platform\Brands\Tools\ListCompetitorBoardsTool());
            $registry->register(new \Platform\Brands\Tools\GetCompetitorBoardTool());
            $registry->register(new \Platform\Brands\Tools\UpdateCompetitorBoardTool());
            $registry->register(new \Platform\Brands\Tools\DeleteCompetitorBoardTool());

            // Competitor-Tools
            $registry->register(new \Platform\Brands\Tools\CreateCompetitorTool());
            $registry->register(new \Platform\Brands\Tools\ListCompetitorsTool());
            $registry->register(new \Platform\Brands\Tools\GetCompetitorTool());
            $registry->register(new \Platform\Brands\Tools\UpdateCompetitorTool());
            $registry->register(new \Platform\Brands\Tools\DeleteCompetitorTool());

            // GuidelineBoard-Tools
            $registry->register(new \Platform\Brands\Tools\CreateGuidelineBoardTool());
            $registry->register(new \Platform\Brands\Tools\ListGuidelineBoardsTool());
            $registry->register(new \Platform\Brands\Tools\GetGuidelineBoardTool());
            $registry->register(new \Platform\Brands\Tools\UpdateGuidelineBoardTool());
            $registry->register(new \Platform\Brands\Tools\DeleteGuidelineBoardTool());

            // GuidelineChapter-Tools
            $registry->register(new \Platform\Brands\Tools\CreateGuidelineChapterTool());
            $registry->register(new \Platform\Brands\Tools\ListGuidelineChaptersTool());
            $registry->register(new \Platform\Brands\Tools\UpdateGuidelineChapterTool());
            $registry->register(new \Platform\Brands\Tools\DeleteGuidelineChapterTool());

            // GuidelineEntry-Tools
            $registry->register(new \Platform\Brands\Tools\CreateGuidelineEntryTool());
            $registry->register(new \Platform\Brands\Tools\ListGuidelineEntriesTool());
            $registry->register(new \Platform\Brands\Tools\GetGuidelineEntryTool());
            $registry->register(new \Platform\Brands\Tools\UpdateGuidelineEntryTool());
            $registry->register(new \Platform\Brands\Tools\DeleteGuidelineEntryTool());

            // MoodboardBoard-Tools
            $registry->register(new \Platform\Brands\Tools\CreateMoodboardBoardTool());
            $registry->register(new \Platform\Brands\Tools\ListMoodboardBoardsTool());
            $registry->register(new \Platform\Brands\Tools\GetMoodboardBoardTool());
            $registry->register(new \Platform\Brands\Tools\UpdateMoodboardBoardTool());
            $registry->register(new \Platform\Brands\Tools\DeleteMoodboardBoardTool());

            // MoodboardImage-Tools
            $registry->register(new \Platform\Brands\Tools\CreateMoodboardImageTool());
            $registry->register(new \Platform\Brands\Tools\ListMoodboardImagesTool());
            $registry->register(new \Platform\Brands\Tools\GetMoodboardImageTool());
            $registry->register(new \Platform\Brands\Tools\UpdateMoodboardImageTool());
            $registry->register(new \Platform\Brands\Tools\DeleteMoodboardImageTool());

            // Export-Tools
            $registry->register(new \Platform\Brands\Tools\ExportBrandTool());
            $registry->register(new \Platform\Brands\Tools\ExportBoardTool());
        } catch (\Throwable $e) {
            // Silent fail - Tool-Registry könnte nicht verfügbar sein
        }
    }
}

<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Illuminate\Support\Facades\Log;
use Platform\Organization\Traits\HasOrganizationContexts;
use Platform\Core\Traits\HasColors;
use Platform\Core\Contracts\HasKeyResultAncestors;
use Platform\Core\Contracts\HasDisplayName;
use Platform\Core\Contracts\AgendaRenderable;
use Platform\Crm\Traits\HasCompanyLinksTrait;
use Platform\Crm\Contracts\CompanyInterface;
use Platform\Crm\Contracts\ContactInterface;
use Platform\Integrations\Contracts\SocialMediaAccountLinkableInterface;

/**
 * @ai.description Marke dient als Container für Brand-Management im Team.
 */
class BrandsBrand extends Model implements HasKeyResultAncestors, HasDisplayName, SocialMediaAccountLinkableInterface, AgendaRenderable
{
    use HasOrganizationContexts, HasColors, HasCompanyLinksTrait;

    protected $table = 'brands_brands';

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'order',
        'user_id',
        'team_id',
        'done',
        'done_at',
    ];

    protected $casts = [
        'uuid' => 'string',
        'done' => 'boolean',
        'done_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $model->uuid = $uuid;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }

    /**
     * Beziehung zu CRM-Kontakten über polymorphe Links
     */
    public function crmContactLinks()
    {
        return $this->morphMany(
            \Platform\Crm\Models\CrmContactLink::class,
            'linkable'
        );
    }

    /**
     * Gibt das primäre verknüpfte Unternehmen zurück (über Interface)
     */
    public function getCompany(): ?CompanyInterface
    {
        return $this->companyLinks()->first()?->company;
    }

    /**
     * Gibt den primären verknüpften Kontakt zurück (über Interface)
     */
    public function getContact(): ?ContactInterface
    {
        return $this->crmContactLinks()->first()?->contact;
    }

    /**
     * Gibt alle Vorfahren-Kontexte für die KeyResult-Kaskade zurück.
     * Brand → Brand selbst (als Root)
     */
    public function keyResultAncestors(): array
    {
        return [];
    }

    /**
     * Gibt den anzeigbaren Namen der Marke zurück.
     */
    public function getDisplayName(): ?string
    {
        return $this->name;
    }

    /**
     * CI Boards dieser Marke
     */
    public function ciBoards()
    {
        return $this->hasMany(BrandsCiBoard::class, 'brand_id')->orderBy('order');
    }

    /**
     * @deprecated Content Boards wurden entfernt (Ticket #441 – Entfernung 2026-06-01).
     *             Verwende stattdessen contentBriefBoards().
     */
    public function contentBoards()
    {
        // Deprecated: gibt leere Collection zurück
        return $this->hasMany(self::class, 'id')->whereRaw('1 = 0');
    }

    /**
     * Social Boards dieser Marke
     */
    public function socialBoards()
    {
        return $this->hasMany(BrandsSocialBoard::class, 'brand_id')->orderBy('order');
    }

    public function kanbanBoards()
    {
        return $this->hasMany(BrandsKanbanBoard::class, 'brand_id')->orderBy('order');
    }

    /**
     * @deprecated Multi Content Boards wurden entfernt (Ticket #441 – Entfernung 2026-06-01).
     *             Verwende stattdessen contentBriefBoards().
     */
    public function multiContentBoards()
    {
        // Deprecated: gibt leere Collection zurück
        return $this->hasMany(self::class, 'id')->whereRaw('1 = 0');
    }

    public function typographyBoards()
    {
        return $this->hasMany(BrandsTypographyBoard::class, 'brand_id')->orderBy('order');
    }

    public function logoBoards()
    {
        return $this->hasMany(BrandsLogoBoard::class, 'brand_id')->orderBy('order');
    }

    public function toneOfVoiceBoards()
    {
        return $this->hasMany(BrandsToneOfVoiceBoard::class, 'brand_id')->orderBy('order');
    }

    public function personaBoards()
    {
        return $this->hasMany(BrandsPersonaBoard::class, 'brand_id')->orderBy('order');
    }

    public function competitorBoards()
    {
        return $this->hasMany(BrandsCompetitorBoard::class, 'brand_id')->orderBy('order');
    }

    public function guidelineBoards()
    {
        return $this->hasMany(BrandsGuidelineBoard::class, 'brand_id')->orderBy('order');
    }

    public function moodboardBoards()
    {
        return $this->hasMany(BrandsMoodboardBoard::class, 'brand_id')->orderBy('order');
    }

    public function assetBoards()
    {
        return $this->hasMany(BrandsAssetBoard::class, 'brand_id')->orderBy('order');
    }

    public function seoBoards()
    {
        return $this->hasMany(BrandsSeoBoard::class, 'brand_id')->orderBy('order');
    }

    /**
     * Content Brief Boards dieser Marke
     */
    public function contentBriefBoards()
    {
        return $this->hasMany(BrandsContentBriefBoard::class, 'brand_id')->orderBy('order');
    }

    /**
     * CTAs (Call-to-Actions) dieser Marke
     */
    public function ctas()
    {
        return $this->hasMany(BrandsCta::class, 'brand_id');
    }

    /**
     * Meta OAuth Token dieser Marke (über User)
     * Ein Brand verwendet den Meta Token des Users
     */
    /**
     * Ruft die Meta IntegrationConnection für den Brand-User ab
     * @deprecated Verwende stattdessen MetaIntegrationService::getConnectionForUser()
     */
    public function metaConnection()
    {
        $user = $this->user;
        
        if ($user) {
            $metaService = app(\Platform\Integrations\Services\MetaIntegrationService::class);
            return $metaService->getConnectionForUser($user);
        }
        
        return null;
    }
    
    /**
     * @deprecated Verwende stattdessen metaConnection()
     */
    public function metaToken()
    {
        return $this->metaConnection();
    }

    /**
     * Facebook Pages dieser Marke (über lose Verknüpfung)
     */
    public function facebookPages()
    {
        $service = app(\Platform\Integrations\Services\IntegrationAccountLinkService::class);
        return $service->getLinkedFacebookPages($this);
    }

    /**
     * Instagram Accounts dieser Marke (über lose Verknüpfung)
     */
    public function instagramAccounts()
    {
        $service = app(\Platform\Integrations\Services\IntegrationAccountLinkService::class);
        return $service->getLinkedInstagramAccounts($this);
    }

    /**
     * WhatsApp Accounts dieser Marke
     * TODO: Verknüpfung implementieren, wenn benötigt
     */
    public function whatsappAccounts()
    {
        // TODO: Verknüpfung implementieren
        return collect();
    }

    /**
     * SocialMediaAccountLinkableInterface Implementation
     */
    public function getSocialMediaAccountLinkableId(): int
    {
        return $this->id;
    }

    public function getSocialMediaAccountLinkableType(): string
    {
        return self::class;
    }

    public function getTeamId(): int
    {
        return $this->team_id ?? 0;
    }

    // ── AgendaRenderable ──────────────────────────────────────

    public function toAgendaItem(): array
    {
        return [
            'title' => $this->name,
            'description' => $this->description ? \Illuminate\Support\Str::limit($this->description, 120) : null,
            'icon' => '🏷️',
            'color' => $this->color,
            'status' => $this->done ? 'Erledigt' : 'Aktiv',
            'status_color' => $this->done ? 'green' : 'blue',
            'url' => route('brands.brands.show', $this),
            'meta' => [],
        ];
    }
}

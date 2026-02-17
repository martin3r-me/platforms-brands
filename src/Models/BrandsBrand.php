<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Illuminate\Support\Facades\Log;
use Platform\Organization\Traits\HasOrganizationContexts;
use Platform\Core\Traits\HasColors;
use Platform\Core\Contracts\HasTimeAncestors;
use Platform\Core\Contracts\HasKeyResultAncestors;
use Platform\Core\Contracts\HasDisplayName;
use Platform\Crm\Traits\HasCompanyLinksTrait;
use Platform\Crm\Contracts\CompanyInterface;
use Platform\Crm\Contracts\ContactInterface;
use Platform\Integrations\Contracts\SocialMediaAccountLinkableInterface;

/**
 * @ai.description Marke dient als Container für Brand-Management im Team.
 */
class BrandsBrand extends Model implements HasTimeAncestors, HasKeyResultAncestors, HasDisplayName, SocialMediaAccountLinkableInterface
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
     * Gibt alle Vorfahren-Kontexte für die Zeitkaskade zurück.
     * Brand → Brand selbst (als Root)
     */
    public function timeAncestors(): array
    {
        return [];
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
     * Content Boards dieser Marke
     */
    public function contentBoards()
    {
        return $this->hasMany(BrandsContentBoard::class, 'brand_id')->orderBy('order');
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

    public function multiContentBoards()
    {
        return $this->hasMany(BrandsMultiContentBoard::class, 'brand_id')->orderBy('order');
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
}

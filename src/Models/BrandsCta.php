<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model für Brand CTAs (Call-to-Actions)
 *
 * Ein CTA gehört zu einer Brand und kann optional auf eine Zielseite (Content Board Block)
 * oder eine externe URL verweisen. CTAs werden nach Typ (primary/secondary/micro) und
 * Funnel-Stage (awareness/consideration/decision) kategorisiert.
 */
class BrandsCta extends Model implements HasDisplayName
{
    protected $table = 'brand_ctas';

    protected $fillable = [
        'uuid',
        'brand_id',
        'cta_board_id',
        'label',
        'description',
        'type',
        'funnel_stage',
        'target_page_id',
        'target_url',
        'is_active',
        'order',
        'impressions',
        'clicks',
        'last_clicked_at',
        'user_id',
        'team_id',
    ];

    protected $casts = [
        'uuid' => 'string',
        'is_active' => 'boolean',
        'order' => 'integer',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'last_clicked_at' => 'datetime',
    ];

    protected $appends = ['conversion_rate'];

    public const TYPES = ['primary', 'secondary', 'micro'];
    public const FUNNEL_STAGES = ['awareness', 'consideration', 'decision'];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $model->uuid = $uuid;
        });
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(BrandsBrand::class, 'brand_id');
    }

    public function ctaBoard(): BelongsTo
    {
        return $this->belongsTo(BrandsCtaBoard::class, 'cta_board_id');
    }

    /**
     * Optionale Zielseite.
     * @deprecated target_page_id referenzierte Content Board Blocks (Ticket #441 – deprecated).
     *             Verwende stattdessen target_url. Diese Relation liefert null bis Entfernung 2026-06-01.
     */
    public function targetPage(): BelongsTo
    {
        // Content Board Blocks wurden entfernt (Ticket #441).
        // Fallback: target_url verwenden.
        return $this->belongsTo(self::class, 'target_page_id')->withDefault(null);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }

    public function getDisplayName(): ?string
    {
        return $this->label;
    }

    /**
     * Computed: Conversion Rate (clicks / impressions)
     */
    public function getConversionRateAttribute(): float
    {
        if ($this->impressions === 0 || $this->impressions === null) {
            return 0.0;
        }

        return round($this->clicks / $this->impressions, 4);
    }

    /**
     * Resolve the redirect URL for click tracking.
     * Prefers target_url, then published_url of the target page's Content Board, then internal route.
     */
    public function getRedirectUrl(): ?string
    {
        if ($this->target_url) {
            return $this->target_url;
        }

        // Deprecated: Content Board Block target_page_id ist nicht mehr auflösbar (Ticket #441)
        return null;
    }

    /**
     * Resolve the page context URL for this CTA.
     *
     * @deprecated target_page_id referenzierte Content Board Blocks (Ticket #441 – deprecated).
     */
    public function getPageContextUrl(): ?string
    {
        // Deprecated: Content Board Block target_page_id ist nicht mehr auflösbar (Ticket #441)
        return null;
    }
}

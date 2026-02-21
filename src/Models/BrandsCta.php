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
     * Optionale Zielseite (Content Board Block)
     */
    public function targetPage(): BelongsTo
    {
        return $this->belongsTo(BrandsContentBoardBlock::class, 'target_page_id');
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

        if ($this->target_page_id && $this->targetPage) {
            // Prefer published_url from the parent Content Board if available
            $contentBoard = $this->targetPage->contentBoard;
            if ($contentBoard && $contentBoard->published_url) {
                return $contentBoard->published_url;
            }

            return route('brands.content-board-blocks.show', [
                'brandsContentBoardBlock' => $this->target_page_id,
                'type' => 'text',
            ]);
        }

        return null;
    }

    /**
     * Resolve the page context URL for this CTA.
     * Returns the published_url of the target page's Content Board if available.
     */
    public function getPageContextUrl(): ?string
    {
        if ($this->target_page_id && $this->targetPage) {
            $contentBoard = $this->targetPage->contentBoard;
            if ($contentBoard && $contentBoard->published_url) {
                return $contentBoard->published_url;
            }
        }

        return null;
    }
}

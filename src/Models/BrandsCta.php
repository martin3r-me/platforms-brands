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
        'label',
        'description',
        'type',
        'funnel_stage',
        'target_page_id',
        'target_url',
        'is_active',
        'user_id',
        'team_id',
    ];

    protected $casts = [
        'uuid' => 'string',
        'is_active' => 'boolean',
    ];

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
}

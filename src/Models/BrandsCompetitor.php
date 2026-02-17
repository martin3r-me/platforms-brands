<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model für einzelne Wettbewerber
 */
class BrandsCompetitor extends Model implements HasDisplayName
{
    protected $table = 'brands_competitors';

    protected $fillable = [
        'uuid',
        'competitor_board_id',
        'name',
        'logo_url',
        'website_url',
        'description',
        'strengths',
        'weaknesses',
        'notes',
        'position_x',
        'position_y',
        'is_own_brand',
        'differentiation',
        'order',
    ];

    protected $casts = [
        'uuid' => 'string',
        'strengths' => 'array',
        'weaknesses' => 'array',
        'differentiation' => 'array',
        'position_x' => 'integer',
        'position_y' => 'integer',
        'is_own_brand' => 'boolean',
        'order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $model->uuid = $uuid;

            if (!$model->order) {
                $maxOrder = self::where('competitor_board_id', $model->competitor_board_id)->max('order') ?? 0;
                $model->order = $maxOrder + 1;
            }
        });
    }

    public function competitorBoard(): BelongsTo
    {
        return $this->belongsTo(BrandsCompetitorBoard::class, 'competitor_board_id');
    }

    public function getDisplayName(): ?string
    {
        return $this->name;
    }
}

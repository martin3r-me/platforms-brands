<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model für SEO Keyword Position Snapshots (Ranking-History)
 */
class BrandsSeoKeywordPosition extends Model implements HasDisplayName
{
    protected $table = 'brands_seo_keyword_positions';

    protected $fillable = [
        'uuid',
        'seo_keyword_id',
        'position',
        'previous_position',
        'serp_features',
        'tracked_at',
        'search_engine',
        'device',
        'location',
    ];

    protected $casts = [
        'uuid' => 'string',
        'position' => 'integer',
        'previous_position' => 'integer',
        'serp_features' => 'array',
        'tracked_at' => 'datetime',
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

    public function seoKeyword(): BelongsTo
    {
        return $this->belongsTo(BrandsSeoKeyword::class, 'seo_keyword_id');
    }

    public function getDisplayName(): ?string
    {
        return 'Position ' . $this->position . ' @ ' . $this->tracked_at?->format('Y-m-d');
    }
}

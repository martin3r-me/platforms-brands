<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model für SEO Keyword Competitor Rankings
 */
class BrandsSeoKeywordCompetitor extends Model implements HasDisplayName
{
    protected $table = 'brands_seo_keyword_competitors';

    protected $fillable = [
        'uuid',
        'seo_keyword_id',
        'domain',
        'url',
        'position',
        'tracked_at',
    ];

    protected $casts = [
        'uuid' => 'string',
        'position' => 'integer',
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
        return $this->domain . ' @ ' . ($this->position ?? '?');
    }
}

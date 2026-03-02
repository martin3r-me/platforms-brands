<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\Uid\UuidV7;

class BrandsContentBriefRanking extends Model
{
    protected $table = 'brands_content_brief_rankings';

    protected $fillable = [
        'uuid',
        'content_brief_board_id',
        'seo_keyword_id',
        'position',
        'previous_position',
        'target_url',
        'found_url',
        'is_target_match',
        'serp_features',
        'cost_cents',
        'search_engine',
        'device',
        'location',
        'tracked_at',
    ];

    protected $casts = [
        'position' => 'integer',
        'previous_position' => 'integer',
        'is_target_match' => 'boolean',
        'serp_features' => 'array',
        'cost_cents' => 'integer',
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

    public function contentBriefBoard(): BelongsTo
    {
        return $this->belongsTo(BrandsContentBriefBoard::class, 'content_brief_board_id');
    }

    public function seoKeyword(): BelongsTo
    {
        return $this->belongsTo(BrandsSeoKeyword::class, 'seo_keyword_id');
    }

    /**
     * Position-Delta: positiv = verbessert, negativ = verschlechtert, null = keine Vergleichsdaten.
     */
    public function getPositionDeltaAttribute(): ?int
    {
        if ($this->previous_position === null || $this->position === null) {
            return null;
        }

        return $this->previous_position - $this->position; // höherer Wert = besser (z.B. 10→5 = +5)
    }

    public function scopeForBrief(Builder $query, int $briefId): Builder
    {
        return $query->where('content_brief_board_id', $briefId);
    }

    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderByDesc('tracked_at');
    }

    public function scopeOnDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('tracked_at', $date);
    }
}

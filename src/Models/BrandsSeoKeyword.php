<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model für SEO Keywords
 */
class BrandsSeoKeyword extends Model implements HasDisplayName
{
    protected $table = 'brands_seo_keywords';

    protected $fillable = [
        'uuid',
        'seo_board_id',
        'keyword_cluster_id',
        'keyword',
        'search_volume',
        'keyword_difficulty',
        'cpc_cents',
        'trend',
        'search_intent',
        'keyword_type',
        'content_idea',
        'priority',
        'url',
        'position',
        'notes',
        'order',
        'last_fetched_at',
        'dataforseo_raw',
        'user_id',
        'team_id',
    ];

    protected $casts = [
        'uuid' => 'string',
        'search_volume' => 'integer',
        'keyword_difficulty' => 'integer',
        'cpc_cents' => 'integer',
        'position' => 'integer',
        'order' => 'integer',
        'last_fetched_at' => 'datetime',
        'dataforseo_raw' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $model->uuid = $uuid;

            if (!$model->order) {
                $maxOrder = self::where('keyword_cluster_id', $model->keyword_cluster_id)
                    ->max('order') ?? 0;
                $model->order = $maxOrder + 1;
            }
        });
    }

    public function seoBoard(): BelongsTo
    {
        return $this->belongsTo(BrandsSeoBoard::class, 'seo_board_id');
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(BrandsSeoKeywordCluster::class, 'keyword_cluster_id');
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
        return $this->keyword;
    }
}

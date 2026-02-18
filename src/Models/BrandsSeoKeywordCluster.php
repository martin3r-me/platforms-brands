<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model für SEO Keyword Clusters
 *
 * Clusters sind die Spalten im SEO Board (Kanban-Layout)
 */
class BrandsSeoKeywordCluster extends Model implements HasDisplayName
{
    protected $table = 'brands_seo_keyword_clusters';

    protected $fillable = [
        'uuid',
        'seo_board_id',
        'name',
        'color',
        'order',
        'user_id',
        'team_id',
    ];

    protected $casts = [
        'uuid' => 'string',
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
                $maxOrder = self::where('seo_board_id', $model->seo_board_id)->max('order') ?? 0;
                $model->order = $maxOrder + 1;
            }
        });
    }

    public function seoBoard(): BelongsTo
    {
        return $this->belongsTo(BrandsSeoBoard::class, 'seo_board_id');
    }

    public function keywords(): HasMany
    {
        return $this->hasMany(BrandsSeoKeyword::class, 'keyword_cluster_id')->orderBy('order');
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
        return $this->name;
    }
}

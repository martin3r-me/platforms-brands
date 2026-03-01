<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

class BrandsContentBriefBoard extends Model implements HasDisplayName
{
    protected $table = 'brands_content_brief_boards';

    public const CONTENT_TYPES = [
        'pillar' => 'Pillar',
        'how-to' => 'How-To',
        'listicle' => 'Listicle',
        'faq' => 'FAQ',
        'comparison' => 'Comparison',
        'deep-dive' => 'Deep-Dive',
        'guide' => 'Guide',
    ];

    public const SEARCH_INTENTS = [
        'informational' => 'Informational',
        'commercial' => 'Commercial',
        'transactional' => 'Transactional',
        'navigational' => 'Navigational',
    ];

    public const STATUSES = [
        'draft' => 'Entwurf',
        'briefed' => 'Gebrieft',
        'in_production' => 'In Produktion',
        'review' => 'Review',
        'published' => 'Veröffentlicht',
    ];

    protected $fillable = [
        'uuid',
        'brand_id',
        'seo_board_id',
        'name',
        'description',
        'content_type',
        'search_intent',
        'status',
        'target_slug',
        'target_word_count',
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
        'order' => 'integer',
        'target_word_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $model->uuid = $uuid;

            if (!$model->order) {
                $maxOrder = self::where('brand_id', $model->brand_id)->max('order') ?? 0;
                $model->order = $maxOrder + 1;
            }
        });
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(BrandsBrand::class, 'brand_id');
    }

    public function seoBoard(): BelongsTo
    {
        return $this->belongsTo(BrandsSeoBoard::class, 'seo_board_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }

    public function outgoingLinks(): HasMany
    {
        return $this->hasMany(BrandsContentBriefLink::class, 'source_content_brief_id');
    }

    public function incomingLinks(): HasMany
    {
        return $this->hasMany(BrandsContentBriefLink::class, 'target_content_brief_id');
    }

    public function getDisplayName(): ?string
    {
        return $this->name;
    }
}

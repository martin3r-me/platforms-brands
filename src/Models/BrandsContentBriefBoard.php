<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;
use Platform\Brands\Models\BrandsLookup;

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
        'target_url',
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

    public function keywordClusters(): BelongsToMany
    {
        return $this->belongsToMany(
            BrandsSeoKeywordCluster::class,
            'brands_content_brief_keyword_clusters',
            'content_brief_id',
            'seo_keyword_cluster_id'
        )
        ->withPivot('role')
        ->withTimestamps();
    }

    public function briefKeywordClusters(): HasMany
    {
        return $this->hasMany(BrandsContentBriefKeywordCluster::class, 'content_brief_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(BrandsContentBriefSection::class, 'content_brief_id')->orderBy('order');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(BrandsContentBriefNote::class, 'content_brief_id')->orderBy('note_type')->orderBy('order');
    }

    public function rankings(): HasMany
    {
        return $this->hasMany(BrandsContentBriefRanking::class, 'content_brief_board_id')->orderByDesc('tracked_at');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(BrandsContentBriefRevision::class, 'content_brief_board_id')->orderByDesc('revised_at');
    }

    /**
     * Validiert einen Wert gegen die Lookup-Tabelle (mit Fallback auf Konstanten).
     */
    public static function isValidLookupValue(string $lookupName, string $value, int $teamId): bool
    {
        $lookup = BrandsLookup::resolve($lookupName, $teamId);

        if ($lookup) {
            return $lookup->isValidValue($value);
        }

        // Fallback auf Konstanten
        return match ($lookupName) {
            'content_type' => array_key_exists($value, self::CONTENT_TYPES),
            'search_intent' => array_key_exists($value, self::SEARCH_INTENTS),
            'content_brief_status' => array_key_exists($value, self::STATUSES),
            default => false,
        };
    }

    /**
     * Gibt die erlaubten Werte für eine Lookup zurück (Lookup-first, Fallback Konstanten).
     */
    public static function getAllowedValues(string $lookupName, int $teamId): array
    {
        $lookup = BrandsLookup::resolve($lookupName, $teamId);

        if ($lookup) {
            return array_keys($lookup->getOptionsArray());
        }

        return match ($lookupName) {
            'content_type' => array_keys(self::CONTENT_TYPES),
            'search_intent' => array_keys(self::SEARCH_INTENTS),
            'content_brief_status' => array_keys(self::STATUSES),
            default => [],
        };
    }

    public function getDisplayName(): ?string
    {
        return $this->name;
    }
}

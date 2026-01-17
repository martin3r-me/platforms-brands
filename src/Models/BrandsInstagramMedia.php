<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model für Instagram Media (Posts, Stories, Reels)
 */
class BrandsInstagramMedia extends Model implements HasDisplayName
{
    protected $table = 'brands_instagram_media';

    protected $fillable = [
        'uuid',
        'instagram_account_id',
        'external_id',
        'caption',
        'media_type',
        'media_url',
        'permalink',
        'thumbnail_url',
        'timestamp',
        'like_count',
        'comments_count',
        'is_story',
        'insights_available',
        'user_id',
    ];

    protected $casts = [
        'uuid' => 'string',
        'timestamp' => 'datetime',
        'like_count' => 'integer',
        'comments_count' => 'integer',
        'is_story' => 'boolean',
        'insights_available' => 'boolean',
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

    public function instagramAccount(): BelongsTo
    {
        return $this->belongsTo(InstagramAccount::class, 'instagram_account_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function insights(): HasMany
    {
        return $this->hasMany(BrandsInstagramMediaInsight::class, 'instagram_media_id');
    }

    public function latestInsight()
    {
        return $this->hasOne(BrandsInstagramMediaInsight::class, 'instagram_media_id')->latestOfMany('insight_date');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BrandsInstagramMediaComment::class, 'instagram_media_id');
    }

    public function hashtags()
    {
        return $this->belongsToMany(BrandsInstagramHashtag::class, 'brands_instagram_media_hashtags', 'instagram_media_id', 'hashtag_id')
            ->withPivot('count')
            ->withTimestamps();
    }

    /**
     * Beziehung zu Context Files
     */
    public function contextFiles()
    {
        return $this->hasMany(\Platform\Core\Models\ContextFile::class, 'context_id', 'id')
            ->where('context_type', static::class);
    }

    public function getDisplayName(): ?string
    {
        return $this->caption ? substr($this->caption, 0, 50) . '...' : "Media {$this->external_id}";
    }
}

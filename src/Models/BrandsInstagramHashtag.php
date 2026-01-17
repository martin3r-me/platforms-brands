<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Symfony\Component\Uid\UuidV7;

/**
 * Model für Instagram Hashtags
 */
class BrandsInstagramHashtag extends Model
{
    protected $table = 'brands_instagram_hashtags';

    protected $fillable = [
        'uuid',
        'name',
        'instagram_hashtag_id',
        'usage_count',
        'user_id',
    ];

    protected $casts = [
        'uuid' => 'string',
        'usage_count' => 'integer',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function media()
    {
        return $this->belongsToMany(BrandsInstagramMedia::class, 'brands_instagram_media_hashtags', 'hashtag_id', 'instagram_media_id')
            ->withPivot('count')
            ->withTimestamps();
    }
}

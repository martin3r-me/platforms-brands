<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model für Instagram Media Comments
 */
class BrandsInstagramMediaComment extends Model
{
    protected $table = 'brands_instagram_media_comments';

    protected $fillable = [
        'instagram_media_id',
        'external_id',
        'text',
        'username',
        'like_count',
        'timestamp',
        'user_id',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'like_count' => 'integer',
    ];

    public function instagramMedia(): BelongsTo
    {
        return $this->belongsTo(BrandsInstagramMedia::class, 'instagram_media_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }
}

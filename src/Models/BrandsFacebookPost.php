<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model für Facebook Posts
 */
class BrandsFacebookPost extends Model implements HasDisplayName
{
    protected $table = 'brands_facebook_posts';

    protected $fillable = [
        'uuid',
        'facebook_page_id',
        'external_id',
        'message',
        'story',
        'type',
        'media_url',
        'permalink_url',
        'published_at',
        'scheduled_publish_time',
        'status',
        'like_count',
        'comment_count',
        'share_count',
        'user_id',
        'team_id',
    ];

    protected $casts = [
        'uuid' => 'string',
        'published_at' => 'datetime',
        'scheduled_publish_time' => 'datetime',
        'like_count' => 'integer',
        'comment_count' => 'integer',
        'share_count' => 'integer',
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

    public function facebookPage(): BelongsTo
    {
        return $this->belongsTo(BrandsFacebookPage::class, 'facebook_page_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
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
        return $this->message 
            ? substr($this->message, 0, 50) . '...' 
            : "Post {$this->external_id}";
    }
}

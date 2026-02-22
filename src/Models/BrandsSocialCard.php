<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model für Social Cards
 * 
 * Cards sind die einzelnen Einträge in einem Slot
 */
class BrandsSocialCard extends Model implements HasDisplayName
{
    protected $table = 'brands_social_cards';

    protected $fillable = [
        'uuid',
        'social_board_id',
        'social_board_slot_id',
        'title',
        'body_md',
        'description',
        'order',
        'publish_at',
        'published_at',
        'status',
        'user_id',
        'team_id',
    ];

    protected $casts = [
        'uuid' => 'string',
        'order' => 'integer',
        'publish_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_PUBLISHING = 'publishing';
    const STATUS_PUBLISHED = 'published';
    const STATUS_FAILED = 'failed';

    const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SCHEDULED,
        self::STATUS_PUBLISHING,
        self::STATUS_PUBLISHED,
        self::STATUS_FAILED,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $model->uuid = $uuid;
            
            if (!$model->order) {
                $maxOrder = self::where('social_board_slot_id', $model->social_board_slot_id)
                    ->max('order') ?? 0;
                $model->order = $maxOrder + 1;
            }
        });
    }

    public function socialBoard(): BelongsTo
    {
        return $this->belongsTo(BrandsSocialBoard::class, 'social_board_id');
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(BrandsSocialBoardSlot::class, 'social_board_slot_id');
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
     * Contracts für diese Social Card (pro Platform-Format ein Contract).
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(BrandsSocialCardContract::class, 'social_card_id');
    }

    public function getDisplayName(): ?string
    {
        return $this->title;
    }
}

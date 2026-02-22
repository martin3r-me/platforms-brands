<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model für Social Card Contracts
 *
 * Ein Contract ist der generierte Output einer Social Card für ein bestimmtes Platform-Format.
 * Der Worker generiert Contracts gegen das Output-Schema des Formats und speichert sie hier.
 */
class BrandsSocialCardContract extends Model implements HasDisplayName
{
    protected $table = 'brands_social_card_contracts';

    protected $fillable = [
        'uuid',
        'social_card_id',
        'platform_format_id',
        'contract',
        'status',
        'published_at',
        'external_post_id',
        'error_message',
        'team_id',
    ];

    protected $casts = [
        'contract' => 'array',
        'published_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_READY = 'ready';
    const STATUS_PUBLISHED = 'published';
    const STATUS_FAILED = 'failed';

    const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_READY,
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
        });
    }

    public function socialCard(): BelongsTo
    {
        return $this->belongsTo(BrandsSocialCard::class, 'social_card_id');
    }

    public function platformFormat(): BelongsTo
    {
        return $this->belongsTo(BrandsSocialPlatformFormat::class, 'platform_format_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }

    public function getDisplayName(): ?string
    {
        return "Contract #{$this->id} ({$this->status})";
    }
}

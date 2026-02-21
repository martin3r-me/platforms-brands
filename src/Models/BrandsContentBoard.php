<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model für Content Boards
 *
 * Einfaches lineares Block-System ohne Grid-Logik.
 * Blöcke werden sequentiell angeordnet (order-Feld).
 */
class BrandsContentBoard extends Model implements HasDisplayName
{
    protected $table = 'brands_content_boards';

    protected $fillable = [
        'uuid',
        'brand_id',
        'multi_content_board_slot_id',
        'name',
        'description',
        'domain',
        'slug',
        'published_url',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(BrandsContentBoardBlock::class, 'content_board_id')->orderBy('order');
    }

    public function multiContentBoardSlot(): BelongsTo
    {
        return $this->belongsTo(BrandsMultiContentBoardSlot::class, 'multi_content_board_slot_id');
    }

    public function getDisplayName(): ?string
    {
        return $this->name;
    }
}

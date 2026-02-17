<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model für einzelne Moodboard-Bilder mit Tags und Annotationen
 */
class BrandsMoodboardImage extends Model implements HasDisplayName
{
    protected $table = 'brands_moodboard_images';

    protected $fillable = [
        'uuid',
        'moodboard_board_id',
        'title',
        'image_path',
        'annotation',
        'tags',
        'type',
        'order',
    ];

    protected $casts = [
        'uuid' => 'string',
        'tags' => 'array',
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
                $maxOrder = self::where('moodboard_board_id', $model->moodboard_board_id)->max('order') ?? 0;
                $model->order = $maxOrder + 1;
            }
        });
    }

    public function moodboardBoard(): BelongsTo
    {
        return $this->belongsTo(BrandsMoodboardBoard::class, 'moodboard_board_id');
    }

    public function getDisplayName(): ?string
    {
        return $this->title ?? 'Bild #' . $this->id;
    }
}

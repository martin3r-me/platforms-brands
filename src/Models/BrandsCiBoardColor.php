<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

class BrandsCiBoardColor extends Model implements HasDisplayName
{
    protected $table = 'brands_ci_board_colors';

    protected $fillable = [
        'uuid',
        'brand_ci_board_id',
        'title',
        'color',
        'order',
        'description',
    ];

    protected $casts = [
        'uuid' => 'string',
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
                $maxOrder = self::where('brand_ci_board_id', $model->brand_ci_board_id)->max('order') ?? 0;
                $model->order = $maxOrder + 1;
            }
        });
    }

    public function ciBoard(): BelongsTo
    {
        return $this->belongsTo(BrandsCiBoard::class, 'brand_ci_board_id');
    }

    public function getDisplayName(): ?string
    {
        return $this->title;
    }
}

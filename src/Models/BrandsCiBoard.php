<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model für Corporate Identity Boards
 * 
 * Vollständig unabhängiges Model - erbt direkt von Laravel Model
 */
class BrandsCiBoard extends Model implements HasDisplayName
{
    protected $table = 'brands_ci_boards';

    protected $fillable = [
        'uuid',
        'brand_id',
        'name',
        'description',
        'order',
        'user_id',
        'team_id',
        'done',
        'done_at',
        'primary_color',
        'secondary_color',
        'accent_color',
        'slogan',
        'font_family',
        'tagline',
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

    public function getDisplayName(): ?string
    {
        return $this->name;
    }
}

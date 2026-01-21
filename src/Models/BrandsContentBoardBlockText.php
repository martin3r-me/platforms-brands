<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Symfony\Component\Uid\UuidV7;

/**
 * Model für Content Board Block Text-Inhalte
 */
class BrandsContentBoardBlockText extends Model
{
    protected $table = 'brands_content_board_block_texts';

    protected $fillable = [
        'uuid',
        'content',
        'user_id',
        'team_id',
    ];

    protected $casts = [
        'uuid' => 'string',
        'content' => 'string',
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

    /**
     * Polymorphe Beziehung zum Block
     */
    public function block(): MorphOne
    {
        return $this->morphOne(BrandsContentBoardBlock::class, 'content', 'content_type', 'content_id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function team(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }
}

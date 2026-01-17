<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model für Content Board Blocks
 */
class BrandsContentBoardBlock extends Model implements HasDisplayName
{
    protected $table = 'brands_content_board_blocks';

    protected $fillable = [
        'uuid',
        'row_id',
        'name',
        'description',
        'order',
        'span',
        'user_id',
        'team_id',
    ];

    protected $casts = [
        'uuid' => 'string',
        'order' => 'integer',
        'span' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $model->uuid = $uuid;
            
            if (!$model->order) {
                $maxOrder = self::where('row_id', $model->row_id)->max('order') ?? 0;
                $model->order = $maxOrder + 1;
            }
            
            // Span Standardwert setzen, falls nicht gesetzt
            if (!$model->span) {
                $model->span = 1;
            }
            
            // Span validieren: muss zwischen 1 und 12 sein
            if ($model->span < 1 || $model->span > 12) {
                $model->span = max(1, min(12, $model->span));
            }
        });
        
        static::updating(function (self $model) {
            // Span validieren: muss zwischen 1 und 12 sein
            if (isset($model->span) && ($model->span < 1 || $model->span > 12)) {
                $model->span = max(1, min(12, $model->span));
            }
        });
    }

    public function row(): BelongsTo
    {
        return $this->belongsTo(BrandsContentBoardRow::class, 'row_id');
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

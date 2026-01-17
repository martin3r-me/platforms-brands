<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model für Content Board Sections
 */
class BrandsContentBoardSection extends Model implements HasDisplayName
{
    protected $table = 'brands_content_board_sections';

    protected $fillable = [
        'uuid',
        'content_board_id',
        'name',
        'description',
        'order',
        'user_id',
        'team_id',
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
                $maxOrder = self::where('content_board_id', $model->content_board_id)->max('order') ?? 0;
                $model->order = $maxOrder + 1;
            }
        });
    }

    public function contentBoard(): BelongsTo
    {
        return $this->belongsTo(BrandsContentBoard::class, 'content_board_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }

    public function rows(): HasMany
    {
        return $this->hasMany(BrandsContentBoardRow::class, 'section_id')->orderBy('order');
    }

    public function getDisplayName(): ?string
    {
        return $this->name;
    }
}

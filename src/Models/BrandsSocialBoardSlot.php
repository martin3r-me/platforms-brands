<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;

/**
 * Model für Social Board Slots
 * 
 * Slots sind die Spalten im Kanban-Board
 */
class BrandsSocialBoardSlot extends Model
{
    protected $table = 'brands_social_board_slots';

    protected $fillable = [
        'uuid',
        'social_board_id',
        'name',
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
                $maxOrder = self::where('social_board_id', $model->social_board_id)->max('order') ?? 0;
                $model->order = $maxOrder + 1;
            }
        });
    }

    public function socialBoard(): BelongsTo
    {
        return $this->belongsTo(BrandsSocialBoard::class, 'social_board_id');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(BrandsSocialCard::class, 'social_board_slot_id')->orderBy('order');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }
}

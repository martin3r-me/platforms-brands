<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;

/**
 * Model für Kanban Board Slots
 *
 * Slots sind die Spalten im Kanban-Board
 */
class BrandsKanbanBoardSlot extends Model
{
    protected $table = 'brands_kanban_board_slots';

    protected $fillable = [
        'uuid',
        'kanban_board_id',
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
                $maxOrder = self::where('kanban_board_id', $model->kanban_board_id)->max('order') ?? 0;
                $model->order = $maxOrder + 1;
            }
        });
    }

    public function kanbanBoard(): BelongsTo
    {
        return $this->belongsTo(BrandsKanbanBoard::class, 'kanban_board_id');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(BrandsKanbanCard::class, 'kanban_board_slot_id')->orderBy('order');
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

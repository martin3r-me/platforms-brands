<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;
use Platform\Core\Traits\HasExtraFields;

/**
 * Model für Kanban Cards
 *
 * Cards sind die einzelnen Einträge in einem Slot
 */
class BrandsKanbanCard extends Model implements HasDisplayName
{
    use HasExtraFields;
    protected $table = 'brands_kanban_cards';

    protected $fillable = [
        'uuid',
        'kanban_board_id',
        'kanban_board_slot_id',
        'title',
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
                $maxOrder = self::where('kanban_board_slot_id', $model->kanban_board_slot_id)
                    ->max('order') ?? 0;
                $model->order = $maxOrder + 1;
            }
        });
    }

    public function kanbanBoard(): BelongsTo
    {
        return $this->belongsTo(BrandsKanbanBoard::class, 'kanban_board_id');
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(BrandsKanbanBoardSlot::class, 'kanban_board_slot_id');
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
        return $this->title;
    }
}

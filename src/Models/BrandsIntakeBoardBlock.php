<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Platform\ActivityLog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;

class BrandsIntakeBoardBlock extends Model
{
    use LogsActivity;

    protected $table = 'brands_intake_board_blocks';

    protected $fillable = [
        'uuid',
        'intake_board_id',
        'block_definition_id',
        'sort_order',
        'is_required',
        'is_active',
        'user_id',
        'team_id',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                do {
                    $uuid = UuidV7::generate();
                } while (self::where('uuid', $uuid)->exists());

                $model->uuid = $uuid;
            }
        });
    }

    /**
     * Beziehungen
     */
    public function intakeBoard(): BelongsTo
    {
        return $this->belongsTo(BrandsIntakeBoard::class, 'intake_board_id');
    }

    public function blockDefinition(): BelongsTo
    {
        return $this->belongsTo(BrandsIntakeBlockDefinition::class, 'block_definition_id');
    }

    public function intakeSteps(): HasMany
    {
        return $this->hasMany(BrandsIntakeStep::class, 'board_block_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }
}

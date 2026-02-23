<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Platform\ActivityLog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;

class BrandsIntakeStep extends Model
{
    use LogsActivity;

    protected $table = 'brands_intake_steps';

    protected $fillable = [
        'uuid',
        'session_id',
        'board_block_id',
        'block_definition_id',
        'answers',
        'ai_interpretation',
        'ai_confidence',
        'ai_suggestions',
        'user_clarification_needed',
        'conversation_context',
        'message_count',
        'clarification_attempts',
        'is_completed',
        'completed_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'ai_interpretation' => 'array',
        'ai_suggestions' => 'array',
        'ai_confidence' => 'decimal:2',
        'conversation_context' => 'array',
        'message_count' => 'integer',
        'clarification_attempts' => 'integer',
        'user_clarification_needed' => 'boolean',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
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
    public function session(): BelongsTo
    {
        return $this->belongsTo(BrandsIntakeSession::class, 'session_id');
    }

    public function boardBlock(): BelongsTo
    {
        return $this->belongsTo(BrandsIntakeBoardBlock::class, 'board_block_id');
    }

    public function blockDefinition(): BelongsTo
    {
        return $this->belongsTo(BrandsIntakeBlockDefinition::class, 'block_definition_id');
    }

    /**
     * Scopes
     */
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_completed', false);
    }

    public function scopeNeedsClarification($query)
    {
        return $query->where('user_clarification_needed', true);
    }

    public function scopeByConfidence($query, $minScore)
    {
        return $query->where('ai_confidence', '>=', $minScore);
    }
}

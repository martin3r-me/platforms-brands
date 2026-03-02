<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\Uid\UuidV7;

class BrandsContentBriefRevision extends Model
{
    protected $table = 'brands_content_brief_revisions';

    protected $fillable = [
        'uuid',
        'content_brief_board_id',
        'revision_type',
        'summary',
        'metrics_before',
        'metrics_after',
        'changes',
        'user_id',
        'revised_at',
    ];

    protected $casts = [
        'metrics_before' => 'array',
        'metrics_after' => 'array',
        'changes' => 'array',
        'revised_at' => 'datetime',
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

    public function contentBriefBoard(): BelongsTo
    {
        return $this->belongsTo(BrandsContentBriefBoard::class, 'content_brief_board_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    /**
     * Berechnet Metriken-Deltas (after - before).
     */
    public function getMetricsDeltaAttribute(): ?array
    {
        if (!$this->metrics_before || !$this->metrics_after) {
            return null;
        }

        $delta = [];
        foreach ($this->metrics_after as $key => $afterVal) {
            $beforeVal = $this->metrics_before[$key] ?? 0;
            $delta[$key] = $afterVal - $beforeVal;
        }

        return $delta;
    }

    public function scopeForBrief(Builder $query, int $briefId): Builder
    {
        return $query->where('content_brief_board_id', $briefId);
    }

    public function scopeChronological(Builder $query): Builder
    {
        return $query->orderBy('revised_at');
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('revised_at');
    }
}

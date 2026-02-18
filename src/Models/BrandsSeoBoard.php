<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model für SEO Boards
 */
class BrandsSeoBoard extends Model implements HasDisplayName
{
    protected $table = 'brands_seo_boards';

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
        'budget_limit_cents',
        'budget_spent_cents',
        'budget_reset_at',
        'refresh_interval_days',
        'last_refreshed_at',
        'dataforseo_config',
    ];

    protected $casts = [
        'uuid' => 'string',
        'done' => 'boolean',
        'done_at' => 'datetime',
        'order' => 'integer',
        'budget_limit_cents' => 'integer',
        'budget_spent_cents' => 'integer',
        'budget_reset_at' => 'datetime',
        'refresh_interval_days' => 'integer',
        'last_refreshed_at' => 'datetime',
        'dataforseo_config' => 'array',
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

    public function keywordClusters(): HasMany
    {
        return $this->hasMany(BrandsSeoKeywordCluster::class, 'seo_board_id')->orderBy('order');
    }

    public function keywords(): HasMany
    {
        return $this->hasMany(BrandsSeoKeyword::class, 'seo_board_id');
    }

    public function budgetLogs(): HasMany
    {
        return $this->hasMany(BrandsSeoBudgetLog::class, 'seo_board_id');
    }

    public function getBudgetRemainingCentsAttribute(): int
    {
        if ($this->budget_limit_cents === null) {
            return PHP_INT_MAX;
        }

        return max(0, $this->budget_limit_cents - $this->budget_spent_cents);
    }

    public function getBudgetPercentageAttribute(): ?float
    {
        if ($this->budget_limit_cents === null || $this->budget_limit_cents === 0) {
            return null;
        }

        return round(($this->budget_spent_cents / $this->budget_limit_cents) * 100, 1);
    }

    public function isRefreshDue(): bool
    {
        if (!$this->last_refreshed_at) {
            return true;
        }

        return $this->last_refreshed_at->addDays($this->refresh_interval_days)->isPast();
    }

    public function getDisplayName(): ?string
    {
        return $this->name;
    }
}

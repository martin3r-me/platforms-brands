<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model für SEO Budget Logs
 */
class BrandsSeoBudgetLog extends Model implements HasDisplayName
{
    protected $table = 'brands_seo_budget_logs';

    protected $fillable = [
        'uuid',
        'seo_board_id',
        'action',
        'keywords_count',
        'cost_cents',
        'user_id',
        'fetched_at',
    ];

    protected $casts = [
        'uuid' => 'string',
        'keywords_count' => 'integer',
        'cost_cents' => 'integer',
        'fetched_at' => 'datetime',
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

    public function seoBoard(): BelongsTo
    {
        return $this->belongsTo(BrandsSeoBoard::class, 'seo_board_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function getDisplayName(): ?string
    {
        return $this->action . ' (' . $this->keywords_count . ' Keywords)';
    }
}

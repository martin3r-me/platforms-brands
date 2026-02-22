<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model für einzelne Wettbewerber
 */
class BrandsCompetitor extends Model implements HasDisplayName
{
    protected $table = 'brands_competitors';

    protected $fillable = [
        'uuid',
        'competitor_board_id',
        'name',
        'logo_url',
        'website_url',
        'description',
        'strengths',
        'weaknesses',
        'notes',
        'position_x',
        'position_y',
        'is_own_brand',
        'differentiation',
        'order',
    ];

    protected $casts = [
        'uuid' => 'string',
        'position_x' => 'integer',
        'position_y' => 'integer',
        'is_own_brand' => 'boolean',
        'order' => 'integer',
    ];

    public function getStrengthsAttribute($value): array
    {
        $decoded = $this->fromJson($value);
        return is_array($decoded) ? $decoded : [];
    }

    public function getWeaknessesAttribute($value): array
    {
        $decoded = $this->fromJson($value);
        return is_array($decoded) ? $decoded : [];
    }

    public function getDifferentiationAttribute($value): array
    {
        $decoded = $this->fromJson($value);
        return is_array($decoded) ? $decoded : [];
    }

    public function setStrengthsAttribute($value): void
    {
        $this->attributes['strengths'] = is_array($value) ? $this->asJson($value) : $value;
    }

    public function setWeaknessesAttribute($value): void
    {
        $this->attributes['weaknesses'] = is_array($value) ? $this->asJson($value) : $value;
    }

    public function setDifferentiationAttribute($value): void
    {
        $this->attributes['differentiation'] = is_array($value) ? $this->asJson($value) : $value;
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $model->uuid = $uuid;

            if (!$model->order) {
                $maxOrder = self::where('competitor_board_id', $model->competitor_board_id)->max('order') ?? 0;
                $model->order = $maxOrder + 1;
            }
        });
    }

    public function competitorBoard(): BelongsTo
    {
        return $this->belongsTo(BrandsCompetitorBoard::class, 'competitor_board_id');
    }

    public function getDisplayName(): ?string
    {
        return $this->name;
    }
}

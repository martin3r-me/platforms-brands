<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model für Guideline-Kapitel (Wiki-artige Struktur)
 */
class BrandsGuidelineChapter extends Model implements HasDisplayName
{
    protected $table = 'brands_guideline_chapters';

    protected $fillable = [
        'uuid',
        'guideline_board_id',
        'title',
        'description',
        'icon',
        'order',
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
                $maxOrder = self::where('guideline_board_id', $model->guideline_board_id)->max('order') ?? 0;
                $model->order = $maxOrder + 1;
            }
        });
    }

    public function guidelineBoard(): BelongsTo
    {
        return $this->belongsTo(BrandsGuidelineBoard::class, 'guideline_board_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(BrandsGuidelineEntry::class, 'guideline_chapter_id')->orderBy('order');
    }

    public function getDisplayName(): ?string
    {
        return $this->title;
    }
}

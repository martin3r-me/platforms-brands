<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model für einzelne Guideline-Einträge (Regeln mit Do/Don't)
 */
class BrandsGuidelineEntry extends Model implements HasDisplayName
{
    protected $table = 'brands_guideline_entries';

    protected $fillable = [
        'uuid',
        'guideline_chapter_id',
        'title',
        'rule_text',
        'rationale',
        'do_example',
        'dont_example',
        'do_image_path',
        'dont_image_path',
        'cross_references',
        'order',
    ];

    protected $casts = [
        'uuid' => 'string',
        'cross_references' => 'array',
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
                $maxOrder = self::where('guideline_chapter_id', $model->guideline_chapter_id)->max('order') ?? 0;
                $model->order = $maxOrder + 1;
            }
        });
    }

    public function guidelineChapter(): BelongsTo
    {
        return $this->belongsTo(BrandsGuidelineChapter::class, 'guideline_chapter_id');
    }

    public function getDisplayName(): ?string
    {
        return $this->title;
    }
}

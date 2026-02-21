<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model für SEO Keyword Context-Links (lose Kopplung zu Content Board Blocks, Notes, URLs etc.)
 */
class BrandsSeoKeywordContext extends Model implements HasDisplayName
{
    protected $table = 'brands_seo_keyword_contexts';

    protected $fillable = [
        'uuid',
        'seo_keyword_id',
        'context_type',
        'context_id',
        'label',
        'url',
        'meta',
    ];

    protected $casts = [
        'uuid' => 'string',
        'context_id' => 'integer',
        'meta' => 'array',
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

    public function seoKeyword(): BelongsTo
    {
        return $this->belongsTo(BrandsSeoKeyword::class, 'seo_keyword_id');
    }

    public function getDisplayName(): ?string
    {
        return $this->label ?? ($this->context_type . ':' . $this->context_id);
    }
}

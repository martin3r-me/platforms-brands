<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Eine bewertete Website-Referenz: Domain + Verdikt (gefällt/gefällt nicht) + Begründung + Aspekt-Tags.
 */
class BrandsReference extends Model implements HasDisplayName
{
    protected $table = 'brands_references';

    protected $fillable = [
        'uuid',
        'reference_board_id',
        'url',
        'title',
        'screenshot_url',
        'verdict',
        'reason',
        'aspects',
        'industry',
        'order',
    ];

    protected $casts = [
        'uuid' => 'string',
        'aspects' => 'array',
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
                $maxOrder = self::where('reference_board_id', $model->reference_board_id)->max('order') ?? 0;
                $model->order = $maxOrder + 1;
            }
        });
    }

    public function referenceBoard(): BelongsTo
    {
        return $this->belongsTo(BrandsReferenceBoard::class, 'reference_board_id');
    }

    /** Host-Teil der URL für die Anzeige (ohne Protokoll/Pfad). */
    public function getHostAttribute(): string
    {
        $host = parse_url($this->url ?? '', PHP_URL_HOST) ?: ($this->url ?? '');
        return preg_replace('/^www\./', '', $host);
    }

    public function getDisplayName(): ?string
    {
        return $this->title ?: $this->host;
    }
}

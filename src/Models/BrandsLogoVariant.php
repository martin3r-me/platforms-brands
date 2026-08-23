<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;
use Platform\Core\Traits\HasContextFileReferences;

/**
 * Model für einzelne Logo-Varianten (Primary, Secondary, Monochrome, Favicon, Icon, etc.)
 */
class BrandsLogoVariant extends Model implements HasDisplayName
{
    use HasContextFileReferences;

    protected $table = 'brands_logo_variants';

    protected $fillable = [
        'uuid',
        'logo_board_id',
        'name',
        'type',
        'description',
        'usage_guidelines',
        'file_path',
        'file_name',
        'file_format',
        'additional_formats',
        'clearspace_factor',
        'min_width_px',
        'min_width_mm',
        'background_color',
        'dos',
        'donts',
        'order',
    ];

    protected $casts = [
        'uuid' => 'string',
        'additional_formats' => 'array',
        'dos' => 'array',
        'donts' => 'array',
        'clearspace_factor' => 'decimal:2',
        'min_width_px' => 'integer',
        'min_width_mm' => 'integer',
        'order' => 'integer',
    ];

    public const TYPES = [
        'primary' => 'Primary Logo',
        'secondary' => 'Secondary Logo',
        'monochrome' => 'Monochrome',
        'favicon' => 'Favicon',
        'icon' => 'Icon',
        'wordmark' => 'Wortmarke',
        'pictorial_mark' => 'Bildmarke',
        'combination_mark' => 'Kombinationsmarke',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $model->uuid = $uuid;

            if (!$model->order) {
                $maxOrder = self::where('logo_board_id', $model->logo_board_id)->max('order') ?? 0;
                $model->order = $maxOrder + 1;
            }
        });
    }

    public function logoBoard(): BelongsTo
    {
        return $this->belongsTo(BrandsLogoBoard::class, 'logo_board_id');
    }

    public function getDisplayName(): ?string
    {
        return $this->name;
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Gibt die URL zur Hauptdatei zurück — bevorzugt das ContextFile,
     * Fallback auf das alte public-Storage-Feld (Legacy-Daten).
     */
    public function getFileUrlAttribute(): ?string
    {
        $ref = $this->getOrderedFileReferences()->first();
        if ($ref && $ref->url) {
            return $ref->url;
        }

        if ($this->file_path) {
            return asset('storage/' . $this->file_path);
        }

        return null;
    }

    /**
     * Prüft ob die Hauptdatei ein SVG ist (für Inline-Rendering)
     */
    public function getIsSvgAttribute(): bool
    {
        return $this->file_format === 'svg';
    }
}

<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;
use Platform\Core\Traits\HasContextFileReferences;

/**
 * Model für einzelne Assets mit Typ, Dateien, Tags und Versionierung
 */
class BrandsAsset extends Model implements HasDisplayName
{
    use HasContextFileReferences;

    protected $table = 'brands_assets';

    protected $fillable = [
        'uuid',
        'asset_board_id',
        'name',
        'description',
        'asset_type',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'thumbnail_path',
        'tags',
        'available_formats',
        'current_version',
        'order',
    ];

    protected $casts = [
        'uuid' => 'string',
        'tags' => 'array',
        'available_formats' => 'array',
        'file_size' => 'integer',
        'current_version' => 'integer',
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
                $maxOrder = self::where('asset_board_id', $model->asset_board_id)->max('order') ?? 0;
                $model->order = $maxOrder + 1;
            }
        });
    }

    public function assetBoard(): BelongsTo
    {
        return $this->belongsTo(BrandsAssetBoard::class, 'asset_board_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(BrandsAssetVersion::class, 'asset_id')->orderByDesc('version_number');
    }

    public function getDisplayName(): ?string
    {
        return $this->name ?? 'Asset #' . $this->id;
    }

    /**
     * URL zur Datei — bevorzugt das ContextFile, Fallback auf das alte
     * public-Storage-Feld (Legacy-Daten).
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
     * Ist die Datei ein Bild (inkl. SVG) — für Inline-Vorschau.
     */
    public function getIsImageAttribute(): bool
    {
        return $this->mime_type && str_starts_with($this->mime_type, 'image/');
    }

    public function getIsSvgAttribute(): bool
    {
        return in_array($this->mime_type, ['image/svg+xml', 'image/svg'], true);
    }

    /**
     * Gibt den lesbaren Asset-Typ zurück
     */
    public function getAssetTypeLabel(): string
    {
        return match ($this->asset_type) {
            'sm_template' => 'Social Media Template',
            'letterhead' => 'Briefkopf',
            'signature' => 'E-Mail-Signatur',
            'banner' => 'Banner',
            'presentation' => 'Präsentation',
            'other' => 'Sonstiges',
            default => $this->asset_type,
        };
    }
}

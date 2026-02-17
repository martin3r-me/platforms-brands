<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model für Asset-Versionen – Ältere Versionen eines Assets aufbewahren
 */
class BrandsAssetVersion extends Model implements HasDisplayName
{
    protected $table = 'brands_asset_versions';

    protected $fillable = [
        'uuid',
        'asset_id',
        'version_number',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'change_note',
        'user_id',
    ];

    protected $casts = [
        'uuid' => 'string',
        'version_number' => 'integer',
        'file_size' => 'integer',
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

    public function asset(): BelongsTo
    {
        return $this->belongsTo(BrandsAsset::class, 'asset_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function getDisplayName(): ?string
    {
        return 'Version ' . $this->version_number;
    }
}

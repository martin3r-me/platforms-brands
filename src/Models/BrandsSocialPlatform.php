<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Lookup-Tabelle für Social-Media-Plattformen.
 *
 * Lose Lookup-Tabelle — kein Enum, keine Hardcodierung. Neue Plattformen
 * werden zur Laufzeit hinzugefügt ohne Code-Deployment.
 */
class BrandsSocialPlatform extends Model implements HasDisplayName
{
    protected $table = 'brands_social_platforms';

    protected $fillable = [
        'name',
        'key',
        'is_active',
        'team_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function formats(): HasMany
    {
        return $this->hasMany(BrandsSocialPlatformFormat::class, 'platform_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }

    public function getDisplayName(): ?string
    {
        return $this->name;
    }
}

<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Lookup-Tabelle für Formate einer Social-Media-Plattform.
 *
 * Jedes Format gehört zu einer Plattform (z.B. Instagram → Story, Post, Reel).
 * Lose Lookup-Tabelle — neue Formate werden zur Laufzeit hinzugefügt ohne Code-Deployment.
 */
class BrandsSocialPlatformFormat extends Model implements HasDisplayName
{
    protected $table = 'brands_social_platform_formats';

    protected $fillable = [
        'platform_id',
        'name',
        'key',
        'aspect_ratio',
        'media_type',
        'is_active',
        'team_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function platform(): BelongsTo
    {
        return $this->belongsTo(BrandsSocialPlatform::class, 'platform_id');
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

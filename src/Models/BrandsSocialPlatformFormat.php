<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'output_schema',
        'rules',
        'is_active',
        'team_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'output_schema' => 'array',
        'rules' => 'array',
    ];

    public function platform(): BelongsTo
    {
        return $this->belongsTo(BrandsSocialPlatform::class, 'platform_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }

    /**
     * Verknüpfte Personas (Audience-Kontext).
     * Mehrere Personas pro Format möglich (z.B. TikTok → "Gen Z" + "Early Adopter").
     */
    public function personas(): BelongsToMany
    {
        return $this->belongsToMany(
            BrandsPersona::class,
            'brands_social_platform_format_personas',
            'platform_format_id',
            'persona_id'
        )
        ->withPivot('notes')
        ->withTimestamps();
    }

    /**
     * Pivot-Records für Persona-Verknüpfungen (für direkte Queries).
     */
    public function formatPersonas(): HasMany
    {
        return $this->hasMany(BrandsSocialPlatformFormatPersona::class, 'platform_format_id');
    }

    public function getDisplayName(): ?string
    {
        return $this->name;
    }
}

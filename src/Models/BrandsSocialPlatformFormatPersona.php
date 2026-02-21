<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Pivot-Model für die Verknüpfung zwischen Platform-Formaten und Personas.
 *
 * Jedes Format kann mehrere Personas als Audience-Kontext haben (z.B. TikTok → "Gen Z" + "Early Adopter").
 * Der Worker nutzt die verknüpften Personas, um Ton, Wortwahl und Komplexität automatisch anzupassen.
 */
class BrandsSocialPlatformFormatPersona extends Model implements HasDisplayName
{
    protected $table = 'brands_social_platform_format_personas';

    protected $fillable = [
        'platform_format_id',
        'persona_id',
        'notes',
    ];

    public function platformFormat(): BelongsTo
    {
        return $this->belongsTo(BrandsSocialPlatformFormat::class, 'platform_format_id');
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(BrandsPersona::class, 'persona_id');
    }

    public function getDisplayName(): ?string
    {
        return $this->persona?->name;
    }
}

<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Geordnete Gliederungsabschnitte innerhalb eines Content Briefs.
 *
 * Struktur-Skelett, KEIN Fließtext – das ist der zentrale Unterschied
 * zu content_board_blocks. Hier wird nur die Outline definiert:
 * Überschrift, Heading-Level, Beschreibung, Ziel-Keywords und Hinweise.
 */
class BrandsContentBriefSection extends Model implements HasDisplayName
{
    protected $table = 'brands_content_brief_sections';

    public const HEADING_LEVELS = [
        'h2' => 'H2',
        'h3' => 'H3',
        'h4' => 'H4',
    ];

    protected $fillable = [
        'content_brief_id',
        'order',
        'heading',
        'heading_level',
        'description',
        'target_keywords',
        'notes',
        'user_id',
        'team_id',
    ];

    protected $casts = [
        'order' => 'integer',
        'target_keywords' => 'array',
        'content_brief_id' => 'integer',
    ];

    public function contentBrief(): BelongsTo
    {
        return $this->belongsTo(BrandsContentBriefBoard::class, 'content_brief_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }

    public function getDisplayName(): ?string
    {
        return $this->heading;
    }
}

<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Freitext-Notizen und strukturierte Anweisungen pro Content Brief.
 *
 * Jede Note hat einen Typ (instruction, source, constraint, example, avoid)
 * und dient als Briefing-Information für Texter und LLMs.
 */
class BrandsContentBriefNote extends Model implements HasDisplayName
{
    protected $table = 'brands_content_brief_notes';

    public const NOTE_TYPES = [
        'instruction' => 'Anweisung',
        'source' => 'Quelle',
        'constraint' => 'Einschränkung',
        'example' => 'Beispiel',
        'avoid' => 'Vermeiden',
    ];

    protected $fillable = [
        'content_brief_id',
        'note_type',
        'content',
        'order',
        'user_id',
        'team_id',
    ];

    protected $casts = [
        'order' => 'integer',
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
        $label = self::NOTE_TYPES[$this->note_type] ?? $this->note_type;
        return $label . ': ' . mb_substr($this->content, 0, 50);
    }
}

<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Core\Contracts\HasDisplayName;

class BrandsContentBriefLink extends Model implements HasDisplayName
{
    protected $table = 'brands_content_brief_links';

    public const LINK_TYPES = [
        'pillar_to_cluster' => 'Pillar → Cluster',
        'cluster_to_pillar' => 'Cluster → Pillar',
        'related' => 'Verwandt',
        'see_also' => 'Siehe auch',
    ];

    protected $fillable = [
        'source_content_brief_id',
        'target_content_brief_id',
        'link_type',
        'anchor_hint',
        'user_id',
        'team_id',
    ];

    protected $casts = [
        'source_content_brief_id' => 'integer',
        'target_content_brief_id' => 'integer',
    ];

    public function sourceContentBrief(): BelongsTo
    {
        return $this->belongsTo(BrandsContentBriefBoard::class, 'source_content_brief_id');
    }

    public function targetContentBrief(): BelongsTo
    {
        return $this->belongsTo(BrandsContentBriefBoard::class, 'target_content_brief_id');
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
        return $this->link_type . ': ' . ($this->sourceContentBrief?->name ?? '?') . ' → ' . ($this->targetContentBrief?->name ?? '?');
    }
}

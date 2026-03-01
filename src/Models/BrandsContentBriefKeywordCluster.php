<?php

namespace Platform\Brands\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Pivot-Model für die m:n-Verknüpfung zwischen Content Brief und SEO Keyword Cluster.
 *
 * Jeder Content Brief hat genau 1 primary Cluster (treibendes Cluster) und
 * beliebig viele secondary/supporting Cluster für zusätzliche Keyword-Abdeckung.
 */
class BrandsContentBriefKeywordCluster extends Model implements HasDisplayName
{
    protected $table = 'brands_content_brief_keyword_clusters';

    public const ROLES = [
        'primary' => 'Primary',
        'secondary' => 'Secondary',
        'supporting' => 'Supporting',
    ];

    protected $fillable = [
        'content_brief_id',
        'seo_keyword_cluster_id',
        'role',
    ];

    public function contentBrief(): BelongsTo
    {
        return $this->belongsTo(BrandsContentBriefBoard::class, 'content_brief_id');
    }

    public function keywordCluster(): BelongsTo
    {
        return $this->belongsTo(BrandsSeoKeywordCluster::class, 'seo_keyword_cluster_id');
    }

    public function getDisplayName(): ?string
    {
        return $this->keywordCluster?->name;
    }
}

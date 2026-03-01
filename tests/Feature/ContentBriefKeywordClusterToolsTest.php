<?php

namespace Platform\Brands\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsContentBriefBoard;
use Platform\Brands\Models\BrandsContentBriefKeywordCluster;
use Platform\Brands\Models\BrandsSeoBoard;
use Platform\Brands\Models\BrandsSeoKeywordCluster;
use Platform\Brands\Models\BrandsSeoKeyword;
use Platform\Brands\Tools\CreateContentBriefKeywordClusterTool;
use Platform\Brands\Tools\ListContentBriefKeywordClustersTool;
use Platform\Brands\Tools\DeleteContentBriefKeywordClusterTool;
use Platform\Brands\Tools\GetContentBriefBoardTool;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Models\User;
use Platform\Core\Models\Team;

class ContentBriefKeywordClusterToolsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Team $team;
    protected BrandsBrand $brand;
    protected ToolContext $context;
    protected BrandsContentBriefBoard $contentBrief;
    protected BrandsSeoBoard $seoBoard;
    protected BrandsSeoKeywordCluster $clusterA;
    protected BrandsSeoKeywordCluster $clusterB;
    protected BrandsSeoKeywordCluster $clusterC;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::factory()->create();
        $this->user = User::factory()->create();
        $this->user->currentTeam = $this->team;

        $this->brand = BrandsBrand::create([
            'name' => 'Test Brand',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $this->seoBoard = BrandsSeoBoard::create([
            'name' => 'Test SEO Board',
            'brand_id' => $this->brand->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $this->contentBrief = BrandsContentBriefBoard::create([
            'name' => 'Test Content Brief',
            'content_type' => 'pillar',
            'search_intent' => 'informational',
            'status' => 'draft',
            'brand_id' => $this->brand->id,
            'seo_board_id' => $this->seoBoard->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $this->clusterA = BrandsSeoKeywordCluster::create([
            'name' => 'Cluster A - Content Marketing',
            'seo_board_id' => $this->seoBoard->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $this->clusterB = BrandsSeoKeywordCluster::create([
            'name' => 'Cluster B - SEO Basics',
            'seo_board_id' => $this->seoBoard->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $this->clusterC = BrandsSeoKeywordCluster::create([
            'name' => 'Cluster C - Social Media',
            'seo_board_id' => $this->seoBoard->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        // Add keywords to clusters for metrics testing
        BrandsSeoKeyword::create([
            'keyword' => 'content marketing',
            'search_volume' => 5000,
            'keyword_difficulty' => 60,
            'cpc_cents' => 250,
            'search_intent' => 'informational',
            'keyword_cluster_id' => $this->clusterA->id,
            'seo_board_id' => $this->seoBoard->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        BrandsSeoKeyword::create([
            'keyword' => 'content strategy',
            'search_volume' => 3000,
            'keyword_difficulty' => 50,
            'cpc_cents' => 200,
            'search_intent' => 'informational',
            'keyword_cluster_id' => $this->clusterA->id,
            'seo_board_id' => $this->seoBoard->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $this->context = new ToolContext(user: $this->user);
    }

    // ─── CREATE KEYWORD CLUSTER LINK ─────────────────────

    /** @test */
    public function it_creates_a_keyword_cluster_link_with_primary_role()
    {
        $tool = new CreateContentBriefKeywordClusterTool();
        $result = $tool->execute([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterA->id,
            'role' => 'primary',
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals($this->contentBrief->id, $result->data['content_brief_id']);
        $this->assertEquals($this->clusterA->id, $result->data['seo_keyword_cluster_id']);
        $this->assertEquals('primary', $result->data['role']);
        $this->assertEquals('Primary', $result->data['role_label']);
        $this->assertNotNull($result->data['created_at']);
    }

    /** @test */
    public function it_creates_a_keyword_cluster_link_with_secondary_role()
    {
        $tool = new CreateContentBriefKeywordClusterTool();
        $result = $tool->execute([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterA->id,
            'role' => 'secondary',
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals('secondary', $result->data['role']);
        $this->assertEquals('Secondary', $result->data['role_label']);
    }

    /** @test */
    public function it_creates_a_keyword_cluster_link_with_supporting_role()
    {
        $tool = new CreateContentBriefKeywordClusterTool();
        $result = $tool->execute([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterA->id,
            'role' => 'supporting',
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals('supporting', $result->data['role']);
        $this->assertEquals('Supporting', $result->data['role_label']);
    }

    /** @test */
    public function it_enforces_single_primary_per_content_brief()
    {
        // First primary
        BrandsContentBriefKeywordCluster::create([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterA->id,
            'role' => 'primary',
        ]);

        // Second primary should fail
        $tool = new CreateContentBriefKeywordClusterTool();
        $result = $tool->execute([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterB->id,
            'role' => 'primary',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('PRIMARY_ALREADY_EXISTS', $result->errorCode);
    }

    /** @test */
    public function it_allows_multiple_secondary_clusters()
    {
        BrandsContentBriefKeywordCluster::create([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterA->id,
            'role' => 'secondary',
        ]);

        $tool = new CreateContentBriefKeywordClusterTool();
        $result = $tool->execute([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterB->id,
            'role' => 'secondary',
        ], $this->context);

        $this->assertTrue($result->success);
    }

    /** @test */
    public function it_allows_multiple_supporting_clusters()
    {
        BrandsContentBriefKeywordCluster::create([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterA->id,
            'role' => 'supporting',
        ]);

        $tool = new CreateContentBriefKeywordClusterTool();
        $result = $tool->execute([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterB->id,
            'role' => 'supporting',
        ], $this->context);

        $this->assertTrue($result->success);
    }

    /** @test */
    public function it_rejects_duplicate_cluster_link()
    {
        BrandsContentBriefKeywordCluster::create([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterA->id,
            'role' => 'secondary',
        ]);

        $tool = new CreateContentBriefKeywordClusterTool();
        $result = $tool->execute([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterA->id,
            'role' => 'supporting', // different role but same cluster
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('DUPLICATE_LINK', $result->errorCode);
    }

    /** @test */
    public function it_rejects_invalid_role()
    {
        $tool = new CreateContentBriefKeywordClusterTool();
        $result = $tool->execute([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterA->id,
            'role' => 'invalid_role',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    /** @test */
    public function it_requires_content_brief_id()
    {
        $tool = new CreateContentBriefKeywordClusterTool();
        $result = $tool->execute([
            'seo_keyword_cluster_id' => $this->clusterA->id,
            'role' => 'primary',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    /** @test */
    public function it_requires_seo_keyword_cluster_id()
    {
        $tool = new CreateContentBriefKeywordClusterTool();
        $result = $tool->execute([
            'content_brief_id' => $this->contentBrief->id,
            'role' => 'primary',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    /** @test */
    public function it_requires_role()
    {
        $tool = new CreateContentBriefKeywordClusterTool();
        $result = $tool->execute([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterA->id,
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    /** @test */
    public function it_rejects_nonexistent_content_brief()
    {
        $tool = new CreateContentBriefKeywordClusterTool();
        $result = $tool->execute([
            'content_brief_id' => 99999,
            'seo_keyword_cluster_id' => $this->clusterA->id,
            'role' => 'primary',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('CONTENT_BRIEF_BOARD_NOT_FOUND', $result->errorCode);
    }

    /** @test */
    public function it_rejects_nonexistent_keyword_cluster()
    {
        $tool = new CreateContentBriefKeywordClusterTool();
        $result = $tool->execute([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => 99999,
            'role' => 'primary',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('KEYWORD_CLUSTER_NOT_FOUND', $result->errorCode);
    }

    // ─── LIST KEYWORD CLUSTERS ───────────────────────────

    /** @test */
    public function it_lists_keyword_clusters_for_content_brief()
    {
        BrandsContentBriefKeywordCluster::create([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterA->id,
            'role' => 'primary',
        ]);
        BrandsContentBriefKeywordCluster::create([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterB->id,
            'role' => 'secondary',
        ]);

        $tool = new ListContentBriefKeywordClustersTool();
        $result = $tool->execute([
            'content_brief_id' => $this->contentBrief->id,
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals(2, $result->data['count']);
        $this->assertCount(2, $result->data['clusters']);
    }

    /** @test */
    public function it_lists_clusters_ordered_by_role_priority()
    {
        BrandsContentBriefKeywordCluster::create([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterC->id,
            'role' => 'supporting',
        ]);
        BrandsContentBriefKeywordCluster::create([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterA->id,
            'role' => 'primary',
        ]);
        BrandsContentBriefKeywordCluster::create([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterB->id,
            'role' => 'secondary',
        ]);

        $tool = new ListContentBriefKeywordClustersTool();
        $result = $tool->execute([
            'content_brief_id' => $this->contentBrief->id,
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals('primary', $result->data['clusters'][0]['role']);
        $this->assertEquals('secondary', $result->data['clusters'][1]['role']);
        $this->assertEquals('supporting', $result->data['clusters'][2]['role']);
    }

    /** @test */
    public function it_includes_keywords_and_metrics_in_list()
    {
        BrandsContentBriefKeywordCluster::create([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterA->id,
            'role' => 'primary',
        ]);

        $tool = new ListContentBriefKeywordClustersTool();
        $result = $tool->execute([
            'content_brief_id' => $this->contentBrief->id,
        ], $this->context);

        $this->assertTrue($result->success);
        $cluster = $result->data['clusters'][0];
        $this->assertEquals(2, $cluster['keyword_count']);
        $this->assertEquals(8000, $cluster['total_search_volume']); // 5000 + 3000
        $this->assertEquals(55.0, $cluster['avg_keyword_difficulty']); // (60 + 50) / 2
        $this->assertCount(2, $cluster['keywords']);
        $this->assertEquals('content marketing', $cluster['keywords'][0]['keyword']);
        $this->assertEquals(5000, $cluster['keywords'][0]['search_volume']);
    }

    /** @test */
    public function it_returns_empty_list_when_no_clusters()
    {
        $tool = new ListContentBriefKeywordClustersTool();
        $result = $tool->execute([
            'content_brief_id' => $this->contentBrief->id,
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals(0, $result->data['count']);
        $this->assertCount(0, $result->data['clusters']);
    }

    /** @test */
    public function it_requires_content_brief_id_for_list()
    {
        $tool = new ListContentBriefKeywordClustersTool();
        $result = $tool->execute([], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    // ─── DELETE KEYWORD CLUSTER LINK ─────────────────────

    /** @test */
    public function it_deletes_a_keyword_cluster_link()
    {
        $link = BrandsContentBriefKeywordCluster::create([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterA->id,
            'role' => 'secondary',
        ]);

        $tool = new DeleteContentBriefKeywordClusterTool();
        $result = $tool->execute([
            'content_brief_id' => $this->contentBrief->id,
            'keyword_cluster_link_id' => $link->id,
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertNull(BrandsContentBriefKeywordCluster::find($link->id));
        $this->assertEquals($this->clusterA->id, $result->data['seo_keyword_cluster_id']);
        $this->assertEquals('secondary', $result->data['role']);
    }

    /** @test */
    public function it_returns_not_found_for_nonexistent_link()
    {
        $tool = new DeleteContentBriefKeywordClusterTool();
        $result = $tool->execute([
            'content_brief_id' => $this->contentBrief->id,
            'keyword_cluster_link_id' => 99999,
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('LINK_NOT_FOUND', $result->errorCode);
    }

    /** @test */
    public function it_rejects_delete_with_wrong_content_brief()
    {
        $otherBrief = BrandsContentBriefBoard::create([
            'name' => 'Other Brief',
            'content_type' => 'how-to',
            'search_intent' => 'informational',
            'status' => 'draft',
            'brand_id' => $this->brand->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $link = BrandsContentBriefKeywordCluster::create([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterA->id,
            'role' => 'primary',
        ]);

        $tool = new DeleteContentBriefKeywordClusterTool();
        $result = $tool->execute([
            'content_brief_id' => $otherBrief->id,
            'keyword_cluster_link_id' => $link->id,
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('LINK_NOT_FOUND', $result->errorCode);
        // Link should still exist
        $this->assertNotNull(BrandsContentBriefKeywordCluster::find($link->id));
    }

    /** @test */
    public function it_requires_content_brief_id_for_delete()
    {
        $tool = new DeleteContentBriefKeywordClusterTool();
        $result = $tool->execute([
            'keyword_cluster_link_id' => 1,
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    /** @test */
    public function it_requires_keyword_cluster_link_id_for_delete()
    {
        $tool = new DeleteContentBriefKeywordClusterTool();
        $result = $tool->execute([
            'content_brief_id' => $this->contentBrief->id,
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    // ─── GET BOARD WITH KEYWORD CLUSTERS ─────────────────

    /** @test */
    public function it_includes_keyword_clusters_in_get_board()
    {
        BrandsContentBriefKeywordCluster::create([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterA->id,
            'role' => 'primary',
        ]);
        BrandsContentBriefKeywordCluster::create([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterB->id,
            'role' => 'secondary',
        ]);

        $tool = new GetContentBriefBoardTool();
        $result = $tool->execute(['id' => $this->contentBrief->id], $this->context);

        $this->assertTrue($result->success);
        $this->assertArrayHasKey('keyword_clusters', $result->data);
        $this->assertCount(2, $result->data['keyword_clusters']);

        // Primary should be first
        $this->assertEquals('primary', $result->data['keyword_clusters'][0]['role']);
        $this->assertEquals($this->clusterA->id, $result->data['keyword_clusters'][0]['seo_keyword_cluster_id']);
        $this->assertEquals('Cluster A - Content Marketing', $result->data['keyword_clusters'][0]['cluster_name']);

        // Secondary should be second
        $this->assertEquals('secondary', $result->data['keyword_clusters'][1]['role']);
    }

    /** @test */
    public function it_includes_keywords_and_metrics_in_get_board()
    {
        BrandsContentBriefKeywordCluster::create([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterA->id,
            'role' => 'primary',
        ]);

        $tool = new GetContentBriefBoardTool();
        $result = $tool->execute(['id' => $this->contentBrief->id], $this->context);

        $this->assertTrue($result->success);
        $cluster = $result->data['keyword_clusters'][0];
        $this->assertArrayHasKey('keywords', $cluster);
        $this->assertArrayHasKey('keyword_count', $cluster);
        $this->assertArrayHasKey('total_search_volume', $cluster);
        $this->assertArrayHasKey('avg_keyword_difficulty', $cluster);
        $this->assertEquals(2, $cluster['keyword_count']);
        $this->assertEquals(8000, $cluster['total_search_volume']);
    }

    /** @test */
    public function it_returns_empty_keyword_clusters_array_when_none()
    {
        $tool = new GetContentBriefBoardTool();
        $result = $tool->execute(['id' => $this->contentBrief->id], $this->context);

        $this->assertTrue($result->success);
        $this->assertArrayHasKey('keyword_clusters', $result->data);
        $this->assertCount(0, $result->data['keyword_clusters']);
    }

    // ─── CASCADE DELETE ──────────────────────────────────

    /** @test */
    public function it_cascades_keyword_cluster_links_when_content_brief_deleted()
    {
        $link = BrandsContentBriefKeywordCluster::create([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterA->id,
            'role' => 'primary',
        ]);

        $linkId = $link->id;
        $this->contentBrief->delete();

        $this->assertNull(BrandsContentBriefKeywordCluster::find($linkId));
    }

    /** @test */
    public function it_cascades_keyword_cluster_links_when_cluster_deleted()
    {
        $link = BrandsContentBriefKeywordCluster::create([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterA->id,
            'role' => 'primary',
        ]);

        $linkId = $link->id;
        $this->clusterA->delete();

        $this->assertNull(BrandsContentBriefKeywordCluster::find($linkId));
    }

    // ─── MODEL ───────────────────────────────────────────

    /** @test */
    public function it_has_correct_content_brief_relationship()
    {
        $link = BrandsContentBriefKeywordCluster::create([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterA->id,
            'role' => 'primary',
        ]);

        $this->assertEquals($this->contentBrief->id, $link->contentBrief->id);
        $this->assertEquals('Test Content Brief', $link->contentBrief->name);
    }

    /** @test */
    public function it_has_correct_keyword_cluster_relationship()
    {
        $link = BrandsContentBriefKeywordCluster::create([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterA->id,
            'role' => 'primary',
        ]);

        $this->assertEquals($this->clusterA->id, $link->keywordCluster->id);
        $this->assertEquals('Cluster A - Content Marketing', $link->keywordCluster->name);
    }

    /** @test */
    public function it_has_brief_keyword_clusters_relationship_on_board()
    {
        BrandsContentBriefKeywordCluster::create([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterA->id,
            'role' => 'primary',
        ]);

        $this->contentBrief->load('briefKeywordClusters');
        $this->assertCount(1, $this->contentBrief->briefKeywordClusters);
        $this->assertEquals($this->clusterA->id, $this->contentBrief->briefKeywordClusters->first()->seo_keyword_cluster_id);
    }

    /** @test */
    public function it_has_keyword_clusters_belongs_to_many_on_board()
    {
        BrandsContentBriefKeywordCluster::create([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterA->id,
            'role' => 'primary',
        ]);

        $this->contentBrief->load('keywordClusters');
        $this->assertCount(1, $this->contentBrief->keywordClusters);
        $this->assertEquals($this->clusterA->id, $this->contentBrief->keywordClusters->first()->id);
        $this->assertEquals('primary', $this->contentBrief->keywordClusters->first()->pivot->role);
    }

    /** @test */
    public function it_has_content_briefs_relationship_on_cluster()
    {
        BrandsContentBriefKeywordCluster::create([
            'content_brief_id' => $this->contentBrief->id,
            'seo_keyword_cluster_id' => $this->clusterA->id,
            'role' => 'primary',
        ]);

        $this->clusterA->load('contentBriefs');
        $this->assertCount(1, $this->clusterA->contentBriefs);
        $this->assertEquals($this->contentBrief->id, $this->clusterA->contentBriefs->first()->id);
    }

    // ─── ENUM CONSTANTS ──────────────────────────────────

    /** @test */
    public function it_defines_all_roles()
    {
        $expected = ['primary', 'secondary', 'supporting'];
        $this->assertEquals($expected, array_keys(BrandsContentBriefKeywordCluster::ROLES));
    }
}

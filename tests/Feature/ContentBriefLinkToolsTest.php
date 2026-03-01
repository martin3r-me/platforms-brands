<?php

namespace Platform\Brands\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsContentBriefBoard;
use Platform\Brands\Models\BrandsContentBriefLink;
use Platform\Brands\Tools\CreateContentBriefLinkTool;
use Platform\Brands\Tools\ListContentBriefLinksTool;
use Platform\Brands\Tools\DeleteContentBriefLinkTool;
use Platform\Brands\Tools\GetContentBriefBoardTool;
use Platform\Brands\Tools\GetTopicClusterMapTool;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Models\User;
use Platform\Core\Models\Team;

class ContentBriefLinkToolsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Team $team;
    protected BrandsBrand $brand;
    protected ToolContext $context;
    protected BrandsContentBriefBoard $pillarBoard;
    protected BrandsContentBriefBoard $clusterBoard;

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

        $this->pillarBoard = BrandsContentBriefBoard::create([
            'name' => 'Pillar: Content Marketing',
            'content_type' => 'pillar',
            'search_intent' => 'informational',
            'status' => 'draft',
            'brand_id' => $this->brand->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $this->clusterBoard = BrandsContentBriefBoard::create([
            'name' => 'Cluster: SEO Basics',
            'content_type' => 'how-to',
            'search_intent' => 'informational',
            'status' => 'draft',
            'brand_id' => $this->brand->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $this->context = new ToolContext(user: $this->user);
    }

    // ─── CREATE LINK ──────────────────────────────────────

    /** @test */
    public function it_creates_a_content_brief_link()
    {
        $tool = new CreateContentBriefLinkTool();
        $result = $tool->execute([
            'source_content_brief_id' => $this->pillarBoard->id,
            'target_content_brief_id' => $this->clusterBoard->id,
            'link_type' => 'pillar_to_cluster',
            'anchor_hint' => 'SEO Basics lernen',
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals($this->pillarBoard->id, $result->data['source_content_brief_id']);
        $this->assertEquals($this->clusterBoard->id, $result->data['target_content_brief_id']);
        $this->assertEquals('pillar_to_cluster', $result->data['link_type']);
        $this->assertEquals('Pillar → Cluster', $result->data['link_type_label']);
        $this->assertEquals('SEO Basics lernen', $result->data['anchor_hint']);
        $this->assertNotNull($result->data['created_at']);
    }

    /** @test */
    public function it_creates_a_link_without_anchor_hint()
    {
        $tool = new CreateContentBriefLinkTool();
        $result = $tool->execute([
            'source_content_brief_id' => $this->pillarBoard->id,
            'target_content_brief_id' => $this->clusterBoard->id,
            'link_type' => 'related',
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertNull($result->data['anchor_hint']);
    }

    /** @test */
    public function it_rejects_self_link()
    {
        $tool = new CreateContentBriefLinkTool();
        $result = $tool->execute([
            'source_content_brief_id' => $this->pillarBoard->id,
            'target_content_brief_id' => $this->pillarBoard->id,
            'link_type' => 'related',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    /** @test */
    public function it_rejects_duplicate_link()
    {
        BrandsContentBriefLink::create([
            'source_content_brief_id' => $this->pillarBoard->id,
            'target_content_brief_id' => $this->clusterBoard->id,
            'link_type' => 'pillar_to_cluster',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new CreateContentBriefLinkTool();
        $result = $tool->execute([
            'source_content_brief_id' => $this->pillarBoard->id,
            'target_content_brief_id' => $this->clusterBoard->id,
            'link_type' => 'pillar_to_cluster',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('DUPLICATE_LINK', $result->errorCode);
    }

    /** @test */
    public function it_allows_same_pair_with_different_link_type()
    {
        BrandsContentBriefLink::create([
            'source_content_brief_id' => $this->pillarBoard->id,
            'target_content_brief_id' => $this->clusterBoard->id,
            'link_type' => 'pillar_to_cluster',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new CreateContentBriefLinkTool();
        $result = $tool->execute([
            'source_content_brief_id' => $this->pillarBoard->id,
            'target_content_brief_id' => $this->clusterBoard->id,
            'link_type' => 'related',
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals('related', $result->data['link_type']);
    }

    /** @test */
    public function it_rejects_invalid_link_type()
    {
        $tool = new CreateContentBriefLinkTool();
        $result = $tool->execute([
            'source_content_brief_id' => $this->pillarBoard->id,
            'target_content_brief_id' => $this->clusterBoard->id,
            'link_type' => 'invalid_type',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    /** @test */
    public function it_rejects_nonexistent_source()
    {
        $tool = new CreateContentBriefLinkTool();
        $result = $tool->execute([
            'source_content_brief_id' => 99999,
            'target_content_brief_id' => $this->clusterBoard->id,
            'link_type' => 'related',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('CONTENT_BRIEF_BOARD_NOT_FOUND', $result->errorCode);
    }

    /** @test */
    public function it_rejects_nonexistent_target()
    {
        $tool = new CreateContentBriefLinkTool();
        $result = $tool->execute([
            'source_content_brief_id' => $this->pillarBoard->id,
            'target_content_brief_id' => 99999,
            'link_type' => 'related',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('CONTENT_BRIEF_BOARD_NOT_FOUND', $result->errorCode);
    }

    /** @test */
    public function it_requires_source_content_brief_id()
    {
        $tool = new CreateContentBriefLinkTool();
        $result = $tool->execute([
            'target_content_brief_id' => $this->clusterBoard->id,
            'link_type' => 'related',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    /** @test */
    public function it_requires_target_content_brief_id()
    {
        $tool = new CreateContentBriefLinkTool();
        $result = $tool->execute([
            'source_content_brief_id' => $this->pillarBoard->id,
            'link_type' => 'related',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    /** @test */
    public function it_requires_link_type()
    {
        $tool = new CreateContentBriefLinkTool();
        $result = $tool->execute([
            'source_content_brief_id' => $this->pillarBoard->id,
            'target_content_brief_id' => $this->clusterBoard->id,
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    // ─── LIST LINKS ───────────────────────────────────────

    /** @test */
    public function it_lists_links_by_content_brief_id()
    {
        BrandsContentBriefLink::create([
            'source_content_brief_id' => $this->pillarBoard->id,
            'target_content_brief_id' => $this->clusterBoard->id,
            'link_type' => 'pillar_to_cluster',
            'anchor_hint' => 'SEO Basics',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new ListContentBriefLinksTool();
        $result = $tool->execute([
            'content_brief_id' => $this->pillarBoard->id,
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals(1, $result->data['count']);
        $this->assertCount(1, $result->data['links']);
        $this->assertEquals('pillar_to_cluster', $result->data['links'][0]['link_type']);
    }

    /** @test */
    public function it_lists_both_incoming_and_outgoing_links()
    {
        $thirdBoard = BrandsContentBriefBoard::create([
            'name' => 'Third Board',
            'content_type' => 'faq',
            'search_intent' => 'informational',
            'status' => 'draft',
            'brand_id' => $this->brand->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        // Outgoing link from pillar
        BrandsContentBriefLink::create([
            'source_content_brief_id' => $this->pillarBoard->id,
            'target_content_brief_id' => $this->clusterBoard->id,
            'link_type' => 'pillar_to_cluster',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        // Incoming link to pillar
        BrandsContentBriefLink::create([
            'source_content_brief_id' => $thirdBoard->id,
            'target_content_brief_id' => $this->pillarBoard->id,
            'link_type' => 'cluster_to_pillar',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new ListContentBriefLinksTool();
        $result = $tool->execute([
            'content_brief_id' => $this->pillarBoard->id,
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals(2, $result->data['count']);
    }

    /** @test */
    public function it_lists_links_by_brand_id()
    {
        BrandsContentBriefLink::create([
            'source_content_brief_id' => $this->pillarBoard->id,
            'target_content_brief_id' => $this->clusterBoard->id,
            'link_type' => 'pillar_to_cluster',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new ListContentBriefLinksTool();
        $result = $tool->execute([
            'brand_id' => $this->brand->id,
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals(1, $result->data['count']);
    }

    /** @test */
    public function it_filters_links_by_link_type()
    {
        BrandsContentBriefLink::create([
            'source_content_brief_id' => $this->pillarBoard->id,
            'target_content_brief_id' => $this->clusterBoard->id,
            'link_type' => 'pillar_to_cluster',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);
        BrandsContentBriefLink::create([
            'source_content_brief_id' => $this->pillarBoard->id,
            'target_content_brief_id' => $this->clusterBoard->id,
            'link_type' => 'related',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new ListContentBriefLinksTool();
        $result = $tool->execute([
            'brand_id' => $this->brand->id,
            'link_type' => 'pillar_to_cluster',
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals(1, $result->data['count']);
        $this->assertEquals('pillar_to_cluster', $result->data['links'][0]['link_type']);
    }

    /** @test */
    public function it_returns_empty_list_when_no_links()
    {
        $tool = new ListContentBriefLinksTool();
        $result = $tool->execute([
            'brand_id' => $this->brand->id,
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals(0, $result->data['count']);
    }

    /** @test */
    public function it_requires_content_brief_id_or_brand_id()
    {
        $tool = new ListContentBriefLinksTool();
        $result = $tool->execute([], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    // ─── DELETE LINK ──────────────────────────────────────

    /** @test */
    public function it_deletes_a_content_brief_link()
    {
        $link = BrandsContentBriefLink::create([
            'source_content_brief_id' => $this->pillarBoard->id,
            'target_content_brief_id' => $this->clusterBoard->id,
            'link_type' => 'pillar_to_cluster',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new DeleteContentBriefLinkTool();
        $result = $tool->execute([
            'content_brief_link_id' => $link->id,
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertNull(BrandsContentBriefLink::find($link->id));
    }

    /** @test */
    public function it_returns_not_found_when_deleting_nonexistent_link()
    {
        $tool = new DeleteContentBriefLinkTool();
        $result = $tool->execute([
            'content_brief_link_id' => 99999,
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('CONTENT_BRIEF_LINK_NOT_FOUND', $result->errorCode);
    }

    // ─── GET BOARD WITH LINKS ─────────────────────────────

    /** @test */
    public function it_includes_outgoing_links_in_get_board()
    {
        BrandsContentBriefLink::create([
            'source_content_brief_id' => $this->pillarBoard->id,
            'target_content_brief_id' => $this->clusterBoard->id,
            'link_type' => 'pillar_to_cluster',
            'anchor_hint' => 'SEO Basics',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new GetContentBriefBoardTool();
        $result = $tool->execute(['id' => $this->pillarBoard->id], $this->context);

        $this->assertTrue($result->success);
        $this->assertArrayHasKey('outgoing_links', $result->data);
        $this->assertCount(1, $result->data['outgoing_links']);
        $this->assertEquals($this->clusterBoard->id, $result->data['outgoing_links'][0]['target_content_brief_id']);
        $this->assertEquals('pillar_to_cluster', $result->data['outgoing_links'][0]['link_type']);
        $this->assertEquals('SEO Basics', $result->data['outgoing_links'][0]['anchor_hint']);
    }

    /** @test */
    public function it_includes_incoming_links_in_get_board()
    {
        BrandsContentBriefLink::create([
            'source_content_brief_id' => $this->pillarBoard->id,
            'target_content_brief_id' => $this->clusterBoard->id,
            'link_type' => 'pillar_to_cluster',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new GetContentBriefBoardTool();
        $result = $tool->execute(['id' => $this->clusterBoard->id], $this->context);

        $this->assertTrue($result->success);
        $this->assertArrayHasKey('incoming_links', $result->data);
        $this->assertCount(1, $result->data['incoming_links']);
        $this->assertEquals($this->pillarBoard->id, $result->data['incoming_links'][0]['source_content_brief_id']);
        $this->assertEquals('pillar_to_cluster', $result->data['incoming_links'][0]['link_type']);
    }

    /** @test */
    public function it_returns_empty_links_arrays_when_no_links()
    {
        $tool = new GetContentBriefBoardTool();
        $result = $tool->execute(['id' => $this->pillarBoard->id], $this->context);

        $this->assertTrue($result->success);
        $this->assertArrayHasKey('outgoing_links', $result->data);
        $this->assertArrayHasKey('incoming_links', $result->data);
        $this->assertCount(0, $result->data['outgoing_links']);
        $this->assertCount(0, $result->data['incoming_links']);
    }

    // ─── TOPIC CLUSTER MAP ────────────────────────────────

    /** @test */
    public function it_returns_topic_cluster_map()
    {
        BrandsContentBriefLink::create([
            'source_content_brief_id' => $this->pillarBoard->id,
            'target_content_brief_id' => $this->clusterBoard->id,
            'link_type' => 'pillar_to_cluster',
            'anchor_hint' => 'SEO Basics',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new GetTopicClusterMapTool();
        $result = $tool->execute([
            'brand_id' => $this->brand->id,
        ], $this->context);

        $this->assertTrue($result->success);

        // Nodes
        $this->assertArrayHasKey('nodes', $result->data);
        $this->assertCount(2, $result->data['nodes']);

        // Edges
        $this->assertArrayHasKey('edges', $result->data);
        $this->assertCount(1, $result->data['edges']);
        $this->assertEquals($this->pillarBoard->id, $result->data['edges'][0]['source']);
        $this->assertEquals($this->clusterBoard->id, $result->data['edges'][0]['target']);

        // Clusters
        $this->assertArrayHasKey('clusters', $result->data);
        $this->assertCount(1, $result->data['clusters']);
        $this->assertEquals($this->pillarBoard->id, $result->data['clusters'][0]['pillar_id']);
        $this->assertContains($this->clusterBoard->id, $result->data['clusters'][0]['cluster_brief_ids']);

        // Stats
        $this->assertEquals(2, $result->data['stats']['total_nodes']);
        $this->assertEquals(1, $result->data['stats']['total_edges']);
        $this->assertEquals(1, $result->data['stats']['total_pillars']);
    }

    /** @test */
    public function it_returns_empty_map_for_brand_without_boards()
    {
        $emptyBrand = BrandsBrand::create([
            'name' => 'Empty Brand',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new GetTopicClusterMapTool();
        $result = $tool->execute([
            'brand_id' => $emptyBrand->id,
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertCount(0, $result->data['nodes']);
        $this->assertCount(0, $result->data['edges']);
        $this->assertCount(0, $result->data['clusters']);
    }

    /** @test */
    public function it_returns_node_link_counts()
    {
        $thirdBoard = BrandsContentBriefBoard::create([
            'name' => 'Third Board',
            'content_type' => 'faq',
            'search_intent' => 'informational',
            'status' => 'draft',
            'brand_id' => $this->brand->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        BrandsContentBriefLink::create([
            'source_content_brief_id' => $this->pillarBoard->id,
            'target_content_brief_id' => $this->clusterBoard->id,
            'link_type' => 'pillar_to_cluster',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);
        BrandsContentBriefLink::create([
            'source_content_brief_id' => $this->pillarBoard->id,
            'target_content_brief_id' => $thirdBoard->id,
            'link_type' => 'pillar_to_cluster',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new GetTopicClusterMapTool();
        $result = $tool->execute([
            'brand_id' => $this->brand->id,
        ], $this->context);

        $this->assertTrue($result->success);

        // Find pillar node
        $pillarNode = collect($result->data['nodes'])->firstWhere('id', $this->pillarBoard->id);
        $this->assertEquals(2, $pillarNode['outgoing_links_count']);
        $this->assertEquals(0, $pillarNode['incoming_links_count']);

        // Find cluster node
        $clusterNode = collect($result->data['nodes'])->firstWhere('id', $this->clusterBoard->id);
        $this->assertEquals(0, $clusterNode['outgoing_links_count']);
        $this->assertEquals(1, $clusterNode['incoming_links_count']);
    }

    /** @test */
    public function it_rejects_nonexistent_brand_for_map()
    {
        $tool = new GetTopicClusterMapTool();
        $result = $tool->execute([
            'brand_id' => 99999,
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('BRAND_NOT_FOUND', $result->errorCode);
    }

    // ─── CASCADE DELETE ───────────────────────────────────

    /** @test */
    public function it_cascades_links_when_content_brief_deleted()
    {
        $link = BrandsContentBriefLink::create([
            'source_content_brief_id' => $this->pillarBoard->id,
            'target_content_brief_id' => $this->clusterBoard->id,
            'link_type' => 'pillar_to_cluster',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $linkId = $link->id;
        $this->pillarBoard->delete();

        $this->assertNull(BrandsContentBriefLink::find($linkId));
    }

    /** @test */
    public function it_cascades_links_when_target_brief_deleted()
    {
        $link = BrandsContentBriefLink::create([
            'source_content_brief_id' => $this->pillarBoard->id,
            'target_content_brief_id' => $this->clusterBoard->id,
            'link_type' => 'pillar_to_cluster',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $linkId = $link->id;
        $this->clusterBoard->delete();

        $this->assertNull(BrandsContentBriefLink::find($linkId));
    }

    // ─── MODEL ────────────────────────────────────────────

    /** @test */
    public function it_has_correct_source_relationship()
    {
        $link = BrandsContentBriefLink::create([
            'source_content_brief_id' => $this->pillarBoard->id,
            'target_content_brief_id' => $this->clusterBoard->id,
            'link_type' => 'pillar_to_cluster',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $this->assertEquals($this->pillarBoard->id, $link->sourceContentBrief->id);
        $this->assertEquals('Pillar: Content Marketing', $link->sourceContentBrief->name);
    }

    /** @test */
    public function it_has_correct_target_relationship()
    {
        $link = BrandsContentBriefLink::create([
            'source_content_brief_id' => $this->pillarBoard->id,
            'target_content_brief_id' => $this->clusterBoard->id,
            'link_type' => 'pillar_to_cluster',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $this->assertEquals($this->clusterBoard->id, $link->targetContentBrief->id);
        $this->assertEquals('Cluster: SEO Basics', $link->targetContentBrief->name);
    }

    /** @test */
    public function it_has_outgoing_links_relationship_on_board()
    {
        BrandsContentBriefLink::create([
            'source_content_brief_id' => $this->pillarBoard->id,
            'target_content_brief_id' => $this->clusterBoard->id,
            'link_type' => 'pillar_to_cluster',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $this->pillarBoard->load('outgoingLinks');
        $this->assertCount(1, $this->pillarBoard->outgoingLinks);
        $this->assertEquals($this->clusterBoard->id, $this->pillarBoard->outgoingLinks->first()->target_content_brief_id);
    }

    /** @test */
    public function it_has_incoming_links_relationship_on_board()
    {
        BrandsContentBriefLink::create([
            'source_content_brief_id' => $this->pillarBoard->id,
            'target_content_brief_id' => $this->clusterBoard->id,
            'link_type' => 'pillar_to_cluster',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $this->clusterBoard->load('incomingLinks');
        $this->assertCount(1, $this->clusterBoard->incomingLinks);
        $this->assertEquals($this->pillarBoard->id, $this->clusterBoard->incomingLinks->first()->source_content_brief_id);
    }

    // ─── ENUM CONSTANTS ───────────────────────────────────

    /** @test */
    public function it_defines_all_link_types()
    {
        $expected = ['pillar_to_cluster', 'cluster_to_pillar', 'related', 'see_also'];
        $this->assertEquals($expected, array_keys(BrandsContentBriefLink::LINK_TYPES));
    }
}

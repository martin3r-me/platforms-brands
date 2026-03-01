<?php

namespace Platform\Brands\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsContentBriefBoard;
use Platform\Brands\Models\BrandsSeoBoard;
use Platform\Brands\Tools\CreateContentBriefBoardTool;
use Platform\Brands\Tools\ListContentBriefBoardsTool;
use Platform\Brands\Tools\GetContentBriefBoardTool;
use Platform\Brands\Tools\UpdateContentBriefBoardTool;
use Platform\Brands\Tools\DeleteContentBriefBoardTool;
use Platform\Brands\Tools\BulkCreateContentBriefBoardsTool;
use Platform\Brands\Tools\BulkUpdateContentBriefBoardsTool;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Models\User;
use Platform\Core\Models\Team;

class ContentBriefBoardToolsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Team $team;
    protected BrandsBrand $brand;
    protected ToolContext $context;

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

        $this->context = new ToolContext(user: $this->user);
    }

    // ─── CREATE ────────────────────────────────────────────

    /** @test */
    public function it_creates_a_content_brief_board_with_minimal_params()
    {
        $tool = new CreateContentBriefBoardTool();
        $result = $tool->execute([
            'brand_id' => $this->brand->id,
            'name' => 'SEO-Pillar: Laravel Testing',
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals('SEO-Pillar: Laravel Testing', $result->data['name']);
        $this->assertEquals('guide', $result->data['content_type']);
        $this->assertEquals('informational', $result->data['search_intent']);
        $this->assertEquals('draft', $result->data['status']);
        $this->assertNotNull($result->data['uuid']);
    }

    /** @test */
    public function it_creates_a_content_brief_board_with_all_fields()
    {
        $seoBoard = BrandsSeoBoard::create([
            'name' => 'Test SEO Board',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
            'brand_id' => $this->brand->id,
        ]);

        $tool = new CreateContentBriefBoardTool();
        $result = $tool->execute([
            'brand_id' => $this->brand->id,
            'name' => 'Pillar: Content Marketing Guide',
            'description' => 'Umfassender Guide zu Content Marketing',
            'content_type' => 'pillar',
            'search_intent' => 'informational',
            'status' => 'briefed',
            'target_slug' => '/blog/content-marketing-guide',
            'target_word_count' => 3000,
            'seo_board_id' => $seoBoard->id,
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals('pillar', $result->data['content_type']);
        $this->assertEquals('informational', $result->data['search_intent']);
        $this->assertEquals('briefed', $result->data['status']);
        $this->assertEquals('/blog/content-marketing-guide', $result->data['target_slug']);
        $this->assertEquals(3000, $result->data['target_word_count']);
        $this->assertEquals($seoBoard->id, $result->data['seo_board_id']);
    }

    /** @test */
    public function it_rejects_invalid_content_type()
    {
        $tool = new CreateContentBriefBoardTool();
        $result = $tool->execute([
            'brand_id' => $this->brand->id,
            'name' => 'Test',
            'content_type' => 'invalid_type',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    /** @test */
    public function it_rejects_invalid_search_intent()
    {
        $tool = new CreateContentBriefBoardTool();
        $result = $tool->execute([
            'brand_id' => $this->brand->id,
            'name' => 'Test',
            'search_intent' => 'invalid_intent',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    /** @test */
    public function it_rejects_invalid_status()
    {
        $tool = new CreateContentBriefBoardTool();
        $result = $tool->execute([
            'brand_id' => $this->brand->id,
            'name' => 'Test',
            'status' => 'invalid_status',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    /** @test */
    public function it_requires_brand_id_for_create()
    {
        $tool = new CreateContentBriefBoardTool();
        $result = $tool->execute([
            'name' => 'Test',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    /** @test */
    public function it_rejects_nonexistent_brand()
    {
        $tool = new CreateContentBriefBoardTool();
        $result = $tool->execute([
            'brand_id' => 99999,
            'name' => 'Test',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('BRAND_NOT_FOUND', $result->errorCode);
    }

    // ─── LIST ──────────────────────────────────────────────

    /** @test */
    public function it_lists_content_brief_boards_for_brand()
    {
        BrandsContentBriefBoard::create([
            'name' => 'Brief 1',
            'content_type' => 'pillar',
            'search_intent' => 'informational',
            'status' => 'draft',
            'brand_id' => $this->brand->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);
        BrandsContentBriefBoard::create([
            'name' => 'Brief 2',
            'content_type' => 'how-to',
            'search_intent' => 'commercial',
            'status' => 'briefed',
            'brand_id' => $this->brand->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new ListContentBriefBoardsTool();
        $result = $tool->execute([
            'brand_id' => $this->brand->id,
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals(2, $result->data['count']);
        $this->assertCount(2, $result->data['content_brief_boards']);
    }

    /** @test */
    public function it_returns_empty_list_for_brand_without_boards()
    {
        $tool = new ListContentBriefBoardsTool();
        $result = $tool->execute([
            'brand_id' => $this->brand->id,
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals(0, $result->data['count']);
    }

    // ─── GET ───────────────────────────────────────────────

    /** @test */
    public function it_gets_a_single_content_brief_board()
    {
        $board = BrandsContentBriefBoard::create([
            'name' => 'Detailed Brief',
            'description' => 'Eine Beschreibung',
            'content_type' => 'faq',
            'search_intent' => 'transactional',
            'status' => 'in_production',
            'target_slug' => '/faq/testing',
            'target_word_count' => 1500,
            'brand_id' => $this->brand->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new GetContentBriefBoardTool();
        $result = $tool->execute(['id' => $board->id], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals('Detailed Brief', $result->data['name']);
        $this->assertEquals('faq', $result->data['content_type']);
        $this->assertEquals('FAQ', $result->data['content_type_label']);
        $this->assertEquals('transactional', $result->data['search_intent']);
        $this->assertEquals('Transactional', $result->data['search_intent_label']);
        $this->assertEquals('in_production', $result->data['status']);
        $this->assertEquals('In Produktion', $result->data['status_label']);
        $this->assertEquals('/faq/testing', $result->data['target_slug']);
        $this->assertEquals(1500, $result->data['target_word_count']);
    }

    /** @test */
    public function it_returns_not_found_for_nonexistent_board()
    {
        $tool = new GetContentBriefBoardTool();
        $result = $tool->execute(['id' => 99999], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('CONTENT_BRIEF_BOARD_NOT_FOUND', $result->errorCode);
    }

    // ─── UPDATE ────────────────────────────────────────────

    /** @test */
    public function it_updates_a_content_brief_board()
    {
        $board = BrandsContentBriefBoard::create([
            'name' => 'Original Name',
            'content_type' => 'guide',
            'search_intent' => 'informational',
            'status' => 'draft',
            'brand_id' => $this->brand->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new UpdateContentBriefBoardTool();
        $result = $tool->execute([
            'content_brief_board_id' => $board->id,
            'name' => 'Updated Name',
            'content_type' => 'pillar',
            'status' => 'briefed',
            'target_word_count' => 5000,
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals('Updated Name', $result->data['content_brief_board_name']);
        $this->assertEquals('pillar', $result->data['content_type']);
        $this->assertEquals('briefed', $result->data['status']);
        $this->assertEquals(5000, $result->data['target_word_count']);
    }

    /** @test */
    public function it_marks_board_as_done()
    {
        $board = BrandsContentBriefBoard::create([
            'name' => 'Board to Complete',
            'content_type' => 'guide',
            'search_intent' => 'informational',
            'status' => 'published',
            'brand_id' => $this->brand->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new UpdateContentBriefBoardTool();
        $result = $tool->execute([
            'content_brief_board_id' => $board->id,
            'done' => true,
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertTrue($result->data['done']);
        $this->assertNotNull($result->data['done_at']);
    }

    /** @test */
    public function it_rejects_invalid_enum_on_update()
    {
        $board = BrandsContentBriefBoard::create([
            'name' => 'Test Board',
            'content_type' => 'guide',
            'search_intent' => 'informational',
            'status' => 'draft',
            'brand_id' => $this->brand->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new UpdateContentBriefBoardTool();
        $result = $tool->execute([
            'content_brief_board_id' => $board->id,
            'content_type' => 'nonexistent',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    // ─── DELETE ────────────────────────────────────────────

    /** @test */
    public function it_deletes_a_content_brief_board()
    {
        $board = BrandsContentBriefBoard::create([
            'name' => 'Board to Delete',
            'content_type' => 'guide',
            'search_intent' => 'informational',
            'status' => 'draft',
            'brand_id' => $this->brand->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new DeleteContentBriefBoardTool();
        $result = $tool->execute([
            'content_brief_board_id' => $board->id,
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertNull(BrandsContentBriefBoard::find($board->id));
    }

    /** @test */
    public function it_returns_not_found_when_deleting_nonexistent_board()
    {
        $tool = new DeleteContentBriefBoardTool();
        $result = $tool->execute([
            'content_brief_board_id' => 99999,
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('CONTENT_BRIEF_BOARD_NOT_FOUND', $result->errorCode);
    }

    // ─── BULK CREATE ───────────────────────────────────────

    /** @test */
    public function it_bulk_creates_content_brief_boards()
    {
        $tool = new BulkCreateContentBriefBoardsTool();
        $result = $tool->execute([
            'defaults' => [
                'brand_id' => $this->brand->id,
                'content_type' => 'how-to',
                'search_intent' => 'informational',
            ],
            'content_brief_boards' => [
                ['name' => 'How to Install Laravel'],
                ['name' => 'How to Deploy Laravel', 'content_type' => 'guide'],
                ['name' => 'How to Test Laravel', 'status' => 'briefed'],
            ],
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals(3, $result->data['summary']['requested']);
        $this->assertEquals(3, $result->data['summary']['ok']);
        $this->assertEquals(0, $result->data['summary']['failed']);

        // First brief uses defaults
        $this->assertEquals('how-to', $result->data['results'][0]['data']['content_type']);
        // Second brief overrides content_type
        $this->assertEquals('guide', $result->data['results'][1]['data']['content_type']);
        // Third brief uses defaults + overrides status
        $this->assertEquals('briefed', $result->data['results'][2]['data']['status']);
    }

    /** @test */
    public function it_bulk_creates_atomically()
    {
        $tool = new BulkCreateContentBriefBoardsTool();
        $result = $tool->execute([
            'atomic' => true,
            'defaults' => [
                'brand_id' => $this->brand->id,
            ],
            'content_brief_boards' => [
                ['name' => 'Brief A'],
                ['name' => 'Brief B'],
            ],
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals(2, $result->data['summary']['ok']);
        $this->assertEquals(2, BrandsContentBriefBoard::where('brand_id', $this->brand->id)->count());
    }

    // ─── BULK UPDATE ───────────────────────────────────────

    /** @test */
    public function it_bulk_updates_content_brief_boards()
    {
        $board1 = BrandsContentBriefBoard::create([
            'name' => 'Board 1',
            'content_type' => 'guide',
            'search_intent' => 'informational',
            'status' => 'draft',
            'brand_id' => $this->brand->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);
        $board2 = BrandsContentBriefBoard::create([
            'name' => 'Board 2',
            'content_type' => 'guide',
            'search_intent' => 'informational',
            'status' => 'draft',
            'brand_id' => $this->brand->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new BulkUpdateContentBriefBoardsTool();
        $result = $tool->execute([
            'updates' => [
                ['content_brief_board_id' => $board1->id, 'status' => 'briefed'],
                ['content_brief_board_id' => $board2->id, 'status' => 'in_production', 'name' => 'Board 2 Updated'],
            ],
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals(2, $result->data['summary']['ok']);
        $this->assertEquals(0, $result->data['summary']['failed']);

        $board1->refresh();
        $board2->refresh();
        $this->assertEquals('briefed', $board1->status);
        $this->assertEquals('in_production', $board2->status);
        $this->assertEquals('Board 2 Updated', $board2->name);
    }

    // ─── MODEL ─────────────────────────────────────────────

    /** @test */
    public function it_auto_generates_uuid_on_create()
    {
        $board = BrandsContentBriefBoard::create([
            'name' => 'UUID Test',
            'content_type' => 'guide',
            'search_intent' => 'informational',
            'status' => 'draft',
            'brand_id' => $this->brand->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $this->assertNotNull($board->uuid);
        $this->assertNotEmpty($board->uuid);
    }

    /** @test */
    public function it_auto_increments_order()
    {
        $board1 = BrandsContentBriefBoard::create([
            'name' => 'First',
            'content_type' => 'guide',
            'search_intent' => 'informational',
            'status' => 'draft',
            'brand_id' => $this->brand->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);
        $board2 = BrandsContentBriefBoard::create([
            'name' => 'Second',
            'content_type' => 'guide',
            'search_intent' => 'informational',
            'status' => 'draft',
            'brand_id' => $this->brand->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $this->assertEquals(1, $board1->order);
        $this->assertEquals(2, $board2->order);
    }

    /** @test */
    public function it_has_correct_brand_relationship()
    {
        $board = BrandsContentBriefBoard::create([
            'name' => 'Relation Test',
            'content_type' => 'guide',
            'search_intent' => 'informational',
            'status' => 'draft',
            'brand_id' => $this->brand->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $this->assertEquals($this->brand->id, $board->brand->id);
        $this->assertEquals($this->brand->name, $board->brand->name);
    }

    /** @test */
    public function it_has_correct_seo_board_relationship()
    {
        $seoBoard = BrandsSeoBoard::create([
            'name' => 'SEO Board',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
            'brand_id' => $this->brand->id,
        ]);

        $board = BrandsContentBriefBoard::create([
            'name' => 'With SEO Board',
            'content_type' => 'guide',
            'search_intent' => 'informational',
            'status' => 'draft',
            'seo_board_id' => $seoBoard->id,
            'brand_id' => $this->brand->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $this->assertEquals($seoBoard->id, $board->seoBoard->id);
    }

    /** @test */
    public function it_cascades_delete_from_brand()
    {
        $board = BrandsContentBriefBoard::create([
            'name' => 'Cascade Test',
            'content_type' => 'guide',
            'search_intent' => 'informational',
            'status' => 'draft',
            'brand_id' => $this->brand->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $boardId = $board->id;
        $this->brand->delete();

        $this->assertNull(BrandsContentBriefBoard::find($boardId));
    }

    // ─── ENUM CONSTANTS ────────────────────────────────────

    /** @test */
    public function it_defines_all_content_types()
    {
        $expected = ['pillar', 'how-to', 'listicle', 'faq', 'comparison', 'deep-dive', 'guide'];
        $this->assertEquals($expected, array_keys(BrandsContentBriefBoard::CONTENT_TYPES));
    }

    /** @test */
    public function it_defines_all_search_intents()
    {
        $expected = ['informational', 'commercial', 'transactional', 'navigational'];
        $this->assertEquals($expected, array_keys(BrandsContentBriefBoard::SEARCH_INTENTS));
    }

    /** @test */
    public function it_defines_all_statuses()
    {
        $expected = ['draft', 'briefed', 'in_production', 'review', 'published'];
        $this->assertEquals($expected, array_keys(BrandsContentBriefBoard::STATUSES));
    }
}

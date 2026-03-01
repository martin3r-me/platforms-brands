<?php

namespace Platform\Brands\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsContentBriefBoard;
use Platform\Brands\Models\BrandsContentBriefSection;
use Platform\Brands\Tools\CreateContentBriefSectionTool;
use Platform\Brands\Tools\ListContentBriefSectionsTool;
use Platform\Brands\Tools\UpdateContentBriefSectionTool;
use Platform\Brands\Tools\DeleteContentBriefSectionTool;
use Platform\Brands\Tools\BulkCreateContentBriefSectionsTool;
use Platform\Brands\Tools\BulkUpdateContentBriefSectionsTool;
use Platform\Brands\Tools\GetContentBriefBoardTool;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Models\User;
use Platform\Core\Models\Team;

class ContentBriefSectionToolsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Team $team;
    protected BrandsBrand $brand;
    protected ToolContext $context;
    protected BrandsContentBriefBoard $board;

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

        $this->board = BrandsContentBriefBoard::create([
            'name' => 'SEO Guide: Content Marketing',
            'content_type' => 'guide',
            'search_intent' => 'informational',
            'status' => 'draft',
            'brand_id' => $this->brand->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $this->context = new ToolContext(user: $this->user);
    }

    // ─── CREATE SECTION ──────────────────────────────────────

    /** @test */
    public function it_creates_a_content_brief_section_with_all_fields()
    {
        $tool = new CreateContentBriefSectionTool();
        $result = $tool->execute([
            'content_brief_id' => $this->board->id,
            'heading' => 'Was ist Content Marketing?',
            'heading_level' => 'h2',
            'description' => 'Einführung in das Thema mit Definition und Relevanz.',
            'target_keywords' => ['content marketing', 'was ist content marketing'],
            'notes' => 'Kurz halten, max. 200 Wörter.',
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals($this->board->id, $result->data['content_brief_id']);
        $this->assertEquals('SEO Guide: Content Marketing', $result->data['content_brief_name']);
        $this->assertEquals('Was ist Content Marketing?', $result->data['heading']);
        $this->assertEquals('h2', $result->data['heading_level']);
        $this->assertEquals('H2', $result->data['heading_level_label']);
        $this->assertEquals('Einführung in das Thema mit Definition und Relevanz.', $result->data['description']);
        $this->assertEquals(['content marketing', 'was ist content marketing'], $result->data['target_keywords']);
        $this->assertEquals('Kurz halten, max. 200 Wörter.', $result->data['notes']);
        $this->assertEquals(1, $result->data['order']);
        $this->assertNotNull($result->data['created_at']);
    }

    /** @test */
    public function it_creates_a_section_with_minimal_params()
    {
        $tool = new CreateContentBriefSectionTool();
        $result = $tool->execute([
            'content_brief_id' => $this->board->id,
            'heading' => 'Einführung',
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals('Einführung', $result->data['heading']);
        $this->assertEquals('h2', $result->data['heading_level']); // default
        $this->assertNull($result->data['description']);
        $this->assertNull($result->data['target_keywords']);
        $this->assertNull($result->data['notes']);
    }

    /** @test */
    public function it_auto_increments_order()
    {
        $tool = new CreateContentBriefSectionTool();

        $result1 = $tool->execute([
            'content_brief_id' => $this->board->id,
            'heading' => 'Einführung',
        ], $this->context);
        $this->assertEquals(1, $result1->data['order']);

        $result2 = $tool->execute([
            'content_brief_id' => $this->board->id,
            'heading' => 'Grundlagen',
        ], $this->context);
        $this->assertEquals(2, $result2->data['order']);

        $result3 = $tool->execute([
            'content_brief_id' => $this->board->id,
            'heading' => 'Fazit',
        ], $this->context);
        $this->assertEquals(3, $result3->data['order']);
    }

    /** @test */
    public function it_allows_explicit_order()
    {
        $tool = new CreateContentBriefSectionTool();
        $result = $tool->execute([
            'content_brief_id' => $this->board->id,
            'heading' => 'Einführung',
            'order' => 5,
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals(5, $result->data['order']);
    }

    /** @test */
    public function it_creates_sections_with_different_heading_levels()
    {
        $tool = new CreateContentBriefSectionTool();

        foreach (['h2', 'h3', 'h4'] as $level) {
            $result = $tool->execute([
                'content_brief_id' => $this->board->id,
                'heading' => "Test $level",
                'heading_level' => $level,
            ], $this->context);

            $this->assertTrue($result->success);
            $this->assertEquals($level, $result->data['heading_level']);
        }
    }

    /** @test */
    public function it_rejects_invalid_heading_level()
    {
        $tool = new CreateContentBriefSectionTool();
        $result = $tool->execute([
            'content_brief_id' => $this->board->id,
            'heading' => 'Test',
            'heading_level' => 'h1',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    /** @test */
    public function it_requires_content_brief_id()
    {
        $tool = new CreateContentBriefSectionTool();
        $result = $tool->execute([
            'heading' => 'Test',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    /** @test */
    public function it_requires_heading()
    {
        $tool = new CreateContentBriefSectionTool();
        $result = $tool->execute([
            'content_brief_id' => $this->board->id,
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    /** @test */
    public function it_rejects_nonexistent_content_brief()
    {
        $tool = new CreateContentBriefSectionTool();
        $result = $tool->execute([
            'content_brief_id' => 99999,
            'heading' => 'Test',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('CONTENT_BRIEF_BOARD_NOT_FOUND', $result->errorCode);
    }

    // ─── LIST SECTIONS ───────────────────────────────────────

    /** @test */
    public function it_lists_sections_ordered_by_order_field()
    {
        BrandsContentBriefSection::create([
            'content_brief_id' => $this->board->id,
            'order' => 3,
            'heading' => 'Fazit',
            'heading_level' => 'h2',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);
        BrandsContentBriefSection::create([
            'content_brief_id' => $this->board->id,
            'order' => 1,
            'heading' => 'Einführung',
            'heading_level' => 'h2',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);
        BrandsContentBriefSection::create([
            'content_brief_id' => $this->board->id,
            'order' => 2,
            'heading' => 'Hauptteil',
            'heading_level' => 'h2',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new ListContentBriefSectionsTool();
        $result = $tool->execute([
            'content_brief_id' => $this->board->id,
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals(3, $result->data['count']);
        $this->assertEquals('Einführung', $result->data['sections'][0]['heading']);
        $this->assertEquals('Hauptteil', $result->data['sections'][1]['heading']);
        $this->assertEquals('Fazit', $result->data['sections'][2]['heading']);
        $this->assertEquals(1, $result->data['sections'][0]['order']);
        $this->assertEquals(2, $result->data['sections'][1]['order']);
        $this->assertEquals(3, $result->data['sections'][2]['order']);
    }

    /** @test */
    public function it_filters_sections_by_heading_level()
    {
        BrandsContentBriefSection::create([
            'content_brief_id' => $this->board->id,
            'order' => 1,
            'heading' => 'H2 Section',
            'heading_level' => 'h2',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);
        BrandsContentBriefSection::create([
            'content_brief_id' => $this->board->id,
            'order' => 2,
            'heading' => 'H3 Section',
            'heading_level' => 'h3',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new ListContentBriefSectionsTool();
        $result = $tool->execute([
            'content_brief_id' => $this->board->id,
            'heading_level' => 'h3',
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals(1, $result->data['count']);
        $this->assertEquals('H3 Section', $result->data['sections'][0]['heading']);
    }

    /** @test */
    public function it_returns_empty_list_when_no_sections()
    {
        $tool = new ListContentBriefSectionsTool();
        $result = $tool->execute([
            'content_brief_id' => $this->board->id,
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals(0, $result->data['count']);
        $this->assertEmpty($result->data['sections']);
    }

    /** @test */
    public function it_requires_content_brief_id_for_listing()
    {
        $tool = new ListContentBriefSectionsTool();
        $result = $tool->execute([], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    /** @test */
    public function it_rejects_nonexistent_content_brief_for_listing()
    {
        $tool = new ListContentBriefSectionsTool();
        $result = $tool->execute([
            'content_brief_id' => 99999,
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('CONTENT_BRIEF_BOARD_NOT_FOUND', $result->errorCode);
    }

    // ─── UPDATE SECTION ──────────────────────────────────────

    /** @test */
    public function it_updates_a_section()
    {
        $section = BrandsContentBriefSection::create([
            'content_brief_id' => $this->board->id,
            'order' => 1,
            'heading' => 'Original Heading',
            'heading_level' => 'h2',
            'description' => 'Original description',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new UpdateContentBriefSectionTool();
        $result = $tool->execute([
            'content_brief_section_id' => $section->id,
            'heading' => 'Updated Heading',
            'heading_level' => 'h3',
            'description' => 'Updated description',
            'target_keywords' => ['new keyword'],
            'notes' => 'New note',
            'order' => 5,
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals('Updated Heading', $result->data['heading']);
        $this->assertEquals('h3', $result->data['heading_level']);
        $this->assertEquals('H3', $result->data['heading_level_label']);
        $this->assertEquals('Updated description', $result->data['description']);
        $this->assertEquals(['new keyword'], $result->data['target_keywords']);
        $this->assertEquals('New note', $result->data['notes']);
        $this->assertEquals(5, $result->data['order']);
    }

    /** @test */
    public function it_updates_only_provided_fields()
    {
        $section = BrandsContentBriefSection::create([
            'content_brief_id' => $this->board->id,
            'order' => 1,
            'heading' => 'Original Heading',
            'heading_level' => 'h2',
            'description' => 'Original description',
            'notes' => 'Original notes',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new UpdateContentBriefSectionTool();
        $result = $tool->execute([
            'content_brief_section_id' => $section->id,
            'heading' => 'New Heading',
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals('New Heading', $result->data['heading']);
        $this->assertEquals('h2', $result->data['heading_level']); // unchanged
        $this->assertEquals('Original description', $result->data['description']); // unchanged
        $this->assertEquals('Original notes', $result->data['notes']); // unchanged
    }

    /** @test */
    public function it_rejects_invalid_heading_level_on_update()
    {
        $section = BrandsContentBriefSection::create([
            'content_brief_id' => $this->board->id,
            'order' => 1,
            'heading' => 'Test',
            'heading_level' => 'h2',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new UpdateContentBriefSectionTool();
        $result = $tool->execute([
            'content_brief_section_id' => $section->id,
            'heading_level' => 'h1',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    /** @test */
    public function it_returns_not_found_when_updating_nonexistent_section()
    {
        $tool = new UpdateContentBriefSectionTool();
        $result = $tool->execute([
            'content_brief_section_id' => 99999,
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('CONTENT_BRIEF_SECTION_NOT_FOUND', $result->errorCode);
    }

    /** @test */
    public function it_can_set_target_keywords_to_empty_array()
    {
        $section = BrandsContentBriefSection::create([
            'content_brief_id' => $this->board->id,
            'order' => 1,
            'heading' => 'Test',
            'heading_level' => 'h2',
            'target_keywords' => ['keyword1', 'keyword2'],
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new UpdateContentBriefSectionTool();
        $result = $tool->execute([
            'content_brief_section_id' => $section->id,
            'target_keywords' => [],
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals([], $result->data['target_keywords']);
    }

    // ─── DELETE SECTION ──────────────────────────────────────

    /** @test */
    public function it_deletes_a_section()
    {
        $section = BrandsContentBriefSection::create([
            'content_brief_id' => $this->board->id,
            'order' => 1,
            'heading' => 'To Be Deleted',
            'heading_level' => 'h2',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new DeleteContentBriefSectionTool();
        $result = $tool->execute([
            'content_brief_section_id' => $section->id,
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals('To Be Deleted', $result->data['heading']);
        $this->assertNull(BrandsContentBriefSection::find($section->id));
    }

    /** @test */
    public function it_returns_not_found_when_deleting_nonexistent_section()
    {
        $tool = new DeleteContentBriefSectionTool();
        $result = $tool->execute([
            'content_brief_section_id' => 99999,
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('CONTENT_BRIEF_SECTION_NOT_FOUND', $result->errorCode);
    }

    // ─── BULK CREATE ─────────────────────────────────────────

    /** @test */
    public function it_bulk_creates_sections()
    {
        $tool = new BulkCreateContentBriefSectionsTool();
        $result = $tool->execute([
            'sections' => [
                [
                    'content_brief_id' => $this->board->id,
                    'heading' => 'Einführung',
                    'heading_level' => 'h2',
                    'description' => 'Start des Artikels.',
                ],
                [
                    'content_brief_id' => $this->board->id,
                    'heading' => 'Hauptteil',
                    'heading_level' => 'h2',
                ],
                [
                    'content_brief_id' => $this->board->id,
                    'heading' => 'Fazit',
                    'heading_level' => 'h2',
                ],
            ],
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals(3, $result->data['summary']['requested']);
        $this->assertEquals(3, $result->data['summary']['ok']);
        $this->assertEquals(0, $result->data['summary']['failed']);
        $this->assertTrue($result->data['results'][0]['ok']);
        $this->assertTrue($result->data['results'][1]['ok']);
        $this->assertTrue($result->data['results'][2]['ok']);
    }

    /** @test */
    public function it_bulk_creates_sections_with_defaults()
    {
        $tool = new BulkCreateContentBriefSectionsTool();
        $result = $tool->execute([
            'defaults' => [
                'content_brief_id' => $this->board->id,
                'heading_level' => 'h3',
            ],
            'sections' => [
                ['heading' => 'Section A'],
                ['heading' => 'Section B'],
                ['heading' => 'Section C', 'heading_level' => 'h4'], // overrides default
            ],
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals(3, $result->data['summary']['ok']);
        $this->assertEquals('h3', $result->data['results'][0]['data']['heading_level']);
        $this->assertEquals('h3', $result->data['results'][1]['data']['heading_level']);
        $this->assertEquals('h4', $result->data['results'][2]['data']['heading_level']);
    }

    /** @test */
    public function it_bulk_creates_with_partial_failures()
    {
        $tool = new BulkCreateContentBriefSectionsTool();
        $result = $tool->execute([
            'sections' => [
                [
                    'content_brief_id' => $this->board->id,
                    'heading' => 'Valid Section',
                ],
                [
                    'content_brief_id' => 99999,
                    'heading' => 'Invalid Section',
                ],
            ],
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals(2, $result->data['summary']['requested']);
        $this->assertEquals(1, $result->data['summary']['ok']);
        $this->assertEquals(1, $result->data['summary']['failed']);
        $this->assertTrue($result->data['results'][0]['ok']);
        $this->assertFalse($result->data['results'][1]['ok']);
    }

    /** @test */
    public function it_rejects_empty_sections_array()
    {
        $tool = new BulkCreateContentBriefSectionsTool();
        $result = $tool->execute([
            'sections' => [],
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('INVALID_ARGUMENT', $result->errorCode);
    }

    // ─── BULK UPDATE ─────────────────────────────────────────

    /** @test */
    public function it_bulk_updates_sections()
    {
        $section1 = BrandsContentBriefSection::create([
            'content_brief_id' => $this->board->id,
            'order' => 1,
            'heading' => 'Original A',
            'heading_level' => 'h2',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);
        $section2 = BrandsContentBriefSection::create([
            'content_brief_id' => $this->board->id,
            'order' => 2,
            'heading' => 'Original B',
            'heading_level' => 'h2',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new BulkUpdateContentBriefSectionsTool();
        $result = $tool->execute([
            'updates' => [
                [
                    'content_brief_section_id' => $section1->id,
                    'heading' => 'Updated A',
                    'order' => 2,
                ],
                [
                    'content_brief_section_id' => $section2->id,
                    'heading' => 'Updated B',
                    'order' => 1,
                ],
            ],
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals(2, $result->data['summary']['requested']);
        $this->assertEquals(2, $result->data['summary']['ok']);
        $this->assertEquals(0, $result->data['summary']['failed']);
        $this->assertEquals('Updated A', $result->data['results'][0]['data']['heading']);
        $this->assertEquals('Updated B', $result->data['results'][1]['data']['heading']);
    }

    /** @test */
    public function it_bulk_updates_for_reordering()
    {
        $section1 = BrandsContentBriefSection::create([
            'content_brief_id' => $this->board->id,
            'order' => 1,
            'heading' => 'Einführung',
            'heading_level' => 'h2',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);
        $section2 = BrandsContentBriefSection::create([
            'content_brief_id' => $this->board->id,
            'order' => 2,
            'heading' => 'Fazit',
            'heading_level' => 'h2',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);
        $section3 = BrandsContentBriefSection::create([
            'content_brief_id' => $this->board->id,
            'order' => 3,
            'heading' => 'Hauptteil',
            'heading_level' => 'h2',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        // Reorder: move Hauptteil before Fazit
        $tool = new BulkUpdateContentBriefSectionsTool();
        $result = $tool->execute([
            'atomic' => true,
            'updates' => [
                ['content_brief_section_id' => $section1->id, 'order' => 1],
                ['content_brief_section_id' => $section3->id, 'order' => 2],
                ['content_brief_section_id' => $section2->id, 'order' => 3],
            ],
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals(3, $result->data['summary']['ok']);

        // Verify ordering via list
        $listTool = new ListContentBriefSectionsTool();
        $listResult = $listTool->execute([
            'content_brief_id' => $this->board->id,
        ], $this->context);

        $this->assertEquals('Einführung', $listResult->data['sections'][0]['heading']);
        $this->assertEquals('Hauptteil', $listResult->data['sections'][1]['heading']);
        $this->assertEquals('Fazit', $listResult->data['sections'][2]['heading']);
    }

    /** @test */
    public function it_rejects_empty_updates_array()
    {
        $tool = new BulkUpdateContentBriefSectionsTool();
        $result = $tool->execute([
            'updates' => [],
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('INVALID_ARGUMENT', $result->errorCode);
    }

    // ─── GET BOARD WITH SECTIONS ─────────────────────────────

    /** @test */
    public function it_includes_sections_in_get_board_ordered()
    {
        BrandsContentBriefSection::create([
            'content_brief_id' => $this->board->id,
            'order' => 2,
            'heading' => 'Hauptteil',
            'heading_level' => 'h2',
            'description' => 'Der Kern des Artikels.',
            'target_keywords' => ['content strategy'],
            'notes' => 'Min. 500 Wörter.',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);
        BrandsContentBriefSection::create([
            'content_brief_id' => $this->board->id,
            'order' => 1,
            'heading' => 'Einführung',
            'heading_level' => 'h2',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);
        BrandsContentBriefSection::create([
            'content_brief_id' => $this->board->id,
            'order' => 3,
            'heading' => 'Fazit',
            'heading_level' => 'h2',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new GetContentBriefBoardTool();
        $result = $tool->execute(['id' => $this->board->id], $this->context);

        $this->assertTrue($result->success);
        $this->assertArrayHasKey('sections', $result->data);
        $this->assertCount(3, $result->data['sections']);

        // Verify ordered by order field
        $this->assertEquals('Einführung', $result->data['sections'][0]['heading']);
        $this->assertEquals(1, $result->data['sections'][0]['order']);
        $this->assertEquals('Hauptteil', $result->data['sections'][1]['heading']);
        $this->assertEquals(2, $result->data['sections'][1]['order']);
        $this->assertEquals('Fazit', $result->data['sections'][2]['heading']);
        $this->assertEquals(3, $result->data['sections'][2]['order']);

        // Verify section fields
        $this->assertEquals('h2', $result->data['sections'][1]['heading_level']);
        $this->assertEquals('H2', $result->data['sections'][1]['heading_level_label']);
        $this->assertEquals('Der Kern des Artikels.', $result->data['sections'][1]['description']);
        $this->assertEquals(['content strategy'], $result->data['sections'][1]['target_keywords']);
        $this->assertEquals('Min. 500 Wörter.', $result->data['sections'][1]['notes']);
    }

    /** @test */
    public function it_returns_empty_sections_array_when_no_sections()
    {
        $tool = new GetContentBriefBoardTool();
        $result = $tool->execute(['id' => $this->board->id], $this->context);

        $this->assertTrue($result->success);
        $this->assertArrayHasKey('sections', $result->data);
        $this->assertCount(0, $result->data['sections']);
    }

    // ─── CASCADE DELETE ──────────────────────────────────────

    /** @test */
    public function it_cascades_sections_when_content_brief_deleted()
    {
        $section = BrandsContentBriefSection::create([
            'content_brief_id' => $this->board->id,
            'order' => 1,
            'heading' => 'Test Section',
            'heading_level' => 'h2',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $sectionId = $section->id;
        $this->board->delete();

        $this->assertNull(BrandsContentBriefSection::find($sectionId));
    }

    // ─── MODEL RELATIONSHIPS ─────────────────────────────────

    /** @test */
    public function it_has_correct_content_brief_relationship()
    {
        $section = BrandsContentBriefSection::create([
            'content_brief_id' => $this->board->id,
            'order' => 1,
            'heading' => 'Test',
            'heading_level' => 'h2',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $this->assertEquals($this->board->id, $section->contentBrief->id);
        $this->assertEquals('SEO Guide: Content Marketing', $section->contentBrief->name);
    }

    /** @test */
    public function it_has_sections_relationship_on_board()
    {
        BrandsContentBriefSection::create([
            'content_brief_id' => $this->board->id,
            'order' => 1,
            'heading' => 'Section 1',
            'heading_level' => 'h2',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);
        BrandsContentBriefSection::create([
            'content_brief_id' => $this->board->id,
            'order' => 2,
            'heading' => 'Section 2',
            'heading_level' => 'h3',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $this->board->load('sections');
        $this->assertCount(2, $this->board->sections);
        $this->assertEquals('Section 1', $this->board->sections->first()->heading);
    }

    // ─── ENUM CONSTANTS ──────────────────────────────────────

    /** @test */
    public function it_defines_all_heading_levels()
    {
        $expected = ['h2', 'h3', 'h4'];
        $this->assertEquals($expected, array_keys(BrandsContentBriefSection::HEADING_LEVELS));
    }

    // ─── NO TEXT CONTENT FIELD ────────────────────────────────

    /** @test */
    public function it_has_no_text_content_field()
    {
        $section = BrandsContentBriefSection::create([
            'content_brief_id' => $this->board->id,
            'order' => 1,
            'heading' => 'Test',
            'heading_level' => 'h2',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        // Bewusste Designentscheidung: Kein text_content / Fließtext-Feld
        $this->assertNotContains('text_content', $section->getFillable());
        $this->assertNotContains('content', $section->getFillable());
        $this->assertNotContains('body', $section->getFillable());
    }

    // ─── DISPLAY NAME ────────────────────────────────────────

    /** @test */
    public function it_returns_heading_as_display_name()
    {
        $section = BrandsContentBriefSection::create([
            'content_brief_id' => $this->board->id,
            'order' => 1,
            'heading' => 'Meine Überschrift',
            'heading_level' => 'h2',
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $this->assertEquals('Meine Überschrift', $section->getDisplayName());
    }
}

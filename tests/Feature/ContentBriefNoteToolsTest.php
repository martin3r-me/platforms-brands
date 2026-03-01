<?php

namespace Platform\Brands\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsContentBriefBoard;
use Platform\Brands\Models\BrandsContentBriefNote;
use Platform\Brands\Tools\CreateContentBriefNoteTool;
use Platform\Brands\Tools\ListContentBriefNotesTool;
use Platform\Brands\Tools\UpdateContentBriefNoteTool;
use Platform\Brands\Tools\DeleteContentBriefNoteTool;
use Platform\Brands\Tools\GetContentBriefBoardTool;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Models\User;
use Platform\Core\Models\Team;

class ContentBriefNoteToolsTest extends TestCase
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
            'name' => 'Gastronomie Content Brief',
            'content_type' => 'guide',
            'search_intent' => 'informational',
            'status' => 'draft',
            'brand_id' => $this->brand->id,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $this->context = new ToolContext(user: $this->user);
    }

    // ─── CREATE NOTE ─────────────────────────────────────────

    /** @test */
    public function it_creates_a_content_brief_note_with_all_fields()
    {
        $tool = new CreateContentBriefNoteTool();
        $result = $tool->execute([
            'content_brief_id' => $this->board->id,
            'note_type' => 'instruction',
            'content' => 'Duze den Leser, schreibe aus Gastronomen-Perspektive',
            'order' => 1,
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals($this->board->id, $result->data['content_brief_id']);
        $this->assertEquals('Gastronomie Content Brief', $result->data['content_brief_name']);
        $this->assertEquals('instruction', $result->data['note_type']);
        $this->assertEquals('Anweisung', $result->data['note_type_label']);
        $this->assertEquals('Duze den Leser, schreibe aus Gastronomen-Perspektive', $result->data['content']);
        $this->assertEquals(1, $result->data['order']);
        $this->assertNotNull($result->data['created_at']);
    }

    /** @test */
    public function it_creates_a_note_with_minimal_params()
    {
        $tool = new CreateContentBriefNoteTool();
        $result = $tool->execute([
            'content_brief_id' => $this->board->id,
            'note_type' => 'avoid',
            'content' => 'Keine generischen KI-Floskeln',
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals('avoid', $result->data['note_type']);
        $this->assertEquals('Vermeiden', $result->data['note_type_label']);
        $this->assertEquals('Keine generischen KI-Floskeln', $result->data['content']);
        $this->assertEquals(1, $result->data['order']); // auto-calculated
    }

    /** @test */
    public function it_creates_notes_for_all_note_types()
    {
        $tool = new CreateContentBriefNoteTool();

        $types = [
            'instruction' => 'Anweisung',
            'source' => 'Quelle',
            'constraint' => 'Einschränkung',
            'example' => 'Beispiel',
            'avoid' => 'Vermeiden',
        ];

        foreach ($types as $type => $label) {
            $result = $tool->execute([
                'content_brief_id' => $this->board->id,
                'note_type' => $type,
                'content' => "Test content for $type",
            ], $this->context);

            $this->assertTrue($result->success, "Failed for note_type: $type");
            $this->assertEquals($type, $result->data['note_type']);
            $this->assertEquals($label, $result->data['note_type_label']);
        }
    }

    /** @test */
    public function it_auto_increments_order()
    {
        $tool = new CreateContentBriefNoteTool();

        $result1 = $tool->execute([
            'content_brief_id' => $this->board->id,
            'note_type' => 'instruction',
            'content' => 'First note',
        ], $this->context);
        $this->assertEquals(1, $result1->data['order']);

        $result2 = $tool->execute([
            'content_brief_id' => $this->board->id,
            'note_type' => 'instruction',
            'content' => 'Second note',
        ], $this->context);
        $this->assertEquals(2, $result2->data['order']);

        $result3 = $tool->execute([
            'content_brief_id' => $this->board->id,
            'note_type' => 'avoid',
            'content' => 'Third note',
        ], $this->context);
        $this->assertEquals(3, $result3->data['order']);
    }

    /** @test */
    public function it_allows_explicit_order()
    {
        $tool = new CreateContentBriefNoteTool();
        $result = $tool->execute([
            'content_brief_id' => $this->board->id,
            'note_type' => 'instruction',
            'content' => 'Custom order note',
            'order' => 10,
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals(10, $result->data['order']);
    }

    /** @test */
    public function it_rejects_invalid_note_type()
    {
        $tool = new CreateContentBriefNoteTool();
        $result = $tool->execute([
            'content_brief_id' => $this->board->id,
            'note_type' => 'invalid_type',
            'content' => 'Test',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    /** @test */
    public function it_requires_content_brief_id()
    {
        $tool = new CreateContentBriefNoteTool();
        $result = $tool->execute([
            'note_type' => 'instruction',
            'content' => 'Test',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    /** @test */
    public function it_requires_note_type()
    {
        $tool = new CreateContentBriefNoteTool();
        $result = $tool->execute([
            'content_brief_id' => $this->board->id,
            'content' => 'Test',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    /** @test */
    public function it_requires_content()
    {
        $tool = new CreateContentBriefNoteTool();
        $result = $tool->execute([
            'content_brief_id' => $this->board->id,
            'note_type' => 'instruction',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    /** @test */
    public function it_rejects_nonexistent_content_brief()
    {
        $tool = new CreateContentBriefNoteTool();
        $result = $tool->execute([
            'content_brief_id' => 99999,
            'note_type' => 'instruction',
            'content' => 'Test',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('CONTENT_BRIEF_BOARD_NOT_FOUND', $result->errorCode);
    }

    /** @test */
    public function it_stores_long_text_content()
    {
        $longContent = str_repeat('Lorem ipsum dolor sit amet. ', 500);

        $tool = new CreateContentBriefNoteTool();
        $result = $tool->execute([
            'content_brief_id' => $this->board->id,
            'note_type' => 'source',
            'content' => $longContent,
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals($longContent, $result->data['content']);

        // Verify persisted in DB
        $note = BrandsContentBriefNote::find($result->data['id']);
        $this->assertEquals($longContent, $note->content);
    }

    // ─── LIST NOTES ──────────────────────────────────────────

    /** @test */
    public function it_lists_notes_ordered_and_grouped()
    {
        BrandsContentBriefNote::create([
            'content_brief_id' => $this->board->id,
            'note_type' => 'avoid',
            'content' => 'Keine KI-Floskeln',
            'order' => 1,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);
        BrandsContentBriefNote::create([
            'content_brief_id' => $this->board->id,
            'note_type' => 'instruction',
            'content' => 'Duze den Leser',
            'order' => 1,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);
        BrandsContentBriefNote::create([
            'content_brief_id' => $this->board->id,
            'note_type' => 'instruction',
            'content' => 'Schreibe aus Gastronomen-Perspektive',
            'order' => 2,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new ListContentBriefNotesTool();
        $result = $tool->execute([
            'content_brief_id' => $this->board->id,
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals(3, $result->data['count']);

        // Check grouped structure
        $this->assertArrayHasKey('grouped', $result->data);
        $this->assertArrayHasKey('instruction', $result->data['grouped']);
        $this->assertArrayHasKey('avoid', $result->data['grouped']);
        $this->assertEquals(2, $result->data['grouped']['instruction']['count']);
        $this->assertEquals(1, $result->data['grouped']['avoid']['count']);
        $this->assertEquals('Anweisung', $result->data['grouped']['instruction']['label']);
        $this->assertEquals('Vermeiden', $result->data['grouped']['avoid']['label']);
    }

    /** @test */
    public function it_filters_notes_by_note_type()
    {
        BrandsContentBriefNote::create([
            'content_brief_id' => $this->board->id,
            'note_type' => 'instruction',
            'content' => 'Instruction note',
            'order' => 1,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);
        BrandsContentBriefNote::create([
            'content_brief_id' => $this->board->id,
            'note_type' => 'source',
            'content' => 'Source note',
            'order' => 1,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new ListContentBriefNotesTool();
        $result = $tool->execute([
            'content_brief_id' => $this->board->id,
            'note_type' => 'source',
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals(1, $result->data['count']);
        $this->assertEquals('Source note', $result->data['notes'][0]['content']);
    }

    /** @test */
    public function it_returns_empty_list_when_no_notes()
    {
        $tool = new ListContentBriefNotesTool();
        $result = $tool->execute([
            'content_brief_id' => $this->board->id,
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals(0, $result->data['count']);
        $this->assertEmpty($result->data['notes']);
        $this->assertEmpty($result->data['grouped']);
    }

    /** @test */
    public function it_requires_content_brief_id_for_listing()
    {
        $tool = new ListContentBriefNotesTool();
        $result = $tool->execute([], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    /** @test */
    public function it_rejects_nonexistent_content_brief_for_listing()
    {
        $tool = new ListContentBriefNotesTool();
        $result = $tool->execute([
            'content_brief_id' => 99999,
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('CONTENT_BRIEF_BOARD_NOT_FOUND', $result->errorCode);
    }

    /** @test */
    public function it_rejects_invalid_note_type_for_listing()
    {
        $tool = new ListContentBriefNotesTool();
        $result = $tool->execute([
            'content_brief_id' => $this->board->id,
            'note_type' => 'invalid',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    /** @test */
    public function it_sorts_notes_by_order_within_type()
    {
        BrandsContentBriefNote::create([
            'content_brief_id' => $this->board->id,
            'note_type' => 'instruction',
            'content' => 'Third',
            'order' => 3,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);
        BrandsContentBriefNote::create([
            'content_brief_id' => $this->board->id,
            'note_type' => 'instruction',
            'content' => 'First',
            'order' => 1,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);
        BrandsContentBriefNote::create([
            'content_brief_id' => $this->board->id,
            'note_type' => 'instruction',
            'content' => 'Second',
            'order' => 2,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new ListContentBriefNotesTool();
        $result = $tool->execute([
            'content_brief_id' => $this->board->id,
            'note_type' => 'instruction',
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals('First', $result->data['notes'][0]['content']);
        $this->assertEquals('Second', $result->data['notes'][1]['content']);
        $this->assertEquals('Third', $result->data['notes'][2]['content']);
        $this->assertEquals(1, $result->data['notes'][0]['order']);
        $this->assertEquals(2, $result->data['notes'][1]['order']);
        $this->assertEquals(3, $result->data['notes'][2]['order']);
    }

    // ─── UPDATE NOTE ─────────────────────────────────────────

    /** @test */
    public function it_updates_a_note()
    {
        $note = BrandsContentBriefNote::create([
            'content_brief_id' => $this->board->id,
            'note_type' => 'instruction',
            'content' => 'Original content',
            'order' => 1,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new UpdateContentBriefNoteTool();
        $result = $tool->execute([
            'content_brief_note_id' => $note->id,
            'note_type' => 'constraint',
            'content' => 'Updated content',
            'order' => 5,
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals('constraint', $result->data['note_type']);
        $this->assertEquals('Einschränkung', $result->data['note_type_label']);
        $this->assertEquals('Updated content', $result->data['content']);
        $this->assertEquals(5, $result->data['order']);
    }

    /** @test */
    public function it_updates_only_provided_fields()
    {
        $note = BrandsContentBriefNote::create([
            'content_brief_id' => $this->board->id,
            'note_type' => 'instruction',
            'content' => 'Original content',
            'order' => 1,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new UpdateContentBriefNoteTool();
        $result = $tool->execute([
            'content_brief_note_id' => $note->id,
            'content' => 'New content only',
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals('New content only', $result->data['content']);
        $this->assertEquals('instruction', $result->data['note_type']); // unchanged
        $this->assertEquals(1, $result->data['order']); // unchanged
    }

    /** @test */
    public function it_rejects_invalid_note_type_on_update()
    {
        $note = BrandsContentBriefNote::create([
            'content_brief_id' => $this->board->id,
            'note_type' => 'instruction',
            'content' => 'Test',
            'order' => 1,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new UpdateContentBriefNoteTool();
        $result = $tool->execute([
            'content_brief_note_id' => $note->id,
            'note_type' => 'invalid_type',
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('VALIDATION_ERROR', $result->errorCode);
    }

    /** @test */
    public function it_returns_not_found_when_updating_nonexistent_note()
    {
        $tool = new UpdateContentBriefNoteTool();
        $result = $tool->execute([
            'content_brief_note_id' => 99999,
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('CONTENT_BRIEF_NOTE_NOT_FOUND', $result->errorCode);
    }

    // ─── DELETE NOTE ─────────────────────────────────────────

    /** @test */
    public function it_deletes_a_note()
    {
        $note = BrandsContentBriefNote::create([
            'content_brief_id' => $this->board->id,
            'note_type' => 'avoid',
            'content' => 'To Be Deleted',
            'order' => 1,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new DeleteContentBriefNoteTool();
        $result = $tool->execute([
            'content_brief_note_id' => $note->id,
        ], $this->context);

        $this->assertTrue($result->success);
        $this->assertEquals('avoid', $result->data['note_type']);
        $this->assertNull(BrandsContentBriefNote::find($note->id));
    }

    /** @test */
    public function it_returns_not_found_when_deleting_nonexistent_note()
    {
        $tool = new DeleteContentBriefNoteTool();
        $result = $tool->execute([
            'content_brief_note_id' => 99999,
        ], $this->context);

        $this->assertFalse($result->success);
        $this->assertEquals('CONTENT_BRIEF_NOTE_NOT_FOUND', $result->errorCode);
    }

    // ─── GET BOARD WITH NOTES ────────────────────────────────

    /** @test */
    public function it_includes_notes_grouped_by_type_in_get_board()
    {
        BrandsContentBriefNote::create([
            'content_brief_id' => $this->board->id,
            'note_type' => 'instruction',
            'content' => 'Duze den Leser',
            'order' => 1,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);
        BrandsContentBriefNote::create([
            'content_brief_id' => $this->board->id,
            'note_type' => 'instruction',
            'content' => 'Schreibe aus Gastronomen-Perspektive',
            'order' => 2,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);
        BrandsContentBriefNote::create([
            'content_brief_id' => $this->board->id,
            'note_type' => 'source',
            'content' => 'https://dehoga.de/statistik-2025 als Datenquelle nutzen',
            'order' => 1,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);
        BrandsContentBriefNote::create([
            'content_brief_id' => $this->board->id,
            'note_type' => 'constraint',
            'content' => 'Keine Produktempfehlung für Einzelanbieter',
            'order' => 1,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);
        BrandsContentBriefNote::create([
            'content_brief_id' => $this->board->id,
            'note_type' => 'avoid',
            'content' => 'Keine generischen KI-Floskeln',
            'order' => 1,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);
        BrandsContentBriefNote::create([
            'content_brief_id' => $this->board->id,
            'note_type' => 'example',
            'content' => 'Tonalität wie in Artikel X',
            'order' => 1,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $tool = new GetContentBriefBoardTool();
        $result = $tool->execute(['id' => $this->board->id], $this->context);

        $this->assertTrue($result->success);
        $this->assertArrayHasKey('notes', $result->data);

        // Grouped by note_type
        $notes = $result->data['notes'];
        $this->assertArrayHasKey('instruction', $notes);
        $this->assertArrayHasKey('source', $notes);
        $this->assertArrayHasKey('constraint', $notes);
        $this->assertArrayHasKey('example', $notes);
        $this->assertArrayHasKey('avoid', $notes);

        // Check instruction group
        $this->assertEquals('Anweisung', $notes['instruction']['label']);
        $this->assertEquals(2, $notes['instruction']['count']);
        $this->assertEquals('Duze den Leser', $notes['instruction']['notes'][0]['content']);
        $this->assertEquals('Schreibe aus Gastronomen-Perspektive', $notes['instruction']['notes'][1]['content']);

        // Check source group
        $this->assertEquals('Quelle', $notes['source']['label']);
        $this->assertEquals(1, $notes['source']['count']);

        // Check constraint group
        $this->assertEquals('Einschränkung', $notes['constraint']['label']);

        // Check avoid group
        $this->assertEquals('Vermeiden', $notes['avoid']['label']);

        // Check example group
        $this->assertEquals('Beispiel', $notes['example']['label']);
    }

    /** @test */
    public function it_returns_empty_notes_when_no_notes_on_board()
    {
        $tool = new GetContentBriefBoardTool();
        $result = $tool->execute(['id' => $this->board->id], $this->context);

        $this->assertTrue($result->success);
        $this->assertArrayHasKey('notes', $result->data);
        $this->assertEmpty($result->data['notes']);
    }

    // ─── CASCADE DELETE ──────────────────────────────────────

    /** @test */
    public function it_cascades_notes_when_content_brief_deleted()
    {
        $note = BrandsContentBriefNote::create([
            'content_brief_id' => $this->board->id,
            'note_type' => 'instruction',
            'content' => 'Test Note',
            'order' => 1,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $noteId = $note->id;
        $this->board->delete();

        $this->assertNull(BrandsContentBriefNote::find($noteId));
    }

    // ─── MODEL RELATIONSHIPS ─────────────────────────────────

    /** @test */
    public function it_has_correct_content_brief_relationship()
    {
        $note = BrandsContentBriefNote::create([
            'content_brief_id' => $this->board->id,
            'note_type' => 'source',
            'content' => 'Test source',
            'order' => 1,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $this->assertEquals($this->board->id, $note->contentBrief->id);
        $this->assertEquals('Gastronomie Content Brief', $note->contentBrief->name);
    }

    /** @test */
    public function it_has_notes_relationship_on_board()
    {
        BrandsContentBriefNote::create([
            'content_brief_id' => $this->board->id,
            'note_type' => 'instruction',
            'content' => 'Note 1',
            'order' => 1,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);
        BrandsContentBriefNote::create([
            'content_brief_id' => $this->board->id,
            'note_type' => 'avoid',
            'content' => 'Note 2',
            'order' => 2,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $this->board->load('notes');
        $this->assertCount(2, $this->board->notes);
    }

    // ─── ENUM CONSTANTS ──────────────────────────────────────

    /** @test */
    public function it_defines_all_note_types()
    {
        $expected = ['instruction', 'source', 'constraint', 'example', 'avoid'];
        $this->assertEquals($expected, array_keys(BrandsContentBriefNote::NOTE_TYPES));
    }

    // ─── DISPLAY NAME ────────────────────────────────────────

    /** @test */
    public function it_returns_type_and_content_as_display_name()
    {
        $note = BrandsContentBriefNote::create([
            'content_brief_id' => $this->board->id,
            'note_type' => 'instruction',
            'content' => 'Duze den Leser, schreibe aus Gastronomen-Perspektive',
            'order' => 1,
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $displayName = $note->getDisplayName();
        $this->assertStringStartsWith('Anweisung: ', $displayName);
        $this->assertStringContainsString('Duze den Leser', $displayName);
    }

    // ─── TICKET EXAMPLE DATA ─────────────────────────────────

    /** @test */
    public function it_handles_all_ticket_example_notes()
    {
        $tool = new CreateContentBriefNoteTool();

        // instruction example
        $result = $tool->execute([
            'content_brief_id' => $this->board->id,
            'note_type' => 'instruction',
            'content' => 'Duze den Leser, schreibe aus Gastronomen-Perspektive',
        ], $this->context);
        $this->assertTrue($result->success);

        // source example
        $result = $tool->execute([
            'content_brief_id' => $this->board->id,
            'note_type' => 'source',
            'content' => 'https://dehoga.de/statistik-2025 als Datenquelle nutzen',
        ], $this->context);
        $this->assertTrue($result->success);

        // constraint example
        $result = $tool->execute([
            'content_brief_id' => $this->board->id,
            'note_type' => 'constraint',
            'content' => 'Keine Produktempfehlung für Einzelanbieter',
        ], $this->context);
        $this->assertTrue($result->success);

        // avoid example
        $result = $tool->execute([
            'content_brief_id' => $this->board->id,
            'note_type' => 'avoid',
            'content' => 'Keine generischen KI-Floskeln',
        ], $this->context);
        $this->assertTrue($result->success);

        // example example
        $result = $tool->execute([
            'content_brief_id' => $this->board->id,
            'note_type' => 'example',
            'content' => 'Tonalität wie in Artikel X',
        ], $this->context);
        $this->assertTrue($result->success);

        // Verify all 5 notes were created
        $listTool = new ListContentBriefNotesTool();
        $listResult = $listTool->execute([
            'content_brief_id' => $this->board->id,
        ], $this->context);

        $this->assertEquals(5, $listResult->data['count']);
        $this->assertCount(5, array_keys($listResult->data['grouped']));
    }
}

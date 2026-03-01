<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsContentBriefBoard;
use Platform\Brands\Models\BrandsContentBriefLink;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateContentBriefLinkTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_brief_links.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/content_brief_links - Erstellt eine Verlinkung zwischen zwei Content Briefs. REST-Parameter: source_content_brief_id (required, integer) - Quell-Content-Brief-ID. target_content_brief_id (required, integer) - Ziel-Content-Brief-ID. link_type (required, string) - pillar_to_cluster|cluster_to_pillar|related|see_also. anchor_hint (optional, string) - Vorgeschlagener Ankertext.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'source_content_brief_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Quell-Content-Briefs (ERFORDERLICH).'
                ],
                'target_content_brief_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Ziel-Content-Briefs (ERFORDERLICH).'
                ],
                'link_type' => [
                    'type' => 'string',
                    'enum' => ['pillar_to_cluster', 'cluster_to_pillar', 'related', 'see_also'],
                    'description' => 'Art der Verlinkung.'
                ],
                'anchor_hint' => [
                    'type' => 'string',
                    'description' => 'Vorgeschlagener Ankertext für den Link.'
                ],
            ],
            'required' => ['source_content_brief_id', 'target_content_brief_id', 'link_type']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $sourceId = $arguments['source_content_brief_id'] ?? null;
            $targetId = $arguments['target_content_brief_id'] ?? null;
            $linkType = $arguments['link_type'] ?? null;

            if (!$sourceId) {
                return ToolResult::error('VALIDATION_ERROR', 'source_content_brief_id ist erforderlich.');
            }
            if (!$targetId) {
                return ToolResult::error('VALIDATION_ERROR', 'target_content_brief_id ist erforderlich.');
            }
            if (!$linkType) {
                return ToolResult::error('VALIDATION_ERROR', 'link_type ist erforderlich.');
            }

            // Validate link_type enum
            if (!array_key_exists($linkType, BrandsContentBriefLink::LINK_TYPES)) {
                return ToolResult::error('VALIDATION_ERROR', 'Ungültiger link_type. Erlaubt: ' . implode(', ', array_keys(BrandsContentBriefLink::LINK_TYPES)));
            }

            // No self-links
            if ($sourceId === $targetId) {
                return ToolResult::error('VALIDATION_ERROR', 'Ein Content Brief kann nicht mit sich selbst verlinkt werden (Self-Link).');
            }

            // Check source exists
            $source = BrandsContentBriefBoard::find($sourceId);
            if (!$source) {
                return ToolResult::error('CONTENT_BRIEF_BOARD_NOT_FOUND', 'Das Quell-Content-Brief wurde nicht gefunden.');
            }

            // Check target exists
            $target = BrandsContentBriefBoard::find($targetId);
            if (!$target) {
                return ToolResult::error('CONTENT_BRIEF_BOARD_NOT_FOUND', 'Das Ziel-Content-Brief wurde nicht gefunden.');
            }

            // Authorization: user must be able to update source
            try {
                Gate::forUser($context->user)->authorize('update', $source);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Links für dieses Content Brief erstellen (Policy).');
            }

            // Check for duplicates
            $exists = BrandsContentBriefLink::where('source_content_brief_id', $sourceId)
                ->where('target_content_brief_id', $targetId)
                ->where('link_type', $linkType)
                ->exists();

            if ($exists) {
                return ToolResult::error('DUPLICATE_LINK', 'Diese Verlinkung existiert bereits (gleiche Quelle, Ziel und Typ).');
            }

            $link = BrandsContentBriefLink::create([
                'source_content_brief_id' => $sourceId,
                'target_content_brief_id' => $targetId,
                'link_type' => $linkType,
                'anchor_hint' => $arguments['anchor_hint'] ?? null,
                'user_id' => $context->user->id,
                'team_id' => $source->team_id,
            ]);

            $link->load(['sourceContentBrief', 'targetContentBrief']);

            return ToolResult::success([
                'id' => $link->id,
                'source_content_brief_id' => $link->source_content_brief_id,
                'source_content_brief_name' => $link->sourceContentBrief->name,
                'target_content_brief_id' => $link->target_content_brief_id,
                'target_content_brief_name' => $link->targetContentBrief->name,
                'link_type' => $link->link_type,
                'link_type_label' => BrandsContentBriefLink::LINK_TYPES[$link->link_type] ?? $link->link_type,
                'anchor_hint' => $link->anchor_hint,
                'created_at' => $link->created_at->toIso8601String(),
                'message' => "Link '{$link->sourceContentBrief->name}' → '{$link->targetContentBrief->name}' ({$link->link_type}) erfolgreich erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Content Brief Links: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'content_brief_link', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}

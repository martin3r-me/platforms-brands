<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSeoKeyword;
use Platform\Brands\Models\BrandsSeoKeywordContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateSeoKeywordContextTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.seo_keyword_contexts.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/seo_keywords/{seo_keyword_id}/contexts - Verknüpft ein SEO Keyword mit einem Kontext (Content Board Block, Note, URL etc.). '
            . 'Lose Kopplung: Die Boards bleiben unabhängig, aber die Verknüpfung zeigt "Dieses Keyword wird durch diesen Content bedient" und umgekehrt. '
            . 'REST-Parameter: seo_keyword_id (required, integer), context_type (required, string – z.B. "content_board_block", "note", "url"), '
            . 'context_id (required, integer – ID des verknüpften Elements), label (optional, string – Anzeigename), '
            . 'url (optional, string – zugehörige URL), meta (optional, object – zusätzliche Metadaten). '
            . 'Workflow: 1) SEO Board: Keyword "arbeitsmedizin köln" → content_status: planned. '
            . '2) Content Board: Artikel erstellen, dabei Keyword verlinken via dieses Tool. '
            . '3) Content geht live → published_url über UpdateSeoKeyword ins Keyword zurück. '
            . '4) SEO Board: Keyword zeigt verlinkten Content + Ranking.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'seo_keyword_id' => [
                    'type' => 'integer',
                    'description' => 'ID des SEO Keywords (ERFORDERLICH).'
                ],
                'context_type' => [
                    'type' => 'string',
                    'description' => 'Typ des verlinkten Kontexts (ERFORDERLICH). Beispiele: "content_board_block", "note", "url", "kanban_card".'
                ],
                'context_id' => [
                    'type' => 'integer',
                    'description' => 'ID des verlinkten Elements (ERFORDERLICH).'
                ],
                'label' => [
                    'type' => 'string',
                    'description' => 'Optional: Anzeigename für den Context-Link (z.B. "Blog-Artikel: Arbeitsmedizin in Köln").'
                ],
                'url' => [
                    'type' => 'string',
                    'description' => 'Optional: Zugehörige URL (z.B. die Published-URL des Contents).'
                ],
                'meta' => [
                    'type' => 'object',
                    'description' => 'Optional: Zusätzliche Metadaten als JSON-Objekt.'
                ],
            ],
            'required' => ['seo_keyword_id', 'context_type', 'context_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $seoKeywordId = $arguments['seo_keyword_id'] ?? null;
            if (!$seoKeywordId) {
                return ToolResult::error('VALIDATION_ERROR', 'seo_keyword_id ist erforderlich.');
            }

            $contextType = $arguments['context_type'] ?? null;
            if (!$contextType) {
                return ToolResult::error('VALIDATION_ERROR', 'context_type ist erforderlich.');
            }

            $contextId = $arguments['context_id'] ?? null;
            if (!$contextId) {
                return ToolResult::error('VALIDATION_ERROR', 'context_id ist erforderlich.');
            }

            $seoKeyword = BrandsSeoKeyword::find($seoKeywordId);
            if (!$seoKeyword) {
                return ToolResult::error('SEO_KEYWORD_NOT_FOUND', 'Das angegebene SEO Keyword wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $seoKeyword);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Context-Links für dieses Keyword erstellen (Policy).');
            }

            // Duplikat-Check
            $existing = BrandsSeoKeywordContext::where('seo_keyword_id', $seoKeywordId)
                ->where('context_type', $contextType)
                ->where('context_id', $contextId)
                ->first();

            if ($existing) {
                return ToolResult::error('DUPLICATE_CONTEXT', 'Dieser Context-Link existiert bereits (ID: ' . $existing->id . ').');
            }

            $contextLink = BrandsSeoKeywordContext::create([
                'seo_keyword_id' => $seoKeywordId,
                'context_type' => $contextType,
                'context_id' => $contextId,
                'label' => $arguments['label'] ?? null,
                'url' => $arguments['url'] ?? null,
                'meta' => $arguments['meta'] ?? null,
            ]);

            return ToolResult::success([
                'id' => $contextLink->id,
                'uuid' => $contextLink->uuid,
                'seo_keyword_id' => $seoKeyword->id,
                'keyword' => $seoKeyword->keyword,
                'context_type' => $contextLink->context_type,
                'context_id' => $contextLink->context_id,
                'label' => $contextLink->label,
                'url' => $contextLink->url,
                'meta' => $contextLink->meta,
                'created_at' => $contextLink->created_at->toIso8601String(),
                'message' => "Context-Link erstellt: '{$seoKeyword->keyword}' ↔ {$contextType}:{$contextId}"
                    . ($contextLink->label ? " ({$contextLink->label})" : '') . '.'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Context-Links: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'seo_keyword', 'context', 'link', 'content', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}

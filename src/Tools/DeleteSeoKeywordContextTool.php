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

class DeleteSeoKeywordContextTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.seo_keyword_contexts.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/seo_keywords/{seo_keyword_id}/contexts/{context_link_id} - Entfernt eine Verknüpfung zwischen einem SEO Keyword und einem Kontext. '
            . 'REST-Parameter: seo_keyword_id (required, integer), context_link_id (required, integer – ID des Context-Links).';
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
                'context_link_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden Context-Links (ERFORDERLICH).'
                ],
            ],
            'required' => ['seo_keyword_id', 'context_link_id']
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

            $contextLinkId = $arguments['context_link_id'] ?? null;
            if (!$contextLinkId) {
                return ToolResult::error('VALIDATION_ERROR', 'context_link_id ist erforderlich.');
            }

            $seoKeyword = BrandsSeoKeyword::find($seoKeywordId);
            if (!$seoKeyword) {
                return ToolResult::error('SEO_KEYWORD_NOT_FOUND', 'Das angegebene SEO Keyword wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $seoKeyword);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Context-Links für dieses Keyword entfernen (Policy).');
            }

            $contextLink = BrandsSeoKeywordContext::where('id', $contextLinkId)
                ->where('seo_keyword_id', $seoKeywordId)
                ->first();

            if (!$contextLink) {
                return ToolResult::error('CONTEXT_LINK_NOT_FOUND', 'Der angegebene Context-Link wurde nicht gefunden oder gehört nicht zu diesem Keyword.');
            }

            $contextType = $contextLink->context_type;
            $contextId = $contextLink->context_id;
            $label = $contextLink->label;

            $contextLink->delete();

            return ToolResult::success([
                'seo_keyword_id' => $seoKeyword->id,
                'keyword' => $seoKeyword->keyword,
                'deleted_context_link_id' => $contextLinkId,
                'context_type' => $contextType,
                'context_id' => $contextId,
                'message' => "Context-Link entfernt: '{$seoKeyword->keyword}' ↔ {$contextType}:{$contextId}"
                    . ($label ? " ({$label})" : '') . '.'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Context-Links: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'seo_keyword', 'context', 'link', 'delete'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => true,
            'side_effects' => ['deletes'],
        ];
    }
}

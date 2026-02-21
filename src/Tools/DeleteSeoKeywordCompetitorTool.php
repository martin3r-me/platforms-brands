<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSeoKeywordCompetitor;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteSeoKeywordCompetitorTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.seo_keyword_competitors.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/seo_keyword_competitors/{id} - Löscht ein Competitor-Ranking. REST-Parameter: id (required, integer) - ID des Competitor-Rankings.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID des Competitor-Rankings.'
                ]
            ],
            'required' => ['id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            if (empty($arguments['id'])) {
                return ToolResult::error('VALIDATION_ERROR', 'Competitor-Ranking-ID ist erforderlich.');
            }

            $competitor = BrandsSeoKeywordCompetitor::with('seoKeyword')->find($arguments['id']);
            if (!$competitor) {
                return ToolResult::error('COMPETITOR_NOT_FOUND', 'Das angegebene Competitor-Ranking wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('delete', $competitor);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Competitor-Ranking nicht löschen (Policy).');
            }

            $keyword = $competitor->seoKeyword?->keyword ?? 'Unbekannt';
            $domain = $competitor->domain;
            $competitor->delete();

            return ToolResult::success([
                'deleted' => true,
                'message' => "Competitor-Ranking '{$domain}' für Keyword '{$keyword}' wurde gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Competitor-Rankings: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'seo_keyword', 'competitor', 'delete'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'destructive',
            'idempotent' => true,
            'side_effects' => ['deletes'],
        ];
    }
}

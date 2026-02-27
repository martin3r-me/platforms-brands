<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSeoBoard;
use Platform\Brands\Services\SeoKeywordCurationService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CurateSeoKeywordsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.seo_keywords.CURATE';
    }

    public function getDescription(): string
    {
        return 'POST /brands/seo_boards/{seo_board_id}/keywords/curate - Bereinigt Keywords eines SEO Boards regelbasiert. '
            . 'Entfernt automatisch: Competitor-Markennamen, Job/Karriere-Keywords, Personen-Namen, lokale Suchen (Stadt+Dienstleister), '
            . 'Vermittlungs-Intent (finden/suchen/buchen — User sucht Dienstleister, nicht Software). '
            . 'WICHTIG: Standardmäßig dry_run=true — zeigt nur was entfernt würde. Mit dry_run=false werden Keywords tatsächlich gelöscht. '
            . 'Nutze custom_exclude für branchen-spezifische Ausschlüsse (z.B. ["sql", "excel"] wenn irrelevant). '
            . 'Nutze custom_include um bestimmte Keywords vor dem Löschen zu schützen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'seo_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des SEO Boards (ERFORDERLICH).',
                ],
                'dry_run' => [
                    'type' => 'boolean',
                    'description' => 'Wenn true (Standard): zeigt nur was entfernt würde. Wenn false: löscht Keywords tatsächlich.',
                ],
                'exclude_competitor_brands' => [
                    'type' => 'boolean',
                    'description' => 'Competitor-Markennamen automatisch aus Brand-Competitors laden und filtern (Standard: true).',
                ],
                'exclude_jobs' => [
                    'type' => 'boolean',
                    'description' => 'Job/Karriere/Gehalt-Keywords filtern (Standard: true).',
                ],
                'exclude_persons' => [
                    'type' => 'boolean',
                    'description' => 'Personen-Namen (Dr., Prof.) filtern (Standard: true).',
                ],
                'exclude_locations' => [
                    'type' => 'boolean',
                    'description' => 'Lokale Suchen (Stadt + Dienstleister, "in der Nähe") filtern (Standard: true).',
                ],
                'exclude_brokers' => [
                    'type' => 'boolean',
                    'description' => 'Vermittlungs-Intent filtern: "finden", "suchen", "vermittlung", "buchen", "termin" (Standard: true). '
                        . 'Für Software-Unternehmen: User die Dienstleister suchen sind keine Zielgruppe.',
                ],
                'min_search_volume' => [
                    'type' => 'integer',
                    'description' => 'Mindest-Suchvolumen. Keywords darunter werden entfernt (Standard: 0 = kein Filter).',
                ],
                'custom_exclude' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Zusätzliche Ausschluss-Patterns (Teilwort-Match). Z.B. ["sql", "excel", "tomedo"].',
                ],
                'custom_include' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Schutz-Liste: Diese Keywords werden NIE entfernt, auch wenn eine Regel greift. Exakter Match (case-insensitive).',
                ],
            ],
            'required' => ['seo_board_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $seoBoardId = $arguments['seo_board_id'] ?? null;
            if (!$seoBoardId) {
                return ToolResult::error('VALIDATION_ERROR', 'seo_board_id ist erforderlich.');
            }

            $seoBoard = BrandsSeoBoard::find($seoBoardId);
            if (!$seoBoard) {
                return ToolResult::error('SEO_BOARD_NOT_FOUND', 'Das angegebene SEO Board wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $seoBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst Keywords dieses SEO Boards nicht bearbeiten (Policy).');
            }

            $curationService = app(SeoKeywordCurationService::class);

            $result = $curationService->curate($seoBoard, [
                'exclude_competitor_brands' => $arguments['exclude_competitor_brands'] ?? true,
                'exclude_jobs' => $arguments['exclude_jobs'] ?? true,
                'exclude_persons' => $arguments['exclude_persons'] ?? true,
                'exclude_locations' => $arguments['exclude_locations'] ?? true,
                'exclude_brokers' => $arguments['exclude_brokers'] ?? true,
                'min_search_volume' => $arguments['min_search_volume'] ?? 0,
                'custom_exclude' => $arguments['custom_exclude'] ?? [],
                'custom_include' => $arguments['custom_include'] ?? [],
                'dry_run' => $arguments['dry_run'] ?? true,
            ]);

            return ToolResult::success($result);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler bei der Keyword-Kuratierung: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'seo_keyword', 'curate', 'cleanup', 'filter'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['deletes'],
        ];
    }
}

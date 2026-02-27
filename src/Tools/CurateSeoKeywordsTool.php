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
        return 'POST /brands/seo_boards/{seo_board_id}/keywords/curate - Bereinigt Keywords eines SEO Boards. '
            . 'Zwei Stufen: 1) BLACKLIST (Competitor-Marken, Jobs, Personen, lokale Suchen, Vermittlungs-Intent, Navigational) '
            . '2) WHITELIST via relevance_topics — Keywords die keinem Thema zugeordnet werden können, fliegen raus. '
            . 'WICHTIG: Nutze relevance_topics mit den Kernthemen der Brand (z.B. ["arbeitsmedizin", "vorsorge", "software", "betriebsarzt"]). '
            . 'Keywords die keinem Topic matchen UND KD=0 haben, sind fast immer Arzt-/Klinik-/Personensuchen. '
            . 'Standardmäßig dry_run=true — zeigt nur was entfernt würde.';
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
                'relevance_topics' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'KERNFEATURE: Liste von Themen-Keywords für die Brand. Nur Keywords die mindestens einem Topic entsprechen (Substring-Match) werden behalten. '
                        . 'Beispiel für Arbeitsmedizin-Software: ["arbeitsmedizin", "betriebsarzt", "vorsorge", "dguv", "software", "praxis", "bgm", "bem", "arbeitsschutz", "untersuchung", "eignungs"]. '
                        . 'Wenn leer, wird kein Relevanz-Filter angewendet (nur Blacklist-Regeln).',
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
                    'description' => 'Vermittlungs-Intent filtern: "finden", "suchen", "vermittlung", "buchen" als ganze Wörter (Standard: true). '
                        . 'Word-Boundary-Match: "betriebsarzt finden" matcht, "gefahrstoffverzeichnis" nicht.',
                ],
                'exclude_navigational' => [
                    'type' => 'boolean',
                    'description' => 'Patienten-/Navigational-Suchen filtern: "arztpraxis", "klinik", "hautarzt", "zahnarzt", "online termin" etc. (Standard: true).',
                ],
                'min_search_volume' => [
                    'type' => 'integer',
                    'description' => 'Mindest-Suchvolumen. Keywords darunter werden entfernt (Standard: 0 = kein Filter).',
                ],
                'custom_exclude' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Zusätzliche Ausschluss-Patterns (Teilwort-Match). Z.B. ["sql", "excel", "icd"].',
                ],
                'custom_include' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Schutz-Liste: Diese Keywords werden NIE entfernt. Exakter Match (case-insensitive).',
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
                'exclude_navigational' => $arguments['exclude_navigational'] ?? true,
                'min_search_volume' => $arguments['min_search_volume'] ?? 0,
                'custom_exclude' => $arguments['custom_exclude'] ?? [],
                'custom_include' => $arguments['custom_include'] ?? [],
                'relevance_topics' => $arguments['relevance_topics'] ?? [],
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
            'tags' => ['brands', 'seo_keyword', 'curate', 'cleanup', 'filter', 'relevance'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['deletes'],
        ];
    }
}

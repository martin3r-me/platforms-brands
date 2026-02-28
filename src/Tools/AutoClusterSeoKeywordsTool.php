<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSeoBoard;
use Platform\Brands\Jobs\AutoClusterSeoKeywordsJob;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class AutoClusterSeoKeywordsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.seo_keywords.AUTO_CLUSTER';
    }

    public function getDescription(): string
    {
        return 'POST /brands/seo_boards/{seo_board_id}/keywords/auto_cluster - Clustert ungeclusterte Keywords automatisch per SERP-URL-Overlap (Google zeigt gleiche Seiten → gleiches Thema). Läuft als Background-Job (kann mehrere Minuten dauern). ACHTUNG: Kostenintensiv! Jedes Keyword = 1 SERP-Abruf (~10 Cents). 300 Keywords ≈ 30€. Budget-Limit des Boards beachten. REST-Parameter: seo_board_id (required, integer), min_overlap (optional, integer, default: 3, range: 1-10), max_keywords (optional, integer, default: 300, max: 500), check_status (optional, boolean) - wenn true, wird nur der aktuelle Job-Status zurückgegeben (kein neuer Job).';
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
                'min_overlap' => [
                    'type' => 'integer',
                    'description' => 'Mindestanzahl gleicher URLs in den Top-10 SERP-Ergebnissen, damit zwei Keywords als verwandt gelten (Standard: 3, Bereich: 1-10). Niedrigerer Wert = größere Cluster.',
                ],
                'max_keywords' => [
                    'type' => 'integer',
                    'description' => 'Maximale Anzahl Keywords für Clustering (Standard: 300, max: 500). Sortiert nach Search Volume (wichtigste zuerst).',
                ],
                'check_status' => [
                    'type' => 'boolean',
                    'description' => 'Wenn true: Gibt nur den aktuellen Clustering-Status zurück, ohne einen neuen Job zu starten. Nutze dies zum Pollen des Fortschritts.',
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
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Keywords für dieses SEO Board clustern (Policy).');
            }

            // Status-Check Mode
            if (!empty($arguments['check_status'])) {
                return $this->checkStatus($seoBoard);
            }

            // Prevent duplicate jobs
            if (in_array($seoBoard->clustering_status, ['pending', 'processing'])) {
                return ToolResult::success([
                    'status' => 'already_running',
                    'clustering_status' => $seoBoard->clustering_status,
                    'clustering_started_at' => $seoBoard->clustering_started_at?->toIso8601String(),
                    'message' => 'Ein Clustering-Job läuft bereits. Nutze check_status=true um den Fortschritt zu prüfen.',
                ]);
            }

            $minOverlap = max(1, min(10, $arguments['min_overlap'] ?? 3));
            $maxKeywords = max(1, min(500, $arguments['max_keywords'] ?? 300));

            // Count unclustered keywords for estimate
            $unclusteredCount = $seoBoard->keywords()
                ->whereNull('keyword_cluster_id')
                ->count();

            if ($unclusteredCount === 0) {
                return ToolResult::success([
                    'status' => 'no_keywords',
                    'message' => 'Keine ungeclusterten Keywords vorhanden.',
                ]);
            }

            $keywordsToProcess = min($unclusteredCount, $maxKeywords);
            $estimatedCostCents = (int) ceil($keywordsToProcess * 10);

            // Set status to pending and dispatch job
            $seoBoard->update([
                'clustering_status' => 'pending',
                'clustering_result' => null,
                'clustering_started_at' => null,
                'clustering_completed_at' => null,
            ]);

            AutoClusterSeoKeywordsJob::dispatch(
                $seoBoard->id,
                $context->user->id,
                $minOverlap,
                $maxKeywords,
            );

            return ToolResult::success([
                'status' => 'dispatched',
                'seo_board_id' => $seoBoard->id,
                'seo_board_name' => $seoBoard->name,
                'keywords_count' => $keywordsToProcess,
                'estimated_cost_cents' => $estimatedCostCents,
                'min_overlap' => $minOverlap,
                'max_keywords' => $maxKeywords,
                'message' => "Clustering-Job gestartet für {$keywordsToProcess} Keywords. Geschätzte Kosten: {$estimatedCostCents} Cents (~" . number_format($estimatedCostCents / 100, 2) . " EUR). Nutze check_status=true um den Fortschritt zu prüfen.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Auto-Clustering: ' . $e->getMessage());
        }
    }

    private function checkStatus(BrandsSeoBoard $seoBoard): ToolResult
    {
        $data = [
            'seo_board_id' => $seoBoard->id,
            'seo_board_name' => $seoBoard->name,
            'clustering_status' => $seoBoard->clustering_status,
            'clustering_started_at' => $seoBoard->clustering_started_at?->toIso8601String(),
            'clustering_completed_at' => $seoBoard->clustering_completed_at?->toIso8601String(),
        ];

        if ($seoBoard->clustering_status === 'completed' && $seoBoard->clustering_result) {
            $result = $seoBoard->clustering_result;
            $data['clusters_created'] = $result['clusters_created'] ?? 0;
            $data['keywords_clustered'] = $result['keywords_clustered'] ?? 0;
            $data['keywords_fetched'] = $result['keywords_fetched'] ?? 0;
            $data['singletons_remaining'] = $result['singletons_remaining'] ?? 0;
            $data['cost_cents'] = $result['cost_cents'] ?? 0;
            $data['message'] = "{$data['clusters_created']} Cluster erstellt, {$data['keywords_clustered']} Keywords zugeordnet. Kosten: {$data['cost_cents']} Cents.";
        } elseif ($seoBoard->clustering_status === 'failed' && $seoBoard->clustering_result) {
            $data['error'] = $seoBoard->clustering_result['error'] ?? 'Unbekannter Fehler';
            $data['message'] = 'Clustering fehlgeschlagen: ' . $data['error'];
        } elseif ($seoBoard->clustering_status === 'processing') {
            $data['message'] = 'Clustering läuft noch. Bitte später erneut prüfen.';
        } elseif ($seoBoard->clustering_status === 'pending') {
            $data['message'] = 'Clustering-Job wartet auf Ausführung (Queue).';
        } else {
            $data['message'] = 'Kein Clustering-Job aktiv.';
        }

        return ToolResult::success($data);
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'seo_keyword', 'cluster', 'auto', 'serp', 'overlap', 'dataforseo'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['external_api', 'costs', 'creates', 'updates'],
        ];
    }
}

<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSocialBoard;
use Platform\Brands\Models\BrandsSocialCard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;
use Carbon\Carbon;

/**
 * Tool zum Abrufen der Redaktionsplan-Ansicht (Editorial Plan) eines Social Boards.
 *
 * ## Übersicht
 *
 * Gibt Social Cards mit publish_at, Status und Contract-Übersicht zurück.
 * Das Planungsobjekt ist die Social Card (nicht der einzelne Contract).
 * Der User sieht auf einen Blick was wann wo rausgeht.
 *
 * ## Response-Struktur
 *
 * ```json
 * {
 *   "social_board_id": 1,
 *   "social_board_name": "Q1 Content Plan",
 *   "period": { "start": "2025-03-01", "end": "2025-03-31" },
 *   "cards": [
 *     {
 *       "id": 42,
 *       "title": "Produkt-Launch Post",
 *       "status": "scheduled",
 *       "publish_at": "2025-03-15T10:00:00+00:00",
 *       "published_at": null,
 *       "slot_name": "Woche 11",
 *       "contracts": [
 *         {
 *           "id": 1,
 *           "platform": "instagram",
 *           "format": "Feed Post",
 *           "status": "ready"
 *         }
 *       ],
 *       "platforms": ["instagram", "facebook"]
 *     }
 *   ],
 *   "unscheduled_cards": [ ... ],
 *   "stats": {
 *     "total": 15,
 *     "draft": 3,
 *     "scheduled": 8,
 *     "published": 2,
 *     "failed": 1,
 *     "publishing": 1
 *   }
 * }
 * ```
 *
 * ## Filter
 *
 * - `status` — Filtere nach Card-Status (draft, scheduled, publishing, published, failed)
 * - `platform_id` — Filtere nach Plattform (nur Cards mit Contracts für diese Plattform)
 * - `from` / `to` — Zeitraum-Filter (ISO8601-Daten). Default: aktueller Monat
 *
 * ## Zusammenhang
 *
 * Nutze dieses Tool für die Redaktionsplan-Ansicht im Social Board.
 * Für die Board-Ansicht (Kanban mit Slots) nutze stattdessen `brands.social_board.GET`.
 */
class GetEditorialPlanTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.social_boards.editorial_plan.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/social_boards/{id}/editorial_plan - Redaktionsplan-Ansicht eines Social Boards. '
            . 'Gibt Social Cards mit publish_at, Status und Contract-Übersicht zurück (Planungsobjekt = Social Card). '
            . 'Der User sieht auf einen Blick was wann wo rausgeht — ohne in jeden Contract einzeln reinschauen zu müssen. '
            . 'REST-Parameter: id (required, integer) - Social Board-ID. '
            . 'from (optional, string) - Startdatum ISO8601 (Default: Monatsanfang). '
            . 'to (optional, string) - Enddatum ISO8601 (Default: Monatsende). '
            . 'status (optional, string) - Status-Filter: draft|scheduled|publishing|published|failed. '
            . 'platform_id (optional, integer) - Plattform-Filter (nur Cards mit Contracts für diese Plattform).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID des Social Boards. Nutze "brands.social_boards.GET" um Social Boards zu finden.'
                ],
                'from' => [
                    'type' => 'string',
                    'description' => 'Optional: Startdatum für den Zeitraum im ISO8601-Format (z.B. "2025-03-01"). Default: Erster Tag des aktuellen Monats.'
                ],
                'to' => [
                    'type' => 'string',
                    'description' => 'Optional: Enddatum für den Zeitraum im ISO8601-Format (z.B. "2025-03-31"). Default: Letzter Tag des aktuellen Monats.'
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['draft', 'scheduled', 'publishing', 'published', 'failed'],
                    'description' => 'Optional: Filtere nach Status der Social Cards.'
                ],
                'platform_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Filtere nach Plattform-ID (nur Cards mit Contracts für diese Plattform). Nutze "brands.social_platforms.GET" um Plattform-IDs zu finden.'
                ],
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
                return ToolResult::error('VALIDATION_ERROR', 'Social Board-ID ist erforderlich. Nutze "brands.social_boards.GET" um Social Boards zu finden.');
            }

            // SocialBoard laden
            $socialBoard = BrandsSocialBoard::with('brand')->find($arguments['id']);
            if (!$socialBoard) {
                return ToolResult::error('SOCIAL_BOARD_NOT_FOUND', 'Das angegebene Social Board wurde nicht gefunden. Nutze "brands.social_boards.GET" um alle verfügbaren Social Boards zu sehen.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('view', $socialBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Social Board (Policy).');
            }

            // Zeitraum bestimmen
            $from = isset($arguments['from'])
                ? Carbon::parse($arguments['from'])->startOfDay()
                : Carbon::now()->startOfMonth();
            $to = isset($arguments['to'])
                ? Carbon::parse($arguments['to'])->endOfDay()
                : Carbon::now()->endOfMonth();

            // Cards-Query aufbauen
            $query = BrandsSocialCard::query()
                ->where('social_board_id', $socialBoard->id)
                ->with(['contracts.platformFormat.platform', 'slot']);

            // Status-Filter
            if (!empty($arguments['status'])) {
                $query->where('status', $arguments['status']);
            }

            // Plattform-Filter
            if (!empty($arguments['platform_id'])) {
                $platformId = (int) $arguments['platform_id'];
                $query->whereHas('contracts.platformFormat.platform', function ($q) use ($platformId) {
                    $q->where('brands_social_platforms.id', $platformId);
                });
            }

            // Scheduled Cards im Zeitraum
            $scheduledCards = (clone $query)
                ->whereNotNull('publish_at')
                ->whereBetween('publish_at', [$from, $to])
                ->orderBy('publish_at')
                ->get();

            // Unscheduled Cards (ohne publish_at)
            $unscheduledCards = (clone $query)
                ->whereNull('publish_at')
                ->orderBy('created_at', 'desc')
                ->get();

            // Cards formatieren
            $formatCard = function (BrandsSocialCard $card) {
                $contracts = $card->contracts->map(function ($contract) {
                    return [
                        'id' => $contract->id,
                        'platform' => $contract->platformFormat->platform->key ?? null,
                        'platform_name' => $contract->platformFormat->platform->name ?? null,
                        'format' => $contract->platformFormat->name ?? null,
                        'format_key' => $contract->platformFormat->key ?? null,
                        'status' => $contract->status,
                        'published_at' => $contract->published_at?->toIso8601String(),
                        'error_message' => $contract->error_message,
                    ];
                })->values()->toArray();

                $platforms = $card->contracts
                    ->map(fn ($c) => $c->platformFormat->platform->key ?? null)
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();

                return [
                    'id' => $card->id,
                    'uuid' => $card->uuid,
                    'title' => $card->title,
                    'status' => $card->status ?? 'draft',
                    'publish_at' => $card->publish_at?->toIso8601String(),
                    'published_at' => $card->published_at?->toIso8601String(),
                    'slot_name' => $card->slot->name ?? null,
                    'contracts' => $contracts,
                    'contracts_count' => count($contracts),
                    'platforms' => $platforms,
                ];
            };

            $scheduledList = $scheduledCards->map($formatCard)->values()->toArray();
            $unscheduledList = $unscheduledCards->map($formatCard)->values()->toArray();

            // Stats berechnen (über alle Cards im Board, nicht nur im Zeitraum)
            $allCards = BrandsSocialCard::where('social_board_id', $socialBoard->id)->get();
            $stats = [
                'total' => $allCards->count(),
                'draft' => $allCards->where('status', 'draft')->count() + $allCards->whereNull('status')->count(),
                'scheduled' => $allCards->where('status', 'scheduled')->count(),
                'publishing' => $allCards->where('status', 'publishing')->count(),
                'published' => $allCards->where('status', 'published')->count(),
                'failed' => $allCards->where('status', 'failed')->count(),
            ];

            return ToolResult::success([
                'social_board_id' => $socialBoard->id,
                'social_board_name' => $socialBoard->name,
                'brand_id' => $socialBoard->brand_id,
                'brand_name' => $socialBoard->brand->name,
                'period' => [
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                ],
                'cards' => $scheduledList,
                'cards_count' => count($scheduledList),
                'unscheduled_cards' => $unscheduledList,
                'unscheduled_count' => count($unscheduledList),
                'stats' => $stats,
                'message' => count($scheduledList) . ' geplante Card(s) im Zeitraum '
                    . $from->format('d.m.Y') . ' – ' . $to->format('d.m.Y')
                    . ', ' . count($unscheduledList) . ' ungeplante Card(s).',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Redaktionsplans: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'social_board', 'editorial_plan', 'redaktionsplan', 'calendar'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}

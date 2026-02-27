<?php

namespace Platform\Brands\Services;

use Platform\Brands\Models\BrandsSeoBoard;
use Platform\Brands\Models\BrandsSeoKeyword;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SeoKeywordCurationService
{
    /**
     * Job/Karriere-Patterns — Keywords die nach Stellenangeboten suchen.
     */
    protected array $jobPatterns = [
        'stellenangebot', 'stellenanzeige', 'jobs', 'job ', 'karriere',
        'gehalt', 'ausbildung', 'studium', 'beruf werden', 'weiterbildung zum',
        'umschulung', 'quereinsteiger', 'bewerbung', 'vacancy', 'hiring',
        'fernstudium', 'berufsbegleitend',
    ];

    /**
     * Personen-Patterns — Ärzte-/Personennamen.
     */
    protected array $personPatterns = [
        'dr. ', 'dr.med', 'prof. ', 'prof.dr',
    ];

    /**
     * Lokale Suche — User sucht Dienstleister vor Ort, nicht Software.
     */
    protected array $localPatterns = [
        'in der nähe', 'in meiner nähe', 'in der naehe',
    ];

    /**
     * Deutsche Großstädte für Location-Filter.
     */
    protected array $cities = [
        'berlin', 'hamburg', 'münchen', 'köln', 'frankfurt', 'stuttgart',
        'düsseldorf', 'dortmund', 'essen', 'leipzig', 'bremen', 'dresden',
        'hannover', 'nürnberg', 'duisburg', 'bochum', 'wuppertal', 'bielefeld',
        'bonn', 'münster', 'karlsruhe', 'mannheim', 'augsburg', 'wiesbaden',
        'aachen', 'braunschweig', 'kiel', 'chemnitz', 'halle', 'magdeburg',
        'freiburg', 'lübeck', 'oberhausen', 'erfurt', 'rostock', 'mainz',
        'kassel', 'saarbrücken', 'potsdam', 'oldenburg', 'regensburg',
        'heidelberg', 'darmstadt', 'würzburg', 'wolfsburg', 'ulm',
    ];

    /**
     * Vermittlungs-Patterns — User sucht Dienstleister, nicht Software.
     */
    protected array $brokerPatterns = [
        'finden', 'suchen', 'vermittlung', 'verzeichnis', 'empfehlung',
        'buchen', 'termin ', 'terminvereinbarung', 'praxis in',
    ];

    /**
     * Kuratiert Keywords eines SEO Boards.
     *
     * @param BrandsSeoBoard $board
     * @param array{
     *   exclude_competitor_brands?: bool,
     *   exclude_jobs?: bool,
     *   exclude_persons?: bool,
     *   exclude_locations?: bool,
     *   exclude_brokers?: bool,
     *   min_search_volume?: int,
     *   custom_exclude?: string[],
     *   custom_include?: string[],
     *   dry_run?: bool,
     * } $options
     * @return array
     */
    public function curate(BrandsSeoBoard $board, array $options = []): array
    {
        $excludeCompetitorBrands = $options['exclude_competitor_brands'] ?? true;
        $excludeJobs = $options['exclude_jobs'] ?? true;
        $excludePersons = $options['exclude_persons'] ?? true;
        $excludeLocations = $options['exclude_locations'] ?? true;
        $excludeBrokers = $options['exclude_brokers'] ?? true;
        $minSearchVolume = $options['min_search_volume'] ?? 0;
        $customExclude = $options['custom_exclude'] ?? [];
        $customInclude = $options['custom_include'] ?? [];
        $dryRun = $options['dry_run'] ?? true;

        // Lade alle Keywords
        $keywords = $board->keywords()->get();

        if ($keywords->isEmpty()) {
            return [
                'total' => 0,
                'keep' => 0,
                'remove' => 0,
                'removed_keywords' => [],
                'kept_keywords' => [],
                'dry_run' => $dryRun,
                'message' => 'Keine Keywords auf dem Board.',
            ];
        }

        // Competitor-Markennamen laden
        $competitorNames = $excludeCompetitorBrands
            ? $this->loadCompetitorNames($board)
            : collect();

        // Custom-Include als Schutz-Set (case-insensitive)
        $includeSet = collect($customInclude)->map(fn ($k) => mb_strtolower(trim($k)));

        $remove = collect();
        $keep = collect();

        foreach ($keywords as $keyword) {
            $kw = mb_strtolower(trim($keyword->keyword));

            // Protected by custom_include — immer behalten
            if ($includeSet->contains($kw)) {
                $keep->push($this->keywordSummary($keyword, 'protected'));
                continue;
            }

            // Regel-Checks
            $reason = $this->checkRules(
                $kw,
                $keyword,
                $competitorNames,
                $excludeJobs,
                $excludePersons,
                $excludeLocations,
                $excludeBrokers,
                $minSearchVolume,
                $customExclude,
            );

            if ($reason) {
                $remove->push($this->keywordSummary($keyword, $reason));
            } else {
                $keep->push($this->keywordSummary($keyword));
            }
        }

        // Tatsächlich löschen wenn kein dry_run
        if (!$dryRun && $remove->isNotEmpty()) {
            $removeIds = $remove->pluck('id')->toArray();
            BrandsSeoKeyword::whereIn('id', $removeIds)->delete();
        }

        return [
            'total' => $keywords->count(),
            'keep' => $keep->count(),
            'remove' => $remove->count(),
            'dry_run' => $dryRun,
            'removed_keywords' => $remove->sortBy('keyword')->values()->toArray(),
            'kept_keywords' => $keep->sortByDesc('search_volume')->values()->toArray(),
            'rules_applied' => $this->rulesAppliedSummary($remove),
            'message' => $dryRun
                ? "Dry-Run: {$remove->count()} von {$keywords->count()} Keywords würden entfernt. Erneut mit dry_run=false aufrufen um zu löschen."
                : "{$remove->count()} Keywords gelöscht, {$keep->count()} behalten.",
        ];
    }

    /**
     * Prüft alle Regeln und gibt den Grund zurück, oder null wenn behalten.
     */
    protected function checkRules(
        string $kw,
        BrandsSeoKeyword $keyword,
        Collection $competitorNames,
        bool $excludeJobs,
        bool $excludePersons,
        bool $excludeLocations,
        bool $excludeBrokers,
        int $minSearchVolume,
        array $customExclude,
    ): ?string {
        // 1. Min SV
        if ($minSearchVolume > 0 && ($keyword->search_volume ?? 0) < $minSearchVolume) {
            return 'low_search_volume';
        }

        // 2. Competitor-Markennamen
        foreach ($competitorNames as $name) {
            if (Str::contains($kw, $name)) {
                return "competitor_brand:{$name}";
            }
        }

        // 3. Job/Karriere
        if ($excludeJobs) {
            foreach ($this->jobPatterns as $pattern) {
                if (Str::contains($kw, $pattern)) {
                    return "job_keyword:{$pattern}";
                }
            }
        }

        // 4. Personen-Namen
        if ($excludePersons) {
            foreach ($this->personPatterns as $pattern) {
                if (Str::contains($kw, $pattern)) {
                    return "person_name:{$pattern}";
                }
            }
        }

        // 5. Lokale Suche (Stadt am Ende oder "in der nähe")
        if ($excludeLocations) {
            foreach ($this->localPatterns as $pattern) {
                if (Str::contains($kw, $pattern)) {
                    return "local_search:{$pattern}";
                }
            }
            foreach ($this->cities as $city) {
                // "betriebsarzt berlin" oder "berlin betriebsarzt"
                if (Str::endsWith($kw, " {$city}") || Str::startsWith($kw, "{$city} ")) {
                    return "local_search:{$city}";
                }
            }
        }

        // 6. Vermittlung/Suche (User sucht Dienstleister, nicht Software)
        if ($excludeBrokers) {
            foreach ($this->brokerPatterns as $pattern) {
                if (Str::contains($kw, $pattern)) {
                    return "broker_intent:{$pattern}";
                }
            }
        }

        // 7. Custom Exclude Patterns
        foreach ($customExclude as $pattern) {
            $p = mb_strtolower(trim($pattern));
            if ($p && Str::contains($kw, $p)) {
                return "custom_exclude:{$p}";
            }
        }

        return null;
    }

    /**
     * Lädt Competitor-Markennamen aus der Brand.
     */
    protected function loadCompetitorNames(BrandsSeoBoard $board): Collection
    {
        $brand = $board->brand;
        if (!$brand) {
            return collect();
        }

        $competitors = $brand->competitorBoards()
            ->with('competitors')
            ->get()
            ->flatMap(fn ($b) => $b->competitors)
            ->where('is_own_brand', false);

        $names = collect();

        foreach ($competitors as $competitor) {
            // Markenname → lowercase, splitten bei Leerzeichen für Teilwort-Match
            $name = mb_strtolower(trim($competitor->name));
            if ($name && mb_strlen($name) >= 3) {
                $names->push($name);
            }

            // Domain extrahieren falls vorhanden
            if ($competitor->website_url) {
                $host = parse_url($competitor->website_url, PHP_URL_HOST);
                if ($host) {
                    $domain = preg_replace('/^www\./', '', $host);
                    $domainBase = explode('.', $domain)[0] ?? null;
                    if ($domainBase && mb_strlen($domainBase) >= 3) {
                        $names->push(mb_strtolower($domainBase));
                    }
                }
            }
        }

        return $names->unique()->values();
    }

    protected function keywordSummary(BrandsSeoKeyword $keyword, ?string $reason = null): array
    {
        $data = [
            'id' => $keyword->id,
            'keyword' => $keyword->keyword,
            'search_volume' => $keyword->search_volume,
            'keyword_difficulty' => $keyword->keyword_difficulty,
        ];

        if ($reason) {
            $data['reason'] = $reason;
        }

        return $data;
    }

    /**
     * Fasst zusammen, welche Regeln wie oft gegriffen haben.
     */
    protected function rulesAppliedSummary(Collection $removed): array
    {
        return $removed
            ->groupBy(fn ($item) => explode(':', $item['reason'] ?? 'unknown')[0])
            ->map(fn ($group, $rule) => $group->count())
            ->sortDesc()
            ->toArray();
    }
}

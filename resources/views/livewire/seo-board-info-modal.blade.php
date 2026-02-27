<x-ui-modal size="xl" model="modalShow" header="SEO Board &mdash; Info & Konzept">
    <div class="space-y-8 text-sm text-[var(--ui-secondary)] leading-relaxed">

        {{-- Intro --}}
        <div>
            <p class="text-[var(--ui-muted)]">
                Das SEO Board ist das zentrale Cockpit f&uuml;r die Keyword-Strategie einer Marke. Es kombiniert Keyword-Research,
                Wettbewerbs-Analyse und Content-Planung in einem datengetriebenen Workflow.
            </p>
        </div>

        {{-- Pipeline --}}
        <div>
            <h3 class="text-xs font-semibold uppercase tracking-wide text-lime-700 mb-3 flex items-center gap-2">
                @svg('heroicon-o-arrow-trending-up', 'w-4 h-4')
                Die SEO-Pipeline
            </h3>
            <div class="bg-lime-50/50 border border-lime-200/60 rounded-lg p-4">
                <div class="flex flex-col gap-3">
                    <div class="flex items-start gap-3">
                        <span class="flex-shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-full bg-lime-600 text-white text-[10px] font-bold">1</span>
                        <div>
                            <span class="font-semibold">Keywords sammeln</span>
                            <span class="text-[var(--ui-muted)]">&mdash; manuell, per Seed-Keywords oder automatisch von Wettbewerber-Domains</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="flex-shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-full bg-lime-600 text-white text-[10px] font-bold">2</span>
                        <div>
                            <span class="font-semibold">Metriken abrufen</span>
                            <span class="text-[var(--ui-muted)]">&mdash; Search Volume, Keyword Difficulty, CPC &uuml;ber DataForSEO</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="flex-shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-full bg-lime-600 text-white text-[10px] font-bold">3</span>
                        <div>
                            <span class="font-semibold">Auto-Clustering</span>
                            <span class="text-[var(--ui-muted)]">&mdash; SERP-Overlap gruppiert Keywords zu thematischen Clustern</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="flex-shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-full bg-lime-600 text-white text-[10px] font-bold">4</span>
                        <div>
                            <span class="font-semibold">Priorisieren</span>
                            <span class="text-[var(--ui-muted)]">&mdash; Opportunity Score zeigt, welche Cluster das beste Verh&auml;ltnis aus Potenzial und Schwierigkeit haben</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="flex-shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-full bg-lime-600 text-white text-[10px] font-bold">5</span>
                        <div>
                            <span class="font-semibold">Content erstellen & tracken</span>
                            <span class="text-[var(--ui-muted)]">&mdash; Status pro Keyword verfolgen, Rankings monitoren</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Auto-Discovery --}}
        <div>
            <h3 class="text-xs font-semibold uppercase tracking-wide text-lime-700 mb-3 flex items-center gap-2">
                @svg('heroicon-o-magnifying-glass-circle', 'w-4 h-4')
                Auto-Discovery von Wettbewerber-Keywords
            </h3>
            <div class="space-y-2">
                <p>
                    Das Tool <code class="px-1.5 py-0.5 rounded bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 text-[11px] font-mono">DISCOVER_FROM_COMPETITORS</code>
                    zieht automatisch Keywords von allen Wettbewerber-Domains der Marke.
                </p>
                <div class="bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg p-3 space-y-1.5 text-xs">
                    <div class="flex items-start gap-2">
                        @svg('heroicon-o-arrow-right', 'w-3.5 h-3.5 text-lime-600 flex-shrink-0 mt-0.5')
                        <span>Liest alle Wettbewerber aus den <strong>Competitor Boards</strong> der Marke</span>
                    </div>
                    <div class="flex items-start gap-2">
                        @svg('heroicon-o-arrow-right', 'w-3.5 h-3.5 text-lime-600 flex-shrink-0 mt-0.5')
                        <span>Filtert: nur externe Wettbewerber (nicht <em>is_own_brand</em>) mit Website-URL</span>
                    </div>
                    <div class="flex items-start gap-2">
                        @svg('heroicon-o-arrow-right', 'w-3.5 h-3.5 text-lime-600 flex-shrink-0 mt-0.5')
                        <span>Pro Domain werden die Top-rankenden Keywords abgerufen (DataForSEO Labs API)</span>
                    </div>
                    <div class="flex items-start gap-2">
                        @svg('heroicon-o-arrow-right', 'w-3.5 h-3.5 text-lime-600 flex-shrink-0 mt-0.5')
                        <span>Deduplizierung: cross-domain + gegen bereits vorhandene Board-Keywords</span>
                    </div>
                    <div class="flex items-start gap-2">
                        @svg('heroicon-o-arrow-right', 'w-3.5 h-3.5 text-lime-600 flex-shrink-0 mt-0.5')
                        <span>Ergebnis: 200&ndash;500 unique Keywords mit Search Volume, KD und CPC</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Auto-Clustering --}}
        <div>
            <h3 class="text-xs font-semibold uppercase tracking-wide text-lime-700 mb-3 flex items-center gap-2">
                @svg('heroicon-o-square-3-stack-3d', 'w-4 h-4')
                SERP-Overlap Clustering &mdash; So funktioniert&rsquo;s
            </h3>
            <div class="space-y-3">
                <p>
                    Das <code class="px-1.5 py-0.5 rounded bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 text-[11px] font-mono">AUTO_CLUSTER</code>-Tool
                    gruppiert Keywords nach dem Prinzip: <strong>&bdquo;Gleiche Google-Ergebnisse = gleiches Thema&ldquo;</strong>.
                </p>

                <div class="bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg p-4 space-y-4">
                    {{-- Schritt 1 --}}
                    <div>
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded bg-blue-100 text-blue-700 text-[10px] font-bold">1</span>
                            <span class="font-semibold text-xs">SERP-Daten abrufen</span>
                        </div>
                        <p class="text-xs text-[var(--ui-muted)] ml-7">
                            F&uuml;r jedes ungeclusterte Keyword werden die Top-10 Google-Ergebnisse abgerufen.
                            Die URLs werden normalisiert (Host ohne www + Pfad, ohne Query-Parameter).
                        </p>
                    </div>

                    {{-- Schritt 2 --}}
                    <div>
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded bg-purple-100 text-purple-700 text-[10px] font-bold">2</span>
                            <span class="font-semibold text-xs">Similarity-Matrix berechnen</span>
                        </div>
                        <p class="text-xs text-[var(--ui-muted)] ml-7">
                            F&uuml;r jedes Keyword-Paar wird gez&auml;hlt, wie viele der Top-10 URLs identisch sind.
                            Wenn der Overlap &ge; <strong>min_overlap</strong> (Standard: 3) ist, gelten die Keywords als verwandt.
                        </p>
                    </div>

                    {{-- Schritt 3 --}}
                    <div>
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded bg-green-100 text-green-700 text-[10px] font-bold">3</span>
                            <span class="font-semibold text-xs">Graph-Clustering (BFS)</span>
                        </div>
                        <p class="text-xs text-[var(--ui-muted)] ml-7">
                            Die verwandten Keywords bilden einen Graphen. Per Breitensuche (BFS) werden zusammenh&auml;ngende
                            Gruppen gefunden &mdash; jede Gruppe wird ein Cluster. Einzelg&auml;nger (Singletons) bleiben ungeclustert.
                        </p>
                    </div>

                    {{-- Schritt 4 --}}
                    <div>
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded bg-amber-100 text-amber-700 text-[10px] font-bold">4</span>
                            <span class="font-semibold text-xs">Cluster benennen & f&auml;rben</span>
                        </div>
                        <p class="text-xs text-[var(--ui-muted)] ml-7">
                            Jeder Cluster wird nach dem Keyword mit dem h&ouml;chsten Search Volume benannt.
                            Die gr&ouml;&szlig;ten Cluster bekommen die ersten Farben aus der Palette (blue, purple, green, amber, ...).
                        </p>
                    </div>
                </div>

                {{-- Visualisierung --}}
                <div class="bg-white border border-[var(--ui-border)]/40 rounded-lg p-4">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-2">Beispiel</p>
                    <div class="font-mono text-[11px] text-[var(--ui-muted)] space-y-1">
                        <div>&ldquo;laufschuhe test&rdquo; &nbsp;&rarr;&nbsp; Top-10: <span class="text-blue-600">A</span>, <span class="text-purple-600">B</span>, <span class="text-green-600">C</span>, D, E, F, G, H, I, J</div>
                        <div>&ldquo;beste laufschuhe&rdquo; &rarr;&nbsp; Top-10: <span class="text-blue-600">A</span>, <span class="text-purple-600">B</span>, <span class="text-green-600">C</span>, K, L, M, N, O, P, Q</div>
                        <div class="pt-1 text-emerald-700 font-semibold">&rarr; Overlap: 3 URLs (A, B, C) &ge; min_overlap &rarr; gleicher Cluster!</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Analyse-View --}}
        <div>
            <h3 class="text-xs font-semibold uppercase tracking-wide text-lime-700 mb-3 flex items-center gap-2">
                @svg('heroicon-o-table-cells', 'w-4 h-4')
                Cluster-Analyse &mdash; Die Metriken
            </h3>
            <div class="overflow-hidden rounded-lg border border-[var(--ui-border)]/40">
                <table class="w-full text-xs">
                    <tbody class="divide-y divide-[var(--ui-border)]/30">
                        <tr class="bg-[var(--ui-muted-5)]">
                            <td class="px-3 py-2 font-semibold w-32">Opportunity Score</td>
                            <td class="px-3 py-2 text-[var(--ui-muted)]">
                                Normalisierter Score (0&ndash;100). Formel: <code class="px-1 py-0.5 rounded bg-white border border-[var(--ui-border)]/40 text-[10px]">&Sigma;SV / (gew.KD + 1)</code> &mdash;
                                hoher Suchvolumen bei niedriger Schwierigkeit = hoher Score. Der beste Cluster bekommt Score 100.
                            </td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">&Sigma; SV</td>
                            <td class="px-3 py-2 text-[var(--ui-muted)]">Summe des monatlichen Suchvolumens aller Keywords im Cluster.</td>
                        </tr>
                        <tr class="bg-[var(--ui-muted-5)]">
                            <td class="px-3 py-2 font-semibold">&empty; KD</td>
                            <td class="px-3 py-2 text-[var(--ui-muted)]">
                                SV-gewichteter Keyword-Difficulty-Durchschnitt. Keywords mit h&ouml;herem SV beeinflussen den Wert st&auml;rker,
                                weil sie bei der Content-Strategie wichtiger sind.
                            </td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">&empty; CPC</td>
                            <td class="px-3 py-2 text-[var(--ui-muted)]">Durchschnittlicher Cost-per-Click in Euro. Zeigt den kommerziellen Wert der Keywords.</td>
                        </tr>
                        <tr class="bg-[var(--ui-muted-5)]">
                            <td class="px-3 py-2 font-semibold">Wert &euro;</td>
                            <td class="px-3 py-2 text-[var(--ui-muted)]">
                                Traffic-Wert = <code class="px-1 py-0.5 rounded bg-white border border-[var(--ui-border)]/40 text-[10px]">&Sigma;SV &times; &empty;CPC</code> &mdash;
                                was man f&uuml;r diesen Traffic monatlich bei Google Ads zahlen m&uuml;sste.
                            </td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">Coverage</td>
                            <td class="px-3 py-2 text-[var(--ui-muted)]">Prozent der Keywords mit Content-Status &ne; &bdquo;Offen&ldquo;. Zeigt den Fortschritt der Content-Erstellung.</td>
                        </tr>
                        <tr class="bg-[var(--ui-muted-5)]">
                            <td class="px-3 py-2 font-semibold">Rankings</td>
                            <td class="px-3 py-2 text-[var(--ui-muted)]">Anzahl Keywords mit bekannter Position in den SERPs (Verhltnis zu Gesamtzahl).</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">&empty; Pos</td>
                            <td class="px-3 py-2 text-[var(--ui-muted)]">Durchschnittliche Google-Position. Farbcodiert: gr&uuml;n (Top 3), lime (Top 10), gelb (Top 20), orange (Top 50), rot (&gt; 50).</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Kosten --}}
        <div>
            <h3 class="text-xs font-semibold uppercase tracking-wide text-lime-700 mb-3 flex items-center gap-2">
                @svg('heroicon-o-banknotes', 'w-4 h-4')
                API-Kosten
            </h3>
            <div class="bg-amber-50/50 border border-amber-200/60 rounded-lg p-4 space-y-2">
                <div class="overflow-hidden rounded border border-amber-200/60">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-amber-100/50">
                                <th class="px-3 py-1.5 text-left text-[10px] font-semibold uppercase tracking-wide text-amber-800">Aktion</th>
                                <th class="px-3 py-1.5 text-right text-[10px] font-semibold uppercase tracking-wide text-amber-800">Kosten ca.</th>
                                <th class="px-3 py-1.5 text-left text-[10px] font-semibold uppercase tracking-wide text-amber-800">Hinweis</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-amber-200/40">
                            <tr>
                                <td class="px-3 py-1.5 font-medium">Metriken abrufen</td>
                                <td class="px-3 py-1.5 text-right tabular-nums">~5 Ct/Keyword</td>
                                <td class="px-3 py-1.5 text-[var(--ui-muted)]">Search Volume, CPC</td>
                            </tr>
                            <tr class="bg-amber-50/40">
                                <td class="px-3 py-1.5 font-medium">SERP-Rankings</td>
                                <td class="px-3 py-1.5 text-right tabular-nums">~10 Ct/Keyword</td>
                                <td class="px-3 py-1.5 text-[var(--ui-muted)]">Positions-Tracking</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-1.5 font-medium">Domain-Discovery</td>
                                <td class="px-3 py-1.5 text-right tabular-nums">~10 Ct/Request</td>
                                <td class="px-3 py-1.5 text-[var(--ui-muted)]">Pro Wettbewerber-Domain</td>
                            </tr>
                            <tr class="bg-amber-50/40">
                                <td class="px-3 py-1.5 font-medium text-amber-800">Auto-Clustering</td>
                                <td class="px-3 py-1.5 text-right tabular-nums font-semibold text-amber-800">~10 Ct/Keyword</td>
                                <td class="px-3 py-1.5 text-amber-700">300 KW &asymp; 30 &euro;</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-[11px] text-amber-700">
                    @svg('heroicon-o-shield-check', 'w-3.5 h-3.5 inline-block mr-0.5')
                    Das Budget-Limit sch&uuml;tzt vor unerwarteten Kosten. Wird vor jedem API-Call gepr&uuml;ft.
                </p>
            </div>
        </div>

        {{-- Verf&uuml;gbare Tools --}}
        <div>
            <h3 class="text-xs font-semibold uppercase tracking-wide text-lime-700 mb-3 flex items-center gap-2">
                @svg('heroicon-o-wrench-screwdriver', 'w-4 h-4')
                Verf&uuml;gbare MCP-Tools
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @php
                    $tools = [
                        ['name' => 'seo_keywords.POST', 'desc' => 'Keyword manuell anlegen'],
                        ['name' => 'seo_keywords.BULK_POST', 'desc' => 'Mehrere Keywords auf einmal anlegen'],
                        ['name' => 'seo_keyword_clusters.POST', 'desc' => 'Cluster manuell erstellen'],
                        ['name' => 'seo_keywords.FETCH_METRICS', 'desc' => 'SV, KD, CPC abrufen'],
                        ['name' => 'seo_keywords.FETCH_RANKINGS', 'desc' => 'SERP-Positionen tracken'],
                        ['name' => 'seo_keywords.DISCOVER', 'desc' => 'Keywords per Seed-Keywords finden'],
                        ['name' => 'seo_keywords.DISCOVER_FROM_DOMAIN', 'desc' => 'Keywords einer Domain finden'],
                        ['name' => 'seo_keywords.DISCOVER_FROM_COMPETITORS', 'desc' => 'Keywords aller Wettbewerber importieren'],
                        ['name' => 'seo_keywords.AUTO_CLUSTER', 'desc' => 'SERP-Overlap Clustering'],
                        ['name' => 'seo_keywords.ANALYZE', 'desc' => 'Keyword-Analyse & Empfehlungen'],
                    ];
                @endphp
                @foreach($tools as $tool)
                    <div class="flex items-start gap-2 p-2 rounded border border-[var(--ui-border)]/40 bg-[var(--ui-muted-5)]">
                        <span class="flex-shrink-0 mt-0.5 w-1.5 h-1.5 rounded-full bg-lime-500"></span>
                        <div>
                            <code class="text-[10px] font-mono font-semibold text-[var(--ui-secondary)]">{{ $tool['name'] }}</code>
                            <div class="text-[10px] text-[var(--ui-muted)]">{{ $tool['desc'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</x-ui-modal>

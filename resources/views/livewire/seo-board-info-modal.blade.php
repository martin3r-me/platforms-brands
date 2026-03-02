<x-ui-modal size="xl" model="modalShow" header="SEO Board &mdash; Info & Konzept">
    <div class="space-y-8 text-sm text-[var(--ui-secondary)] leading-relaxed">

        {{-- Intro --}}
        <div>
            <p class="text-[var(--ui-muted)]">
                Das SEO Board ist das zentrale Cockpit f&uuml;r die Keyword-Strategie einer Marke. Es kombiniert Keyword-Research,
                Wettbewerbs-Analyse, Content-Planung, Ranking-Monitoring und Revisions-Tracking in einem datengetriebenen Workflow.
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
                            <span class="text-[var(--ui-muted)]">&mdash; multi-dimensionaler Opportunity Score bewertet kommerziellen Wert, Schwierigkeit, Content-L&uuml;cken und Ranking-N&auml;he</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="flex-shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-full bg-lime-600 text-white text-[10px] font-bold">5</span>
                        <div>
                            <span class="font-semibold">Content Briefs erstellen</span>
                            <span class="text-[var(--ui-muted)]">&mdash; Seitenstruktur (H1&ndash;H5), Content-Typ, Such-Intent und Ziel-URL pro Cluster definieren</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="flex-shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-full bg-lime-600 text-white text-[10px] font-bold">6</span>
                        <div>
                            <span class="font-semibold">Ver&ouml;ffentlichen & tracken</span>
                            <span class="text-[var(--ui-muted)]">&mdash; wöchentliches Ranking-Monitoring, Ursache-Wirkung durch Revisions-Log</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Content Briefs --}}
        <div>
            <h3 class="text-xs font-semibold uppercase tracking-wide text-lime-700 mb-3 flex items-center gap-2">
                @svg('heroicon-o-document-text', 'w-4 h-4')
                Content Brief Boards &mdash; Von Cluster zu Seite
            </h3>
            <div class="space-y-3">
                <p>
                    Jeder Keyword-Cluster wird zu einem <strong>Content Brief Board</strong> &mdash; dem Bauplan f&uuml;r eine Seite.
                    Ein Brief definiert die Seitenstruktur, <em>nicht</em> den Flie&szlig;text.
                </p>
                <div class="bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg p-4 space-y-3">
                    <div class="flex items-start gap-2">
                        @svg('heroicon-o-document-check', 'w-4 h-4 text-lime-600 flex-shrink-0 mt-0.5')
                        <div>
                            <span class="font-semibold text-xs">Kern-Felder</span>
                            <p class="text-xs text-[var(--ui-muted)] mt-0.5">
                                Name (H1-Kandidat), Content-Typ, Such-Intent, Status, Ziel-Slug, qualifizierte Ziel-URL, Ziel-Wortanzahl
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        @svg('heroicon-o-bars-3-bottom-left', 'w-4 h-4 text-lime-600 flex-shrink-0 mt-0.5')
                        <div>
                            <span class="font-semibold text-xs">Sections (Outline)</span>
                            <p class="text-xs text-[var(--ui-muted)] mt-0.5">
                                Seitenstruktur als H2/H3/H4-Ger&uuml;st mit kurzer Beschreibung je Section &mdash; der rote Faden f&uuml;r den Text
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        @svg('heroicon-o-link', 'w-4 h-4 text-lime-600 flex-shrink-0 mt-0.5')
                        <div>
                            <span class="font-semibold text-xs">Keyword-Cluster & Internal Links</span>
                            <p class="text-xs text-[var(--ui-muted)] mt-0.5">
                                Jeder Brief ist mit Keyword-Clustern verkn&uuml;pft (prim&auml;r/sekund&auml;r) und enth&auml;lt geplante interne Verlinkungen zu anderen Briefs
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        @svg('heroicon-o-clipboard-document-list', 'w-4 h-4 text-lime-600 flex-shrink-0 mt-0.5')
                        <div>
                            <span class="font-semibold text-xs">Notes & Constraints</span>
                            <p class="text-xs text-[var(--ui-muted)] mt-0.5">
                                Briefing-Notizen, Tone-of-Voice-Hinweise, Quellen, Einschr&auml;nkungen &mdash; alles was der Autor wissen muss
                            </p>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-[var(--ui-muted)]">
                    Content-Typen, Such-Intents und Status werden &uuml;ber <strong>Lookup-Tabellen</strong> verwaltet und sind pro Team konfigurierbar
                    (<code class="px-1 py-0.5 rounded bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 text-[10px] font-mono">brands.lookups.GET</code>).
                </p>
            </div>
        </div>

        {{-- Ranking-Tracking --}}
        <div>
            <h3 class="text-xs font-semibold uppercase tracking-wide text-lime-700 mb-3 flex items-center gap-2">
                @svg('heroicon-o-chart-bar', 'w-4 h-4')
                Ranking-Tracking &mdash; Erfolg messen
            </h3>
            <div class="space-y-3">
                <p>
                    Sobald ein Content Brief eine <strong>target_url</strong> hat und auf <em>Ver&ouml;ffentlicht</em> steht,
                    wird das Ranking <strong>jeden Sonntag automatisch</strong> getrackt.
                </p>
                <div class="bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg p-4 space-y-3">
                    <div class="flex items-start gap-2">
                        @svg('heroicon-o-clock', 'w-4 h-4 text-blue-600 flex-shrink-0 mt-0.5')
                        <div>
                            <span class="font-semibold text-xs">Automatisch (Sonntag 03:00)</span>
                            <p class="text-xs text-[var(--ui-muted)] mt-0.5">
                                F&uuml;r jeden ver&ouml;ffentlichten Brief: alle Keywords aus den verkn&uuml;pften Clustern werden per
                                DataForSEO SERP API gepr&uuml;ft. Jeder Durchlauf erzeugt immutable Snapshots.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        @svg('heroicon-o-check-badge', 'w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5')
                        <div>
                            <span class="font-semibold text-xs">is_target_match</span>
                            <p class="text-xs text-[var(--ui-muted)] mt-0.5">
                                Zeigt ob <em>exakt</em> die Ziel-URL rankt, oder eine andere Seite der Domain.
                                So erkennst du Keyword-Kannibalisierung sofort.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        @svg('heroicon-o-arrow-trending-up', 'w-4 h-4 text-purple-600 flex-shrink-0 mt-0.5')
                        <div>
                            <span class="font-semibold text-xs">Verlaufs-Analyse</span>
                            <p class="text-xs text-[var(--ui-muted)] mt-0.5">
                                Drei Ansichten: <em>latest</em> (aktuelle Positionen), <em>history</em> (Wochenverlauf mit &empty; Position, Top-10-Quote),
                                <em>detail</em> (ein Keyword &uuml;ber Zeit). Position-Delta zeigt Verbesserung/Verschlechterung.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Multi-Region --}}
        <div>
            <h3 class="text-xs font-semibold uppercase tracking-wide text-lime-700 mb-3 flex items-center gap-2">
                @svg('heroicon-o-map-pin', 'w-4 h-4')
                Multi-Region &mdash; Regionale Rankings tracken
            </h3>
            <div class="space-y-3">
                <p>
                    Jedes SEO Board kann <strong>mehrere Locations</strong> f&uuml;r SERP-Tracking konfigurieren.
                    So lassen sich Rankings f&uuml;r verschiedene Regionen oder St&auml;dte separat messen.
                </p>
                <div class="bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg p-4 space-y-3">
                    <div class="flex items-start gap-2">
                        @svg('heroicon-o-globe-europe-africa', 'w-4 h-4 text-blue-600 flex-shrink-0 mt-0.5')
                        <div>
                            <span class="font-semibold text-xs">Location-Suche</span>
                            <p class="text-xs text-[var(--ui-muted)] mt-0.5">
                                Per <code class="px-1 py-0.5 rounded bg-white border border-[var(--ui-border)]/40 text-[10px] font-mono">dataforseo_locations.GET</code>
                                k&ouml;nnen Locations gesucht werden (z.B. &bdquo;D&uuml;sseldorf&ldquo; &rarr; Code 1004074). Kostenlos, kein Credit-Verbrauch.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        @svg('heroicon-o-cog-6-tooth', 'w-4 h-4 text-indigo-600 flex-shrink-0 mt-0.5')
                        <div>
                            <span class="font-semibold text-xs">Konfiguration auf Board-Ebene</span>
                            <p class="text-xs text-[var(--ui-muted)] mt-0.5">
                                Locations werden in <code class="px-1 py-0.5 rounded bg-white border border-[var(--ui-border)]/40 text-[10px] font-mono">dataforseo_config.locations</code>
                                gespeichert. Jede Location hat <code class="text-[10px]">code</code> und <code class="text-[10px]">label</code>.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        @svg('heroicon-o-exclamation-triangle', 'w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5')
                        <div>
                            <span class="font-semibold text-xs">Kostenhinweis</span>
                            <p class="text-xs text-[var(--ui-muted)] mt-0.5">
                                Pro Location wird <em>jedes Keyword einzeln</em> gepr&uuml;ft. 3 Locations &times; 100 Keywords = 300 SERP-Calls (&asymp; 30 &euro;).
                            </p>
                        </div>
                    </div>
                </div>
                <div class="bg-white border border-[var(--ui-border)]/40 rounded-lg p-4">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-2">Beispiel-Konfiguration</p>
                    <div class="font-mono text-[11px] text-[var(--ui-muted)] space-y-1">
                        <div class="text-[var(--ui-secondary)] font-semibold">Marke: Lokaler Dienstleister (D&uuml;sseldorf + K&ouml;ln)</div>
                        <div class="mt-1">locations: [</div>
                        <div class="pl-4">{code: 1004074, label: &ldquo;D&uuml;sseldorf&rdquo;},</div>
                        <div class="pl-4">{code: 1004073, label: &ldquo;K&ouml;ln&rdquo;}</div>
                        <div>]</div>
                        <div class="pt-1 text-emerald-700">&rarr; Ranking-Report zeigt Positionen pro Stadt</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Revision Log --}}
        <div>
            <h3 class="text-xs font-semibold uppercase tracking-wide text-lime-700 mb-3 flex items-center gap-2">
                @svg('heroicon-o-pencil-square', 'w-4 h-4')
                Revision-Log &mdash; &Auml;nderungen dokumentieren
            </h3>
            <div class="space-y-3">
                <p>
                    Jede Content-&Auml;nderung an einem ver&ouml;ffentlichten Brief wird als <strong>Revision</strong> dokumentiert.
                    So l&auml;sst sich direkt korrelieren: <em>&bdquo;Nach welcher &Auml;nderung hat sich das Ranking verbessert?&ldquo;</em>
                </p>
                <div class="bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg p-4 space-y-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div>
                            <span class="font-semibold">Erfasste Daten</span>
                            <ul class="mt-1 space-y-0.5 text-[var(--ui-muted)]">
                                <li>&bull; Revisions-Typ (Optimierung, Erweiterung, Umschreibung, SEO-Fix, ...)</li>
                                <li>&bull; Zusammenfassung (Freitext)</li>
                                <li>&bull; Einzelne &Auml;nderungen (z.B. &bdquo;H2 hinzugef&uuml;gt&ldquo;, &bdquo;Absatz umgeschrieben&ldquo;)</li>
                            </ul>
                        </div>
                        <div>
                            <span class="font-semibold">Metriken vorher/nachher</span>
                            <ul class="mt-1 space-y-0.5 text-[var(--ui-muted)]">
                                <li>&bull; Wortanzahl, Anzahl H2/H3/H4</li>
                                <li>&bull; Abs&auml;tze, Bilder</li>
                                <li>&bull; Interne / externe Links</li>
                                <li>&bull; Automatisches Delta (Differenz)</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="bg-white border border-[var(--ui-border)]/40 rounded-lg p-4">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-2">Beispiel-Timeline</p>
                    <div class="font-mono text-[11px] text-[var(--ui-muted)] space-y-1.5">
                        <div class="flex items-center gap-2">
                            <span class="w-16 text-right tabular-nums">03.03.</span>
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            <span>Rankings: &empty; Pos 22, 3&times; Top-20</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-16 text-right tabular-nums">05.03.</span>
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            <span>Revision: +300 W&ouml;rter, +1 H2, 2 interne Links</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-16 text-right tabular-nums">10.03.</span>
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            <span>Rankings: &empty; Pos 16, 8&times; Top-20 <span class="text-emerald-600 font-semibold">&uarr;</span></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-16 text-right tabular-nums">18.03.</span>
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            <span>Revision: Meta-Title optimiert, 3 Cluster-Links</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-16 text-right tabular-nums">24.03.</span>
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            <span>Rankings: &empty; Pos 11, 14&times; Top-20 <span class="text-emerald-600 font-semibold">&uarr;&uarr;</span></span>
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
                                Normalisierter Score (0&ndash;100), der vier Dimensionen kombiniert:
                                <code class="px-1 py-0.5 rounded bg-white border border-[var(--ui-border)]/40 text-[10px]">Wert/KD &times; Coverage-L&uuml;cke &times; Pos-Boost</code>
                                <div class="mt-1.5 space-y-1 text-[11px]">
                                    <div>&bull; <strong>Traffic-Wert / (KD+1)</strong> &mdash; kommerzieller Wert pro Schwierigkeitseinheit</div>
                                    <div>&bull; <strong>Coverage-L&uuml;cke</strong> &mdash; je weniger Content vorhanden, desto h&ouml;her der Score</div>
                                    <div>&bull; <strong>Position-Boost</strong> &mdash; Low-hanging fruit (Pos 11&ndash;20) bekommen 1,5&times; Bonus,
                                        Top-10 wird abgewertet (0,3&times;), Pos 21&ndash;50 leicht geboostet (1,2&times;)</div>
                                </div>
                                <div class="mt-1.5 text-[11px]">Der beste Cluster bekommt Score 100. Ein @svg('heroicon-s-bolt', 'w-3 h-3 inline-block text-amber-500') markiert Low-hanging fruit.</div>
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
                            <td class="px-3 py-2 text-[var(--ui-muted)]">Anzahl Keywords mit bekannter Position in den SERPs (Verh&auml;ltnis zu Gesamtzahl).</td>
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
                            <tr>
                                <td class="px-3 py-1.5 font-medium">Brief-Ranking-Tracking</td>
                                <td class="px-3 py-1.5 text-right tabular-nums">~10 Ct/KW/Location</td>
                                <td class="px-3 py-1.5 text-[var(--ui-muted)]">W&ouml;chentlich (So), &times; Anzahl Locations</td>
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

            <p class="text-xs text-[var(--ui-muted)] mb-3">Alle Tools sind per LLM-Chat oder MCP-API aufrufbar.</p>

            {{-- SEO Keywords --}}
            <p class="text-[10px] font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-2">Keywords & Clustering</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-4">
                @php
                    $seoTools = [
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
                @foreach($seoTools as $tool)
                    <div class="flex items-start gap-2 p-2 rounded border border-[var(--ui-border)]/40 bg-[var(--ui-muted-5)]">
                        <span class="flex-shrink-0 mt-0.5 w-1.5 h-1.5 rounded-full bg-lime-500"></span>
                        <div>
                            <code class="text-[10px] font-mono font-semibold text-[var(--ui-secondary)]">{{ $tool['name'] }}</code>
                            <div class="text-[10px] text-[var(--ui-muted)]">{{ $tool['desc'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Content Briefs --}}
            <p class="text-[10px] font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-2">Content Briefs & Planung</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-4">
                @php
                    $briefTools = [
                        ['name' => 'content_brief_boards.POST', 'desc' => 'Content Brief erstellen'],
                        ['name' => 'content_brief_boards.bulk.POST', 'desc' => 'Mehrere Briefs auf einmal erstellen'],
                        ['name' => 'content_brief_sections.POST', 'desc' => 'Outline-Section (H2/H3/H4) anlegen'],
                        ['name' => 'content_brief_sections.bulk.POST', 'desc' => 'Mehrere Sections auf einmal'],
                        ['name' => 'content_brief_keyword_clusters.POST', 'desc' => 'Keyword-Cluster an Brief koppeln'],
                        ['name' => 'content_brief_links.POST', 'desc' => 'Interne Verlinkung planen'],
                        ['name' => 'content_brief_notes.POST', 'desc' => 'Briefing-Notizen & Constraints'],
                        ['name' => 'topic_cluster_map.GET', 'desc' => 'Topic-Cluster-Map visualisieren'],
                    ];
                @endphp
                @foreach($briefTools as $tool)
                    <div class="flex items-start gap-2 p-2 rounded border border-[var(--ui-border)]/40 bg-[var(--ui-muted-5)]">
                        <span class="flex-shrink-0 mt-0.5 w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <div>
                            <code class="text-[10px] font-mono font-semibold text-[var(--ui-secondary)]">{{ $tool['name'] }}</code>
                            <div class="text-[10px] text-[var(--ui-muted)]">{{ $tool['desc'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Tracking & Revisions --}}
            <p class="text-[10px] font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-2">Ranking-Tracking & Revisions</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-4">
                @php
                    $trackTools = [
                        ['name' => 'content_brief_rankings.GET', 'desc' => 'Rankings abrufen (latest/history/detail)'],
                        ['name' => 'content_brief_rankings.TRACK', 'desc' => 'Manuelles Ranking-Tracking starten'],
                        ['name' => 'content_brief_revisions.POST', 'desc' => 'Content-&Auml;nderung dokumentieren'],
                        ['name' => 'content_brief_revisions.GET', 'desc' => 'Revisions-Historie abrufen'],
                    ];
                @endphp
                @foreach($trackTools as $tool)
                    <div class="flex items-start gap-2 p-2 rounded border border-[var(--ui-border)]/40 bg-[var(--ui-muted-5)]">
                        <span class="flex-shrink-0 mt-0.5 w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                        <div>
                            <code class="text-[10px] font-mono font-semibold text-[var(--ui-secondary)]">{{ $tool['name'] }}</code>
                            <div class="text-[10px] text-[var(--ui-muted)]">{{ $tool['desc'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Lookups --}}
            <p class="text-[10px] font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-2">Konfiguration (Lookups)</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @php
                    $lookupTools = [
                        ['name' => 'dataforseo_locations.GET', 'desc' => 'Location-Codes suchen (L&auml;nder, Regionen, St&auml;dte)'],
                        ['name' => 'lookups.GET', 'desc' => 'Alle Lookup-Tabellen auflisten'],
                        ['name' => 'lookup_values.GET', 'desc' => 'Werte einer Lookup anzeigen'],
                        ['name' => 'lookup_values.POST', 'desc' => 'Neuen Lookup-Wert hinzuf&uuml;gen'],
                        ['name' => 'lookup_values.PUT', 'desc' => 'Lookup-Wert bearbeiten / deaktivieren'],
                    ];
                @endphp
                @foreach($lookupTools as $tool)
                    <div class="flex items-start gap-2 p-2 rounded border border-[var(--ui-border)]/40 bg-[var(--ui-muted-5)]">
                        <span class="flex-shrink-0 mt-0.5 w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                        <div>
                            <code class="text-[10px] font-mono font-semibold text-[var(--ui-secondary)]">{{ $tool['name'] }}</code>
                            <div class="text-[10px] text-[var(--ui-muted)]">{!! $tool['desc'] !!}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</x-ui-modal>

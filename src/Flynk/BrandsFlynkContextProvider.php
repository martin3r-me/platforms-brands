<?php

namespace Platform\Brands\Flynk;

use Illuminate\Support\Collection;
use Platform\Brands\Models\BrandsBrand;
use Platform\FlynkConnector\Contracts\ProvidesFlynkContext;
use Platform\Organization\Models\OrganizationEntity;
use Platform\Organization\Services\EntityDimensionBridge;

/**
 * Erster FLYNK-Kontext-Lieferant: liefert den Marken-Kontext eines Knotens
 * (Identität, Tonalität, Personas, Guidelines, Visuals, CTAs) als Basis für
 * FLYNKs Content-Generierung. Adapter zum Connector-Port ProvidesFlynkContext.
 */
class BrandsFlynkContextProvider implements ProvidesFlynkContext
{
    public function contextKey(): string
    {
        return 'brand';
    }

    public function contextForEntity(OrganizationEntity $node): ?array
    {
        $brand = $this->resolveBrand($node);
        if (! $brand) {
            return null;
        }

        $ci    = $brand->ciBoards()->first();
        $tov   = $brand->toneOfVoiceBoards()->with(['entries', 'dimensions'])->first();
        $pers  = $brand->personaBoards()->with('personas')->first();
        $guide = $brand->guidelineBoards()->with('chapters.entries')->first();
        $typo  = $brand->typographyBoards()->with('entries')->first();

        $entriesByType = $tov ? $tov->entries->groupBy('type') : collect();

        return array_filter([
            'name'             => $brand->name,
            'description'      => $brand->description,
            'identity'         => $this->identity($ci, $entriesByType),
            'voice'            => $this->voice($tov, $guide),
            'visuals'          => $this->visuals($ci, $typo),
            'logos'            => $this->logos($brand),
            'moodboard'        => $this->moodboard($brand),
            'assets'           => $this->assets($brand),
            'design'           => $this->design($brand),
            'audience'         => $this->audience($pers),
            'ctas'             => $this->ctas($brand),
            'references'       => $this->references($brand),
        ], fn ($v) => $v !== null && $v !== [] && $v !== '');
    }

    protected function resolveBrand(OrganizationEntity $node): ?BrandsBrand
    {
        // Verortung wie überall via Dimension-Link (Morph-Alias 'brands_brand').
        //
        // Der FLYNK-Container hängt am Website-Knoten (z. B. "PausePlus Landingpage"),
        // die Marke aber am Venture darüber. Deshalb den Org-Baum vom Knoten aus
        // nach oben laufen und die erste gefundene Marke nehmen.
        //
        // Der Wurzel-/Träger-Knoten (parent_entity_id === null, z. B. BHG.DIGITAL)
        // wird bewusst NICHT als Marken-Quelle für seine Kinder herangezogen –
        // sonst würde jeder beliebige Knoten die Dach-Marke erben.
        $current = $node;
        $guard = 0;

        while ($current && $current->parent_entity_id !== null && $guard++ < 12) {
            $link = EntityDimensionBridge::linksForEntity($current->id)
                ->first(fn ($l) => $l->linkable_type === 'brands_brand');

            if ($link) {
                return BrandsBrand::find($link->linkable_id);
            }

            $current = OrganizationEntity::find($current->parent_entity_id);
        }

        return null;
    }

    protected function identity($ci, Collection $entriesByType): array
    {
        $pick = fn (string $type) => $entriesByType->get($type, collect())->pluck('content')->filter()->values()->all();

        $slogan = $pick('slogan')[0] ?? ($ci?->slogan);

        return array_filter([
            'slogan'         => $slogan,
            'tagline'        => $ci?->tagline,
            'elevator_pitch' => $pick('elevator_pitch')[0] ?? null,
            'core_messages'  => $pick('core_message'),
            'values'         => $pick('value'),
            'claims'         => $pick('claim'),
        ], fn ($v) => $v !== null && $v !== []);
    }

    protected function voice($tov, $guide): array
    {
        $dimensions = $tov
            ? $tov->dimensions->map(fn ($d) => [
                'name'  => $d->name,
                'left'  => $d->label_left,
                'right' => $d->label_right,
                'value' => $d->value,
            ])->values()->all()
            : [];

        $dos = [];
        $donts = [];
        if ($guide) {
            foreach ($guide->chapters as $chapter) {
                foreach ($chapter->entries as $entry) {
                    if (! empty($entry->do_example))   { $dos[]   = $entry->do_example; }
                    if (! empty($entry->dont_example)) { $donts[] = $entry->dont_example; }
                }
            }
        }

        return array_filter([
            'dimensions' => $dimensions,
            'dos'        => $dos,
            'donts'      => $donts,
        ], fn ($v) => $v !== []);
    }

    protected function visuals($ci, $typo): array
    {
        $colors = $ci ? array_filter([
            'primary'   => $ci->primary_color,
            'secondary' => $ci->secondary_color,
            'accent'    => $ci->accent_color,
        ]) : [];

        $typography = $typo
            ? $typo->entries->map(fn ($e) => array_filter([
                'role'        => $e->role,
                'font_family' => $e->font_family,
                'font_weight' => $e->font_weight,
                'font_size'   => $e->font_size,
                'line_height' => $e->line_height,
            ], fn ($v) => $v !== null && $v !== ''))->values()->all()
            : [];

        return array_filter([
            'colors'      => $colors,
            'font_family' => $ci?->font_family,
            'typography'  => $typography,
        ], fn ($v) => $v !== null && $v !== [] && $v !== '');
    }

    /**
     * Logo-Varianten mit lang gültigen Datei-URLs für den FLYNK-Ingest.
     * Die ContextFile-Standard-URL läuft nach 60 Min ab; für den Push nutzen wir
     * getUrlForExternalService() mit 7 Tagen TTL (S3-presign-kompatibel).
     */
    protected function logos(BrandsBrand $brand): array
    {
        $board = $brand->logoBoards()->with('variants')->first();
        if (! $board) {
            return [];
        }

        $ttl = 60 * 24 * 7; // 7 Tage

        return $board->variants
            ->map(function ($v) use ($ttl) {
                $file = $v->getOrderedFileReferences()->first()?->contextFile;
                $url  = $file?->getUrlForExternalService($ttl);
                if (! $url) {
                    return null;
                }

                return array_filter([
                    'name'   => $v->name,
                    'type'   => $v->type,
                    'format' => $v->file_format,
                    'url'    => $url,
                ], fn ($x) => $x !== null && $x !== '');
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Moodboard-Bilder (Bildsprache/Stilrichtung) mit lang gültigen URLs.
     */
    protected function moodboard(BrandsBrand $brand): array
    {
        $board = $brand->moodboardBoards()->with('images')->first();
        if (! $board) {
            return [];
        }

        $ttl = 60 * 24 * 7; // 7 Tage

        return $board->images
            ->map(function ($img) use ($ttl) {
                $file = $img->getOrderedFileReferences()->first()?->contextFile;
                $url  = $file?->getUrlForExternalService($ttl);
                if (! $url) {
                    return null;
                }

                return array_filter([
                    'title'      => $img->title,
                    'annotation' => $img->annotation,
                    'type'       => $img->type,
                    'tags'       => $img->tags,
                    'url'        => $url,
                ], fn ($x) => $x !== null && $x !== '' && $x !== []);
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Marken-Assets (Templates, Briefköpfe, Banner …) mit lang gültigen URLs.
     */
    protected function assets(BrandsBrand $brand): array
    {
        $board = $brand->assetBoards()->with('assets')->first();
        if (! $board) {
            return [];
        }

        $ttl = 60 * 24 * 7; // 7 Tage

        return $board->assets
            ->map(function ($asset) use ($ttl) {
                $file = $asset->getOrderedFileReferences()->first()?->contextFile;
                $url  = $file?->getUrlForExternalService($ttl);
                if (! $url) {
                    return null;
                }

                return array_filter([
                    'name'      => $asset->name,
                    'type'      => $asset->asset_type,
                    'mime_type' => $asset->mime_type,
                    'tags'      => $asset->tags,
                    'url'       => $url,
                ], fn ($x) => $x !== null && $x !== '' && $x !== []);
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Design-Richtung: Bestandsseite (IST), Wireframe (SOLL-Struktur),
     * Mockup (SOLL-Design). Externe, dauerhaft gültige Links (Artifact/Figma/Live).
     */
    protected function design(BrandsBrand $brand): array
    {
        return array_filter([
            'live_url'      => $brand->live_url,
            'wireframe_url' => $brand->wireframe_url,
            'mockup_url'    => $brand->mockup_url,
        ], fn ($v) => $v !== null && $v !== '');
    }

    protected function audience($pers): array
    {
        if (! $pers) {
            return [];
        }

        $personas = $pers->personas->map(fn ($p) => array_filter([
            'name'        => $p->name,
            'age'         => $p->age,
            'gender'      => $p->gender,
            'occupation'  => $p->occupation,
            'goals'       => $p->goals,
            'pain_points' => $p->pain_points,
            'channels'    => $p->channels,
        ], fn ($v) => $v !== null && $v !== []))->values()->all();

        return $personas ? ['personas' => $personas] : [];
    }

    protected function ctas(BrandsBrand $brand): array
    {
        return $brand->ctas()
            ->where('is_active', true)
            ->orderBy('order')
            ->get()
            ->map(fn ($c) => array_filter([
                'label'        => $c->label,
                'type'         => $c->type,
                'funnel_stage' => $c->funnel_stage,
            ], fn ($v) => $v !== null && $v !== ''))
            ->values()
            ->all();
    }

    /**
     * Kuratierte Website-Benchmarks der Marke (Referenzen-Board) — die konkrete Design-
     * Richtung für den ersten Entwurf. Nach Verdikt gruppiert: `liked` = so soll es werden,
     * `disliked` = so nicht, `neutral` = zur Orientierung. Je Referenz Domain + Begründung
     * + betroffene Aspekte (Layout/Typo/Farbe/Bildsprache/…).
     */
    protected function references(BrandsBrand $brand): array
    {
        $refs = $brand->referenceBoards()->with('references')->get()
            ->flatMap(fn ($board) => $board->references);

        if ($refs->isEmpty()) {
            return [];
        }

        $map = fn ($r) => array_filter([
            'url'            => $r->url,
            'host'          => $r->host,
            'title'         => $r->title,
            'reason'        => $r->reason,
            'aspects'       => $r->aspects,
            'industry'      => $r->industry,
            'screenshot_url' => $r->screenshot_url,
        ], fn ($v) => $v !== null && $v !== '' && $v !== []);

        $byVerdict = $refs->groupBy('verdict');
        $group = fn (string $v) => $byVerdict->get($v, collect())->map($map)->values()->all();

        return array_filter([
            'liked'    => $group('like'),
            'disliked' => $group('dislike'),
            'neutral'  => $group('neutral'),
        ], fn ($v) => $v !== []);
    }
}

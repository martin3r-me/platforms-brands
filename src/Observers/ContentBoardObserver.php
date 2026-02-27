<?php

namespace Platform\Brands\Observers;

use Platform\Brands\Models\BrandsContentBoard;
use Platform\Brands\Models\BrandsSeoKeywordContext;

class ContentBoardObserver
{
    public function updated(BrandsContentBoard $contentBoard): void
    {
        if (!$contentBoard->isDirty('published_url')) {
            return;
        }

        $publishedUrl = $contentBoard->published_url;

        if ($publishedUrl === null) {
            return;
        }

        // Block-IDs des Content Boards laden
        $blockIds = $contentBoard->blocks()->pluck('id')->toArray();

        // Alle SEO Keyword Contexts finden: direkt via content_board oder via content_board_block
        $contexts = BrandsSeoKeywordContext::query()
            ->where(function ($query) use ($contentBoard, $blockIds) {
                $query->where(function ($q) use ($contentBoard) {
                    $q->where('context_type', 'content_board')
                      ->where('context_id', $contentBoard->id);
                });

                if (!empty($blockIds)) {
                    $query->orWhere(function ($q) use ($blockIds) {
                        $q->where('context_type', 'content_board_block')
                          ->whereIn('context_id', $blockIds);
                    });
                }
            })
            ->get();

        foreach ($contexts as $context) {
            // Context-URL aktualisieren
            $context->update(['url' => $publishedUrl]);

            // Verlinktes SEO Keyword aktualisieren
            $keyword = $context->seoKeyword;

            if ($keyword) {
                $keyword->update([
                    'published_url' => $publishedUrl,
                    'content_status' => 'published',
                ]);
            }
        }
    }
}

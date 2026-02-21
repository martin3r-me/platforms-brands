<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $platformIds = DB::table('brands_social_platforms')
            ->pluck('id', 'key')
            ->toArray();

        // Instagram
        $this->updateFormat($platformIds['instagram'], 'post', [
            'output_schema' => [
                'text' => ['type' => 'string', 'max_length' => 2200, 'required' => true],
                'image_url' => ['type' => 'string', 'required' => true],
                'hashtags' => ['type' => 'array', 'max_items' => 30, 'required' => false],
                'link' => ['type' => 'string', 'required' => false, 'allowed' => false],
                'alt_text' => ['type' => 'string', 'required' => false],
            ],
            'rules' => [
                'allows_links' => false,
                'hashtag_style' => 'many',
                'tone_adjustment' => 'casual',
            ],
        ]);

        $this->updateFormat($platformIds['instagram'], 'story', [
            'output_schema' => [
                'text' => ['type' => 'string', 'max_length' => 250, 'required' => false],
                'image_url' => ['type' => 'string', 'required' => true],
                'sticker_text' => ['type' => 'string', 'max_length' => 50, 'required' => false],
                'link' => ['type' => 'string', 'required' => false],
                'cta_label' => ['type' => 'string', 'max_length' => 30, 'required' => false],
            ],
            'rules' => [
                'allows_links' => true,
                'hashtag_style' => 'none',
                'tone_adjustment' => 'casual',
                'ephemeral' => true,
            ],
        ]);

        $this->updateFormat($platformIds['instagram'], 'reel', [
            'output_schema' => [
                'text' => ['type' => 'string', 'max_length' => 2200, 'required' => true],
                'video_url' => ['type' => 'string', 'required' => true],
                'cover_image_url' => ['type' => 'string', 'required' => false],
                'hashtags' => ['type' => 'array', 'max_items' => 30, 'required' => false],
                'audio_name' => ['type' => 'string', 'required' => false],
            ],
            'rules' => [
                'allows_links' => false,
                'hashtag_style' => 'many',
                'tone_adjustment' => 'casual',
                'max_duration_seconds' => 90,
            ],
        ]);

        $this->updateFormat($platformIds['instagram'], 'carousel', [
            'output_schema' => [
                'text' => ['type' => 'string', 'max_length' => 2200, 'required' => true],
                'slides' => ['type' => 'array', 'min_items' => 2, 'max_items' => 20, 'required' => true, 'items' => ['image_url' => ['type' => 'string', 'required' => true], 'alt_text' => ['type' => 'string', 'required' => false]]],
                'hashtags' => ['type' => 'array', 'max_items' => 30, 'required' => false],
            ],
            'rules' => [
                'allows_links' => false,
                'hashtag_style' => 'many',
                'tone_adjustment' => 'casual',
            ],
        ]);

        // Facebook
        $this->updateFormat($platformIds['facebook'], 'post', [
            'output_schema' => [
                'text' => ['type' => 'string', 'max_length' => 63206, 'required' => true],
                'image_url' => ['type' => 'string', 'required' => false],
                'link' => ['type' => 'string', 'required' => false],
                'hashtags' => ['type' => 'array', 'max_items' => 10, 'required' => false],
            ],
            'rules' => [
                'allows_links' => true,
                'hashtag_style' => 'few',
                'tone_adjustment' => 'conversational',
                'link_preview' => true,
            ],
        ]);

        $this->updateFormat($platformIds['facebook'], 'story', [
            'output_schema' => [
                'text' => ['type' => 'string', 'max_length' => 250, 'required' => false],
                'image_url' => ['type' => 'string', 'required' => true],
                'link' => ['type' => 'string', 'required' => false],
                'cta_label' => ['type' => 'string', 'max_length' => 30, 'required' => false],
            ],
            'rules' => [
                'allows_links' => true,
                'hashtag_style' => 'none',
                'tone_adjustment' => 'casual',
                'ephemeral' => true,
            ],
        ]);

        $this->updateFormat($platformIds['facebook'], 'reel', [
            'output_schema' => [
                'text' => ['type' => 'string', 'max_length' => 2200, 'required' => true],
                'video_url' => ['type' => 'string', 'required' => true],
                'cover_image_url' => ['type' => 'string', 'required' => false],
                'hashtags' => ['type' => 'array', 'max_items' => 10, 'required' => false],
            ],
            'rules' => [
                'allows_links' => false,
                'hashtag_style' => 'few',
                'tone_adjustment' => 'casual',
                'max_duration_seconds' => 90,
            ],
        ]);

        $this->updateFormat($platformIds['facebook'], 'video', [
            'output_schema' => [
                'title' => ['type' => 'string', 'max_length' => 255, 'required' => true],
                'text' => ['type' => 'string', 'max_length' => 63206, 'required' => false],
                'video_url' => ['type' => 'string', 'required' => true],
                'thumbnail_url' => ['type' => 'string', 'required' => false],
                'hashtags' => ['type' => 'array', 'max_items' => 10, 'required' => false],
            ],
            'rules' => [
                'allows_links' => true,
                'hashtag_style' => 'few',
                'tone_adjustment' => 'conversational',
            ],
        ]);

        // LinkedIn
        $this->updateFormat($platformIds['linkedin'], 'post', [
            'output_schema' => [
                'text' => ['type' => 'string', 'max_length' => 3000, 'required' => true],
                'image_url' => ['type' => 'string', 'required' => false],
                'link' => ['type' => 'string', 'required' => false],
                'hashtags' => ['type' => 'array', 'max_items' => 5, 'required' => false],
            ],
            'rules' => [
                'allows_links' => true,
                'hashtag_style' => 'few',
                'tone_adjustment' => 'professional',
                'link_preview' => true,
            ],
        ]);

        $this->updateFormat($platformIds['linkedin'], 'article', [
            'output_schema' => [
                'title' => ['type' => 'string', 'max_length' => 150, 'required' => true],
                'body' => ['type' => 'string', 'required' => true],
                'cover_image_url' => ['type' => 'string', 'required' => false],
                'subtitle' => ['type' => 'string', 'max_length' => 300, 'required' => false],
                'hashtags' => ['type' => 'array', 'max_items' => 5, 'required' => false],
            ],
            'rules' => [
                'allows_links' => true,
                'hashtag_style' => 'few',
                'tone_adjustment' => 'professional',
                'supports_rich_text' => true,
            ],
        ]);

        $this->updateFormat($platformIds['linkedin'], 'video', [
            'output_schema' => [
                'text' => ['type' => 'string', 'max_length' => 3000, 'required' => true],
                'video_url' => ['type' => 'string', 'required' => true],
                'thumbnail_url' => ['type' => 'string', 'required' => false],
                'hashtags' => ['type' => 'array', 'max_items' => 5, 'required' => false],
            ],
            'rules' => [
                'allows_links' => true,
                'hashtag_style' => 'few',
                'tone_adjustment' => 'professional',
            ],
        ]);

        $this->updateFormat($platformIds['linkedin'], 'carousel', [
            'output_schema' => [
                'text' => ['type' => 'string', 'max_length' => 3000, 'required' => true],
                'slides' => ['type' => 'array', 'min_items' => 2, 'max_items' => 20, 'required' => true, 'items' => ['image_url' => ['type' => 'string', 'required' => true], 'title' => ['type' => 'string', 'required' => false]]],
                'hashtags' => ['type' => 'array', 'max_items' => 5, 'required' => false],
            ],
            'rules' => [
                'allows_links' => true,
                'hashtag_style' => 'few',
                'tone_adjustment' => 'professional',
            ],
        ]);

        // TikTok
        $this->updateFormat($platformIds['tiktok'], 'video', [
            'output_schema' => [
                'text' => ['type' => 'string', 'max_length' => 4000, 'required' => true],
                'video_url' => ['type' => 'string', 'required' => true],
                'cover_image_url' => ['type' => 'string', 'required' => false],
                'hashtags' => ['type' => 'array', 'max_items' => 20, 'required' => false],
                'audio_name' => ['type' => 'string', 'required' => false],
            ],
            'rules' => [
                'allows_links' => false,
                'hashtag_style' => 'many',
                'tone_adjustment' => 'entertaining',
                'max_duration_seconds' => 600,
            ],
        ]);

        $this->updateFormat($platformIds['tiktok'], 'story', [
            'output_schema' => [
                'text' => ['type' => 'string', 'max_length' => 300, 'required' => false],
                'video_url' => ['type' => 'string', 'required' => true],
            ],
            'rules' => [
                'allows_links' => false,
                'hashtag_style' => 'none',
                'tone_adjustment' => 'entertaining',
                'ephemeral' => true,
                'max_duration_seconds' => 15,
            ],
        ]);

        $this->updateFormat($platformIds['tiktok'], 'photo', [
            'output_schema' => [
                'text' => ['type' => 'string', 'max_length' => 4000, 'required' => true],
                'image_url' => ['type' => 'string', 'required' => true],
                'hashtags' => ['type' => 'array', 'max_items' => 20, 'required' => false],
            ],
            'rules' => [
                'allows_links' => false,
                'hashtag_style' => 'many',
                'tone_adjustment' => 'entertaining',
            ],
        ]);

        // Pinterest
        $this->updateFormat($platformIds['pinterest'], 'pin', [
            'output_schema' => [
                'title' => ['type' => 'string', 'max_length' => 100, 'required' => true],
                'text' => ['type' => 'string', 'max_length' => 500, 'required' => false],
                'image_url' => ['type' => 'string', 'required' => true],
                'link' => ['type' => 'string', 'required' => true],
                'alt_text' => ['type' => 'string', 'max_length' => 500, 'required' => false],
            ],
            'rules' => [
                'allows_links' => true,
                'hashtag_style' => 'none',
                'tone_adjustment' => 'inspirational',
                'link_required' => true,
            ],
        ]);

        $this->updateFormat($platformIds['pinterest'], 'idea_pin', [
            'output_schema' => [
                'title' => ['type' => 'string', 'max_length' => 100, 'required' => true],
                'slides' => ['type' => 'array', 'min_items' => 1, 'max_items' => 20, 'required' => true, 'items' => ['image_url' => ['type' => 'string', 'required' => true], 'text' => ['type' => 'string', 'required' => false]]],
            ],
            'rules' => [
                'allows_links' => false,
                'hashtag_style' => 'none',
                'tone_adjustment' => 'inspirational',
            ],
        ]);

        $this->updateFormat($platformIds['pinterest'], 'video_pin', [
            'output_schema' => [
                'title' => ['type' => 'string', 'max_length' => 100, 'required' => true],
                'text' => ['type' => 'string', 'max_length' => 500, 'required' => false],
                'video_url' => ['type' => 'string', 'required' => true],
                'cover_image_url' => ['type' => 'string', 'required' => false],
                'link' => ['type' => 'string', 'required' => true],
            ],
            'rules' => [
                'allows_links' => true,
                'hashtag_style' => 'none',
                'tone_adjustment' => 'inspirational',
                'link_required' => true,
            ],
        ]);

        // X (Twitter)
        $this->updateFormat($platformIds['x'], 'post', [
            'output_schema' => [
                'text' => ['type' => 'string', 'max_length' => 280, 'required' => true],
                'image_url' => ['type' => 'string', 'required' => false],
                'link' => ['type' => 'string', 'required' => false],
                'hashtags' => ['type' => 'array', 'max_items' => 5, 'required' => false],
            ],
            'rules' => [
                'allows_links' => true,
                'hashtag_style' => 'few',
                'tone_adjustment' => 'concise',
                'link_shortens_text' => true,
            ],
        ]);

        $this->updateFormat($platformIds['x'], 'video', [
            'output_schema' => [
                'text' => ['type' => 'string', 'max_length' => 280, 'required' => true],
                'video_url' => ['type' => 'string', 'required' => true],
                'hashtags' => ['type' => 'array', 'max_items' => 5, 'required' => false],
            ],
            'rules' => [
                'allows_links' => true,
                'hashtag_style' => 'few',
                'tone_adjustment' => 'concise',
                'max_duration_seconds' => 140,
            ],
        ]);

        // Threads
        $this->updateFormat($platformIds['threads'], 'post', [
            'output_schema' => [
                'text' => ['type' => 'string', 'max_length' => 500, 'required' => true],
                'image_url' => ['type' => 'string', 'required' => false],
                'link' => ['type' => 'string', 'required' => false],
            ],
            'rules' => [
                'allows_links' => true,
                'hashtag_style' => 'none',
                'tone_adjustment' => 'conversational',
            ],
        ]);

        $this->updateFormat($platformIds['threads'], 'carousel', [
            'output_schema' => [
                'text' => ['type' => 'string', 'max_length' => 500, 'required' => true],
                'slides' => ['type' => 'array', 'min_items' => 2, 'max_items' => 20, 'required' => true, 'items' => ['image_url' => ['type' => 'string', 'required' => true]]],
            ],
            'rules' => [
                'allows_links' => true,
                'hashtag_style' => 'none',
                'tone_adjustment' => 'conversational',
            ],
        ]);

        $this->updateFormat($platformIds['threads'], 'video', [
            'output_schema' => [
                'text' => ['type' => 'string', 'max_length' => 500, 'required' => true],
                'video_url' => ['type' => 'string', 'required' => true],
            ],
            'rules' => [
                'allows_links' => true,
                'hashtag_style' => 'none',
                'tone_adjustment' => 'conversational',
                'max_duration_seconds' => 300,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('brands_social_platform_formats')
            ->whereIn('platform_id', DB::table('brands_social_platforms')->whereIn('key', [
                'instagram', 'facebook', 'linkedin', 'tiktok', 'pinterest', 'x', 'threads',
            ])->pluck('id'))
            ->update(['output_schema' => null, 'rules' => null]);
    }

    private function updateFormat(int $platformId, string $key, array $data): void
    {
        DB::table('brands_social_platform_formats')
            ->where('platform_id', $platformId)
            ->where('key', $key)
            ->update([
                'output_schema' => json_encode($data['output_schema']),
                'rules' => json_encode($data['rules']),
            ]);
    }
};

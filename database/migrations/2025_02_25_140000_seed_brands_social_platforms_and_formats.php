<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // Plattformen einfügen
        $platforms = [
            ['name' => 'Instagram', 'key' => 'instagram', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Facebook', 'key' => 'facebook', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'LinkedIn', 'key' => 'linkedin', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'TikTok', 'key' => 'tiktok', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Pinterest', 'key' => 'pinterest', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'X', 'key' => 'x', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Threads', 'key' => 'threads', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('brands_social_platforms')->insert($platforms);

        // Plattform-IDs abfragen
        $platformIds = DB::table('brands_social_platforms')
            ->pluck('id', 'key')
            ->toArray();

        // Formate pro Plattform
        $formats = [
            // Instagram
            ['platform_id' => $platformIds['instagram'], 'name' => 'Post', 'key' => 'post', 'aspect_ratio' => '1:1', 'media_type' => 'image', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['platform_id' => $platformIds['instagram'], 'name' => 'Story', 'key' => 'story', 'aspect_ratio' => '9:16', 'media_type' => 'image', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['platform_id' => $platformIds['instagram'], 'name' => 'Reel', 'key' => 'reel', 'aspect_ratio' => '9:16', 'media_type' => 'video', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['platform_id' => $platformIds['instagram'], 'name' => 'Carousel', 'key' => 'carousel', 'aspect_ratio' => '1:1', 'media_type' => 'carousel', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],

            // Facebook
            ['platform_id' => $platformIds['facebook'], 'name' => 'Post', 'key' => 'post', 'aspect_ratio' => '16:9', 'media_type' => 'image', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['platform_id' => $platformIds['facebook'], 'name' => 'Story', 'key' => 'story', 'aspect_ratio' => '9:16', 'media_type' => 'image', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['platform_id' => $platformIds['facebook'], 'name' => 'Reel', 'key' => 'reel', 'aspect_ratio' => '9:16', 'media_type' => 'video', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['platform_id' => $platformIds['facebook'], 'name' => 'Video', 'key' => 'video', 'aspect_ratio' => '16:9', 'media_type' => 'video', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],

            // LinkedIn
            ['platform_id' => $platformIds['linkedin'], 'name' => 'Post', 'key' => 'post', 'aspect_ratio' => '1:1', 'media_type' => 'image', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['platform_id' => $platformIds['linkedin'], 'name' => 'Article', 'key' => 'article', 'aspect_ratio' => null, 'media_type' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['platform_id' => $platformIds['linkedin'], 'name' => 'Video', 'key' => 'video', 'aspect_ratio' => '16:9', 'media_type' => 'video', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['platform_id' => $platformIds['linkedin'], 'name' => 'Carousel', 'key' => 'carousel', 'aspect_ratio' => '1:1', 'media_type' => 'carousel', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],

            // TikTok
            ['platform_id' => $platformIds['tiktok'], 'name' => 'Video', 'key' => 'video', 'aspect_ratio' => '9:16', 'media_type' => 'video', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['platform_id' => $platformIds['tiktok'], 'name' => 'Story', 'key' => 'story', 'aspect_ratio' => '9:16', 'media_type' => 'video', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['platform_id' => $platformIds['tiktok'], 'name' => 'Photo', 'key' => 'photo', 'aspect_ratio' => '9:16', 'media_type' => 'image', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],

            // Pinterest
            ['platform_id' => $platformIds['pinterest'], 'name' => 'Pin', 'key' => 'pin', 'aspect_ratio' => '2:3', 'media_type' => 'image', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['platform_id' => $platformIds['pinterest'], 'name' => 'Idea Pin', 'key' => 'idea_pin', 'aspect_ratio' => '9:16', 'media_type' => 'image', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['platform_id' => $platformIds['pinterest'], 'name' => 'Video Pin', 'key' => 'video_pin', 'aspect_ratio' => '2:3', 'media_type' => 'video', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],

            // X (Twitter)
            ['platform_id' => $platformIds['x'], 'name' => 'Post', 'key' => 'post', 'aspect_ratio' => '16:9', 'media_type' => 'image', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['platform_id' => $platformIds['x'], 'name' => 'Video', 'key' => 'video', 'aspect_ratio' => '16:9', 'media_type' => 'video', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],

            // Threads
            ['platform_id' => $platformIds['threads'], 'name' => 'Post', 'key' => 'post', 'aspect_ratio' => '1:1', 'media_type' => 'image', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['platform_id' => $platformIds['threads'], 'name' => 'Carousel', 'key' => 'carousel', 'aspect_ratio' => '1:1', 'media_type' => 'carousel', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['platform_id' => $platformIds['threads'], 'name' => 'Video', 'key' => 'video', 'aspect_ratio' => '9:16', 'media_type' => 'video', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('brands_social_platform_formats')->insert($formats);
    }

    public function down(): void
    {
        DB::table('brands_social_platform_formats')->whereIn(
            'platform_id',
            DB::table('brands_social_platforms')->whereIn('key', [
                'instagram', 'facebook', 'linkedin', 'tiktok', 'pinterest', 'x', 'threads',
            ])->pluck('id')
        )->delete();

        DB::table('brands_social_platforms')->whereIn('key', [
            'instagram', 'facebook', 'linkedin', 'tiktok', 'pinterest', 'x', 'threads',
        ])->delete();
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('brands_content_brief_keyword_clusters');
        Schema::create('brands_content_brief_keyword_clusters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_brief_id');
            $table->foreign('content_brief_id', 'fk_cbkc_content_brief')
                ->references('id')
                ->on('brands_content_brief_boards')
                ->onDelete('cascade');
            $table->foreignId('seo_keyword_cluster_id');
            $table->foreign('seo_keyword_cluster_id', 'fk_cbkc_keyword_cluster')
                ->references('id')
                ->on('brands_seo_keyword_clusters')
                ->onDelete('cascade');
            $table->string('role'); // primary, secondary, supporting
            $table->timestamps();

            $table->unique(['content_brief_id', 'seo_keyword_cluster_id'], 'cbkc_brief_cluster_unique');
            $table->index(['content_brief_id']);
            $table->index(['seo_keyword_cluster_id'], 'cbkc_seo_keyword_cluster_id_index');
            $table->index(['role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_content_brief_keyword_clusters');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('brands_brands', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('team_id')->constrained('crm_companies')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->after('company_id')->constrained('crm_contacts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brands_brands', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['contact_id']);
            $table->dropColumn(['company_id', 'contact_id']);
        });
    }
};

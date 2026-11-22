<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('geo_locations', function (Blueprint $table) {
            $table->string('created_by', 100)->nullable()->after('data_hash');
            $table->string('updated_by', 100)->nullable()->after('created_by');
        });

        Schema::table('blocks', function (Blueprint $table) {
            $table->string('actor', 100)->nullable()->after('geo_location_id');
        });
    }

    public function down(): void
    {
        Schema::table('geo_locations', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('blocks', function (Blueprint $table) {
            $table->dropColumn('actor');
        });
    }
};

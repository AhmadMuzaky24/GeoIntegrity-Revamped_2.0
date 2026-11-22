<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('height')->unique();
            $table->foreignId('geo_location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('data_hash', 64);
            $table->string('prev_hash', 64);
            $table->unsignedBigInteger('nonce')->default(0);
            $table->string('hash', 64)->unique();
            $table->unsignedBigInteger('timestamp');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocks');
    }
};

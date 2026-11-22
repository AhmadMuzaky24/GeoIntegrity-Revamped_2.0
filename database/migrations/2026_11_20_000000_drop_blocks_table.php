<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The local proof-of-work chain is gone: anchors live in the GeoAnchor
 * contract, so the `blocks` table only duplicated state.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('blocks');
    }

    public function down(): void
    {
        // Irreversible: the chain data was a duplicate of the on-chain anchors.
    }
};

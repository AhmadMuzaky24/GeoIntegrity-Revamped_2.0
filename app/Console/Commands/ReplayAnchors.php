<?php

namespace App\Console\Commands;

use App\Models\GeoLocation;
use App\Services\EvmAnchorStore;
use App\Services\GeoLocationHasher;
use Illuminate\Console\Command;

/**
 * Hardhat keeps its state in memory, so a restarted node has an empty contract.
 * This writes every point's hash back on-chain (CLI only — the web UI never
 * re-anchors a point, by design).
 */
class ReplayAnchors extends Command
{
    protected $signature = 'chain:replay';

    protected $description = 'Tulis ulang anchor semua titik ke kontrak (setelah node Hardhat restart)';

    public function handle(EvmAnchorStore $store, GeoLocationHasher $hasher): int
    {
        if (! $store->available()) {
            $this->error('Kontrak tidak terjangkau di '.config('chain.rpc_url').' — jalankan `npx hardhat node` dulu.');

            return self::FAILURE;
        }

        $written = 0;

        foreach (GeoLocation::orderBy('id')->get() as $location) {
            $hash = $location->data_hash ?: $hasher->hash($location);
            $latest = $store->latest($location->id);

            if ($latest && strtolower($latest['data_hash']) === '0x'.strtolower($hash)) {
                continue;
            }

            $location->update(['data_hash' => $hash]);

            $store->anchor($location->id, $hash, $location->created_by ?? 'Anonim');

            $this->line("#{$location->id} {$location->name} → anchor");

            $written++;
        }

        $this->info("Selesai: {$written} anchor ditulis ke kontrak.");

        return self::SUCCESS;
    }
}

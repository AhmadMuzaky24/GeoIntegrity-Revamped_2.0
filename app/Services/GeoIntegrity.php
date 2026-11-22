<?php

namespace App\Services;

use App\Models\GeoLocation;

/**
 * Jawaban dari satu pertanyaan: apakah isi baris di database masih sama dengan
 * hash yang pernah di-anchor ke kontrak?
 */
class GeoIntegrity
{
    public function __construct(
        private EvmAnchorStore $store,
        private GeoLocationHasher $hasher,
    ) {}

    /** @return array{data_hash: string, actor: ?string, timestamp: int, index: int, tx: ?string}|null */
    public function latest(GeoLocation $location): ?array
    {
        return $this->store->latest($location->id);
    }

    public function intact(GeoLocation $location, ?array $anchor = null): bool
    {
        $anchor ??= $this->latest($location);

        if ($anchor === null) {
            return false;
        }

        $anchored = $this->normalize($anchor['data_hash']);

        return $this->normalize($location->data_hash) === $anchored
            && $this->hasher->hash($location) === $anchored;
    }

    /**
     * @return array{checked: int, tampered: array<int, array{id: int, name: string, reason: string}>}
     */
    public function audit(): array
    {
        $tampered = [];
        $checked = 0;

        GeoLocation::chunkById(200, function ($locations) use (&$tampered, &$checked) {
            foreach ($locations as $location) {
                $checked++;
                $anchor = $this->latest($location);

                if ($anchor === null) {
                    $tampered[] = ['id' => $location->id, 'name' => $location->name, 'reason' => 'unregistered'];
                } elseif (! $this->intact($location, $anchor)) {
                    $tampered[] = ['id' => $location->id, 'name' => $location->name, 'reason' => 'hash_mismatch'];
                }
            }
        });

        return ['checked' => $checked, 'tampered' => $tampered];
    }

    public function normalize(?string $hash): string
    {
        return strtolower(preg_replace('/^0x/', '', (string) $hash));
    }
}

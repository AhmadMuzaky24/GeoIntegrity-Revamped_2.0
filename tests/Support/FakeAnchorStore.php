<?php

namespace Tests\Support;

use App\Services\EvmAnchorStore;

/**
 * In-memory stand-in for the contract, so feature tests never need Hardhat.
 */
class FakeAnchorStore extends EvmAnchorStore
{
    /** @var array<int, array{index: int, location_id: int, data_hash: string, actor: ?string, timestamp: int, tx: ?string}> */
    public array $anchors = [];

    public function driver(): string
    {
        return 'evm';
    }

    public function available(): bool
    {
        return true;
    }

    public function anchor(int $locationId, string $dataHash, ?string $actor): void
    {
        $this->anchors[] = [
            'index' => count($this->anchors),
            'location_id' => $locationId,
            'data_hash' => $dataHash,
            'actor' => $actor,
            'timestamp' => time(),
            'tx' => '0xfake',
        ];
    }

    public function latest(int $locationId): ?array
    {
        foreach (array_reverse($this->anchors) as $anchor) {
            if ($anchor['location_id'] === $locationId) {
                return $anchor;
            }
        }

        return null;
    }

    public function all(): array
    {
        return array_reverse($this->anchors);
    }

    public function status(): array
    {
        return ['valid' => true, 'blocks' => count($this->anchors), 'driver' => $this->driver()];
    }
}

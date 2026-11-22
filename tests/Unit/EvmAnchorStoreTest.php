<?php

namespace Tests\Unit;

use App\Services\EvmAnchorStore;
use PHPUnit\Framework\TestCase;

class EvmAnchorStoreTest extends TestCase
{
    public function test_uint_word_is_left_padded_to_32_bytes(): void
    {
        $store = new EvmAnchorStore;

        $this->assertSame(
            '0000000000000000000000000000000000000000000000000000000000000007',
            $store->uintWord(7),
        );
    }

    public function test_actor_round_trips_through_bytes32(): void
    {
        $store = new EvmAnchorStore;

        $word = $store->toBytes32('User A');

        $this->assertSame(64, strlen($word));
        $this->assertSame('User A', $store->fromBytes32($word));
    }

    public function test_actor_is_truncated_to_32_bytes(): void
    {
        $store = new EvmAnchorStore;

        $this->assertSame(64, strlen($store->toBytes32(str_repeat('x', 40))));
    }

    public function test_words_splits_rpc_result(): void
    {
        $store = new EvmAnchorStore;

        $words = $store->words('0x'.str_repeat('0', 64).str_repeat('1', 64));

        $this->assertCount(2, $words);
        $this->assertSame(str_repeat('1', 64), $words[1]);
    }

    public function test_to_int_reads_hex_word(): void
    {
        $store = new EvmAnchorStore;

        $this->assertSame(258, $store->toInt(str_pad('102', 64, '0', STR_PAD_LEFT)));
    }
}

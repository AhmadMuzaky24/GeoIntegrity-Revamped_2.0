<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Semua urusan blockchain lewat file ini.
 *
 * Baca  : JSON-RPC `eth_call` ke kontrak GeoAnchor (nilainya fixed-size, jadi
 *         hasilnya cukup dipecah per 32 byte — tanpa library ABI).
 * Tulis : memanggil chain/scripts/anchor.js, karena tanda tangan transaksi
 *         butuh secp256k1 + RLP yang tidak ada di PHP.
 */
class EvmAnchorStore
{
    private const SELECTORS = [
        'total' => '0x2ddbd13a',
        'anchorAt' => '0x16994960',
        'latestFor' => '0x852c8f4c',
        'countFor' => '0x45c2d24e',
    ];

    private const ZERO_WORD = '0000000000000000000000000000000000000000000000000000000000000000';

    public function driver(): string
    {
        return 'evm';
    }

    public function available(): bool
    {
        try {
            $this->rpc('eth_chainId', []);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function anchor(int $locationId, string $dataHash, ?string $actor): void
    {
        $key = env('CHAIN_DEPLOYER_KEY');

        if (! $key) {
            throw new RuntimeException('CHAIN_DEPLOYER_KEY belum diisi — tidak bisa mengirim transaksi anchor.');
        }

        // Node on Windows aborts (exit 134) when the child env is stripped, so
        // hand it the whole current environment plus the chain settings.
        $env = array_filter(getenv(), fn ($value) => is_string($value)) + [
            'CHAIN_RPC_URL' => config('chain.rpc_url'),
            'CHAIN_CONTRACT_ADDRESS' => config('chain.contract'),
            'CHAIN_DEPLOYER_KEY' => $key,
        ];

        $result = Process::path(base_path())
            ->env($env)
            ->timeout(60)
            ->run([
                config('chain.node_bin'),
                config('chain.write_script'),
                (string) $locationId,
                $dataHash,
                $actor ?? 'Anonim',
            ]);

        $payload = json_decode(trim($result->output()), true);

        if (! $result->successful() || ! is_array($payload) || isset($payload['error'])) {
            $detail = $payload['error']
                ?? (trim($result->errorOutput().' '.$result->output()) ?: 'tanpa keluaran');

            throw new RuntimeException(
                'Anchor ke kontrak gagal (exit '.$result->exitCode().'): '.$detail
            );
        }
    }

    public function latest(int $locationId): ?array
    {
        try {
            $words = $this->call('latestFor', $this->uintWord($locationId));
        } catch (\Throwable) {
            return null; // contract reverts with "no anchor for location"
        }

        return [
            'data_hash' => '0x'.substr($words[0], 0, 64),
            'actor' => $this->fromBytes32($words[1]),
            'timestamp' => $this->toInt($words[2]),
            'index' => $this->toInt($words[3]),
            'tx' => null,
        ];
    }

    public function all(): array
    {
        $total = min($this->toInt($this->call('total')[0] ?? self::ZERO_WORD), 200);
        $anchors = [];

        for ($index = $total - 1; $index >= 0; $index--) {
            $words = $this->call('anchorAt', $this->uintWord($index));

            $anchors[] = [
                'index' => $index,
                'location_id' => $this->toInt($words[0]),
                'data_hash' => '0x'.substr($words[1], 0, 64),
                'actor' => $this->fromBytes32($words[2]),
                'timestamp' => $this->toInt($words[3]),
                'extra' => null,
            ];
        }

        return $anchors;
    }

    public function status(): array
    {
        try {
            return [
                'valid' => true,
                'blocks' => $this->toInt($this->call('total')[0] ?? self::ZERO_WORD),
                'driver' => $this->driver(),
            ];
        } catch (\Throwable $e) {
            return [
                'valid' => false,
                'blocks' => 0,
                'driver' => $this->driver(),
                'error' => 'Kontrak tidak terjangkau di '.config('chain.rpc_url').' ('.$e->getMessage().')',
            ];
        }
    }

    /** @return array<int, string> 32-byte words without the 0x prefix */
    public function call(string $function, string $argsHex = ''): array
    {
        $data = self::SELECTORS[$function].$argsHex;

        $result = $this->rpc('eth_call', [
            ['to' => config('chain.contract'), 'data' => $data],
            'latest',
        ]);

        return $this->words($result);
    }

    /** @return array<int, string> */
    public function words(string $hex): array
    {
        $clean = preg_replace('/^0x/', '', $hex);

        return $clean === '' ? [] : str_split($clean, 64);
    }

    public function uintWord(int $value): string
    {
        return str_pad(dechex($value), 64, '0', STR_PAD_LEFT);
    }

    public function toBytes32(string $text): string
    {
        return substr(bin2hex($text), 0, 64).str_repeat('0', 64 - min(64, strlen(bin2hex($text))));
    }

    public function fromBytes32(string $word): string
    {
        $hex = preg_replace('/0+$/', '', $word) ?: '';
        $bytes = $hex === '' ? '' : hex2bin(str_pad($hex, (int) ceil(strlen($hex) / 2) * 2, '0'));

        return $bytes === false ? '' : $bytes;
    }

    public function toInt(string $word): int
    {
        return $word === '' ? 0 : (int) hexdec($word);
    }

    private function rpc(string $method, array $params): string
    {
        $response = Http::timeout(10)->acceptJson()->post(config('chain.rpc_url'), [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => $method,
            'params' => $params,
        ]);

        if (! $response->successful()) {
            throw new ConnectionException('RPC HTTP '.$response->status());
        }

        $body = $response->json();

        if (! isset($body['result'])) {
            throw new RuntimeException($body['error']['message'] ?? 'RPC error');
        }

        return (string) $body['result'];
    }
}

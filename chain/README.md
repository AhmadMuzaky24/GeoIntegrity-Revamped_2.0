# GeoIntegrity chain (Solidity + Hardhat)

Anchors live in `contracts/GeoAnchor.sol`. Laravel talks to it over JSON-RPC:
reads with `eth_call`, writes through `scripts/anchor.js` (ethers signs the tx).

## First run

```bash
cd chain
npm install
npx hardhat test                      # 6 contract tests, no node needed
npx hardhat node                      # terminal 1: local chain on :8545
npx hardhat run scripts/deploy.js --network localhost   # terminal 2: prints address
```

Copy the printed address into the Laravel `.env`:

```
CHAIN_RPC_URL=http://127.0.0.1:8545
CHAIN_CONTRACT_ADDRESS=0x...
CHAIN_DEPLOYER_KEY=0xac09...   # hardhat account #0, local only
```

Then `php artisan config:clear`, `php artisan chain:replay` (kalau database sudah
berisi titik), dan `php artisan serve` di root proyek.

## Notes

- The node keeps state in memory: after a restart, redeploy and run
  `php artisan chain:replay` to write every point's hash back on-chain.
- There is no second/offline chain — the contract is the only place anchors live.
- The contract is permissionless on purpose: the app decides who may anchor;
  the chain only guarantees a stored hash can never be replaced.
- Konsep lengkap untuk presentasi: `../ALUR-BLOCKCHAIN.md`.

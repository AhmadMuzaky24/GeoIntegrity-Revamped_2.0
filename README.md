# GeoIntegrity

Sistem verifikasi integritas data titik peta. Setiap titik diberi hash SHA-256
dari seluruh isinya, hash itu dicatat ke smart contract Solidity yang tidak punya
fungsi ubah/hapus, dan verifikasi dilakukan dengan menghitung ulang hash lalu
membandingkannya dengan catatan di kontrak.

Dibangun dengan Laravel 13 (aplikasi + SQLite), Solidity 0.8.24, dan Hardhat
(blockchain lokal).

---

## Dokumen

| File | Isi |
|---|---|
| [SETUP.md](SETUP.md) | dependency, instalasi, cara menjalankan web + Hardhat, troubleshooting |
| [ALUR-BLOCKCHAIN.md](ALUR-BLOCKCHAIN.md) | urutan teknis: file mana memanggil file mana, langkah demi langkah |
| [PETA-KONSEP.md](PETA-KONSEP.md) | peta konsep masalah–solusi, teknis, dan sistem |
| [KERANGKA-LAPORAN.md](KERANGKA-LAPORAN.md) | kerangka bab laporan + panduan menulis + fakta teknis siap pakai |
| [DEPLOY-RAILWAY.md](DEPLOY-RAILWAY.md) | deploy ke Railway: opsi arsitektur, env vars, troubleshooting |
| [DEMO-BESOK.md](DEMO-BESOK.md) | runbook demo 3 laptop: LAN, tunnel cloudflared, urutan menyalakan |
| [chain/README.md](chain/README.md) | catatan khusus kontrak dan Hardhat |

---

## Ringkas cara menjalankan

Butuh PHP 8.3+, Composer, Node 20+, npm. Detail lengkap di [SETUP.md](SETUP.md).

```bash
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate --seed

cd chain && npm install && npx hardhat test
npx hardhat node                                                   # terminal 1
npx hardhat run scripts/deploy.js --network localhost              # terminal 2
# isi CHAIN_CONTRACT_ADDRESS + CHAIN_DEPLOYER_KEY di .env, lalu:
php artisan config:clear && php artisan chain:replay
php artisan serve                                                  # terminal 3
```

Buka http://127.0.0.1:8000.

---

## Skenario demo

| Pelaku | Aksi | Hasil |
|---|---|---|
| User A | buat titik baru di peta | anchor tercatat di kontrak atas nama User A |
| User B | ubah longitude titik itu | tidak ada anchor baru |
| User C | buka detail titik | *Hash tidak valid*, *Diubah oleh pihak lain*, credential tidak bisa di-anchor ulang |

Identitas A/B/C dipilih lewat tautan di header (tanpa login) dan hanya untuk
keperluan demonstrasi.

---

## Struktur singkat

```
app/Services/GeoLocationHasher.php      hash SHA-256 dari seluruh kolom titik
app/Services/GeoIntegrity.php           memutuskan data utuh atau tidak
app/Services/EvmAnchorStore.php         baca kontrak (eth_call), tulis lewat anchor.js
app/Console/Commands/ReplayAnchors.php  php artisan chain:replay
chain/contracts/GeoAnchor.sol           penyimpanan anchor (append-only)
chain/scripts/{deploy,anchor}.js        deploy kontrak, kirim transaksi anchor
resources/views/                        peta, detail titik, explorer anchor
tests/                                  26 test PHP + 6 test kontrak
```

---

## Keamanan

`CHAIN_DEPLOYER_KEY` di `.env` adalah private key akun #0 Hardhat yang publik
diketahui. Hanya untuk node lokal. Jangan dipakai di testnet/mainnet dan jangan
pernah di-commit.

---

## Lisensi

Kode proyek ini disusun untuk keperluan akademik. Framework Laravel yang
digunakan berlisensi MIT.

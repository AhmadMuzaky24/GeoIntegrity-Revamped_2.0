# Setup GeoIntegrity

Panduan menjalankan proyek ini dari nol setelah clone dari GitHub.

Aplikasi ini terdiri dari dua bagian:

| Bagian | Isi | Butuh |
|---|---|---|
| `geo-integrity/` (root) | Laravel 13 — peta Leaflet, CRUD titik, halaman detail & verifikasi | PHP + Composer |
| `geo-integrity/chain/` | Kontrak Solidity `GeoAnchor` + Hardhat (compile, test, local node) | Node.js + npm |

Data titik disimpan di SQLite. Hash-nya di-anchor ke smart contract. Tidak ada
database blockchain kedua — satu sumber kebenaran: kontrak.

Penjelasan konsepnya untuk presentasi ada di [ALUR-BLOCKCHAIN.md](ALUR-BLOCKCHAIN.md).
Untuk menaruh aplikasi ini di internet (Railway), lihat
[DEPLOY-RAILWAY.md](DEPLOY-RAILWAY.md).

---

## 1. Dependencies yang harus di-install

### Wajib

| Dependency | Versi minimum | Cek dengan | Catatan |
|---|---|---|---|
| PHP | 8.3 (diuji di 8.5) | `php -v` | ekstensi `pdo_sqlite` dan `sqlite3` harus aktif |
| Composer | 2.x | `composer -V` | dependency manager PHP |
| Node.js | 20 LTS (diuji di 24) | `node -v` | untuk Hardhat dan skrip anchor |
| npm | 10.x | `npm -v` | ikut Node.js |
| Git | bebas | `git --version` | untuk clone repo |

Cek ekstensi PHP:

```bash
php -m | grep -i sqlite      # Windows PowerShell: php -m | Select-String sqlite
```

Harus muncul `pdo_sqlite` dan `sqlite3`. Kalau tidak ada, aktifkan di `php.ini`
(hapus `;` di depan `extension=pdo_sqlite` dan `extension=sqlite3`).

Tidak perlu MySQL/MariaDB, tidak perlu Redis, tidak perlu Docker.

### Opsional

| Dependency | Untuk apa |
|---|---|
| `npm install` di root proyek | hanya kalau ingin memakai `resources/views/welcome.blade.php` (butuh Vite/Tailwind). Aplikasi utama tidak memakainya, jadi boleh dilewati |

---

## 2. Menjalankan aplikasi

Butuh tiga terminal. Urutannya penting.

### Langkah 1 — siapkan Laravel

```bash
git clone <URL-REPO> geo-integrity
cd geo-integrity

composer install

cp .env.example .env
# Windows PowerShell: Copy-Item .env.example .env

php artisan key:generate

# buat file database SQLite kosong
touch database/database.sqlite
# Windows PowerShell: New-Item database/database.sqlite

php artisan migrate --seed
```

`migrate --seed` membuat 4 user demo: `User A`, `User B`, `User C` (email
`a@demo.test`, `b@demo.test`, `c@demo.test`, password `demo`) plus satu user test
bawaan Laravel.

### Langkah 2 — install & test kontrak (sekali saja)

```bash
cd chain
npm install
npx hardhat test
```

Hasil yang diharapkan: `6 passing`. Run pertama mengunduh compiler Solidity
(butuh internet, ± 1–2 menit).

### Langkah 3 — Terminal 1: nyalakan local chain

```bash
cd chain
npx hardhat node
```

Biarkan berjalan. Node mendengarkan di `http://127.0.0.1:8545` (chain id 31337)
dan mencetak 20 akun tes.

### Langkah 4 — Terminal 2: deploy kontrak

```bash
cd chain
npx hardhat run scripts/deploy.js --network localhost
```

Keluarannya JSON; catat `address`, misalnya
`0x5FbDB2315678afecb367f032d93F642f64180aa3`.

### Langkah 5 — Terminal 2: arahkan Laravel ke kontrak

Edit `.env` di root proyek:

```
CHAIN_RPC_URL=http://127.0.0.1:8545
CHAIN_CONTRACT_ADDRESS=0x5FbDB2315678afecb367f032d93F642f64180aa3
CHAIN_DEPLOYER_KEY=0xac0974bec39a17e36ba4a6b4d238ff944bacb478cbed5efcae784d7bf4f2ff80
```

> **Peringatan:** key di atas adalah private key akun #0 Hardhat yang publik
> diketahui. Hanya untuk node lokal. Jangan dipakai di testnet/mainnet, dan
> jangan commit `.env`.

```bash
php artisan config:clear
```

Kalau database sudah berisi titik (setelah node di-restart, atau setelah
`migrate --seed` dari data lama), tulis ulang anchor-nya ke kontrak:

```bash
php artisan chain:replay
```

### Langkah 6 — Terminal 3: jalankan web

```bash
php artisan serve
```

Buka http://127.0.0.1:8000, lalu:

1. pilih **User A** di header, isi form, klik peta untuk memilih koordinat, simpan;
2. ganti ke **User B**, edit titik tersebut (ubah longitude), simpan;
3. ganti ke **User C**, buka detail titik — muncul badge *Hash tidak valid*,
   *Diubah oleh pihak lain*, dan teks *Credential tidak dapat di-anchor ulang*.

Halaman `/chain` menampilkan semua anchor yang ada di kontrak.

---

## 3. Verifikasi bahwa semuanya sehat

```bash
# root proyek
php artisan test              # 26 test (feature + unit)

# folder chain/
npx hardhat test              # 6 test kontrak

# dengan server jalan
curl http://127.0.0.1:8000/chain/verify
```

Contoh jawaban `/chain/verify` saat semua data cocok:

```json
{"valid":true,"blocks":4,"driver":"evm","data":{"checked":4,"tampered":[]}}
```

Arti field: `valid` = kontrak terjangkau, `blocks` = jumlah anchor di kontrak,
`data.tampered` = titik yang isinya menyimpang dari anchor (`reason:
hash_mismatch` atau `unregistered`).

Bukti per titik:

```bash
curl http://127.0.0.1:8000/chain/proof/1
```

---

## 4. Kalau ada masalah

| Gejala | Penyebab & solusi |
|---|---|
| `could not find driver (sqlite)` | ekstensi `pdo_sqlite` belum aktif → edit `php.ini`, restart terminal |
| `No application encryption key has been specified` | `.env` belum dibuat / `php artisan key:generate` belum dijalankan |
| `SQLSTATE[HY000]: unable to open database file` | file `database/database.sqlite` belum ada → `touch database/database.sqlite` lalu `php artisan migrate --seed` |
| `npm install` ditolak PowerShell | `Set-ExecutionPolicy -Scope Process -Bypass` lalu ulangi |
| `npx hardhat test` gagal unduh compiler | tidak ada internet saat run pertama; ulangi saat online |
| Port 8000 dipakai | `php artisan serve --port=8001` |
| Port 8545 dipakai (node lama masih hidup) | tutup proses Hardhat sebelumnya, atau `CHAIN_RPC_URL` ke port lain |
| Halaman `/chain` menampilkan *Kontrak tidak terjangkau* | `npx hardhat node` belum jalan, atau `CHAIN_CONTRACT_ADDRESS` salah → jalankan node, deploy, `php artisan config:clear` |
| Anchor hilang setelah node di-restart | node Hardhat menyimpan state di memori → deploy ulang lalu `php artisan chain:replay` |
| Titik yang sudah diubah tidak bisa di-anchor ulang | memang disengaja: credential yang berubah jadi tidak valid permanen |

---

## 5. Yang tidak ikut ter-commit

Repo tidak menyertakan file berikut, jadi harus dibuat/di-install sendiri:

- `.env` → salin dari `.env.example`
- `vendor/` → `composer install`
- `chain/node_modules/`, `chain/artifacts/`, `chain/cache/` → `npm install`
- `database/database.sqlite` → buat file kosong + `php artisan migrate --seed`

---

## 6. Peta file penting

```
app/Services/EvmAnchorStore.php      satu-satunya jembatan ke kontrak (baca JSON-RPC, tulis via node)
app/Services/GeoIntegrity.php        aturan validasi: data masih cocok dengan anchor?
app/Services/GeoLocationHasher.php   SHA-256 konten titik
app/Console/Commands/ReplayAnchors.php  `php artisan chain:replay`
app/Support/Actor.php                identitas demo (A/B/C) dari session
chain/contracts/GeoAnchor.sol        kontrak Solidity (penyimpanan anchor)
chain/scripts/deploy.js              deploy ke node lokal
chain/scripts/anchor.js              kirim transaksi anchor (dipanggil Laravel)
chain/README.md                      catatan khusus folder chain
tests/Feature/BlockchainTest.php     test alur anchor & deteksi perubahan
tests/Feature/DemoTraceabilityTest.php  skenario A buat, B ubah, C mendeteksi
tests/Unit/EvmAnchorStoreTest.php    test encode/decode data kontrak
tests/Support/FakeAnchorStore.php    kontrak palsu untuk test (tanpa Hardhat)
```

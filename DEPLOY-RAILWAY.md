# Deploy GeoIntegrity ke Railway

Panduan praktis. Baca bagian 1 dulu — ada satu hal yang **tidak bisa** dibawa apa
adanya ke Railway (node Hardhat lokal), dan pilihan arsitekturnya ditentukan oleh
itu.

File yang sudah disiapkan di repo:

| File | Fungsi |
|---|---|
| `Dockerfile` | image service web: PHP 8.3 + ekstensi (pgsql, sqlite) + Node + Composer |
| `railway.json` | memaksa builder Dockerfile, start command, healthcheck `/up` |
| `scripts/railway-start.sh` | cache config/route, migrate `--force`, jalankan server di `$PORT` |
| `.dockerignore` | mengecualikan `vendor/`, `node_modules/`, `.env`, SQLite, test |
| `chain/Dockerfile` + `chain/start-node.sh` | hanya untuk opsi B (node Hardhat sebagai service) |

---

## 1. Kenyataan yang harus dipahami dulu

| Bagian | Di Railway | Akibat |
|---|---|---|
| Aplikasi Laravel | jalan normal di container | aman |
| SQLite | hilang setiap redeploy/restart | butuh **Postgres** atau **volume** |
| Node Hardhat lokal | state-nya di memori | anchor hilang setiap restart |
| Penulisan anchor | butuh `node` di dalam container web | sudah dipasang di `Dockerfile` |
| Kunci deployer | dipakai menandatangani transaksi | simpan di Variables, jangan di repo |

Karena itu ada tiga pilihan arsitektur. **Rekomendasi: opsi A.**

| Opsi | Blockchain | Cocok untuk | Kelemahan |
|---|---|---|---|
| **A. Chain publik (Sepolia)** | kontrak di testnet, dibaca lewat RPC | demo online yang stabil, review teman | butuh wallet testnet + RPC provider |
| B. Chain sebagai service Railway | `npx hardhat node` di service kedua | demo singkat | anchor hilang tiap restart, alamat bisa berubah |
| C. Laravel saja tanpa chain | tidak ada | hanya untuk melihat UI | semua titik berstatus *Belum terdaftar* |

---

## 2. Opsi A — Laravel di Railway + kontrak di Sepolia (rekomendasi)

### 2.1 Prasyarat

- Repo sudah di GitHub (Railway deploy dari repo).
- Akun Railway (butuh kredit trial/plan berbayar untuk deploy).
- Wallet testnet dengan saldo Sepolia ETH dari faucet
  (mis. `https://sepoliafaucet.com` atau faucet Alchemy).
- RPC Sepolia: Alchemy / Infura / Ankr, atau RPC publik
  `https://ethereum-sepolia-rpc.publicnode.com`.

### 2.2 Deploy kontrak ke Sepolia (dari laptop)

Tambahkan network ke `chain/hardhat.config.js`:

```js
networks: {
    localhost: { url: process.env.CHAIN_RPC_URL || "http://127.0.0.1:8545", chainId: 31337 },
    sepolia: {
        url: process.env.SEPOLIA_RPC_URL,
        accounts: [process.env.SEPOLIA_PRIVATE_KEY],
        chainId: 11155111,
    },
},
```

Lalu:

```bash
cd chain
SEPOLIA_RPC_URL="https://ethereum-sepolia-rpc.publicnode.com" \
SEPOLIA_PRIVATE_KEY="0x..." \
npx hardhat run scripts/deploy.js --network sepolia
```

Catat `address` dari keluaran JSON. Alamat ini yang dipakai aplikasi di Railway.

> Jangan pakai private key akun Hardhat #0 di Sepolia (key itu publik). Buat wallet
> baru, isi dari faucet, dan simpan private key hanya di Railway Variables.

### 2.3 Buat project Railway

1. Railway → **New Project** → **Deploy from GitHub repo** → pilih repo ini.
2. Service web akan dibuat otomatis; `railway.json` memaksa builder `Dockerfile`.
3. **New** → **Database** → **PostgreSQL**.
4. Di service web → **Variables**, tambahkan (referensi variabel Postgres memakai
   `${{ }}`):

```
APP_NAME=GeoIntegrity
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...            # php artisan key:generate --show
APP_URL=https://<domain-yang-diberi-railway>
LOG_CHANNEL=stderr

DB_CONNECTION=pgsql
DB_URL=${{Postgres.DATABASE_URL}}

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync

CHAIN_RPC_URL=https://ethereum-sepolia-rpc.publicnode.com
CHAIN_CONTRACT_ADDRESS=0x...   # hasil deploy langkah 2.2
CHAIN_DEPLOYER_KEY=0x...       # wallet testnet, bukan akun Hardhat #0
```

5. **Settings → Networking → Generate Domain** agar bisa diakses dari luar.
6. Deploy. Start command menjalankan `php artisan migrate --force` otomatis, jadi
   tabel dibuat saat container hidup.

### 2.4 Isi data demo (opsional)

User demo A/B/C dibuat oleh seeder. Jalankan sekali dari laptop yang sudah
terhubung ke Railway:

```bash
npm i -g @railway/cli
railway login
railway link            # pilih project + service web
railway run php artisan db:seed --force
```

Kalau tidak dijalankan, aplikasi tetap hidup; tautan identitas A/B/C di header
hanya kosong.

### 2.5 Verifikasi

```bash
curl https://<domain>/up                 # 200 "OK"
curl https://<domain>/chain/verify       # JSON
```

Jawaban yang diharapkan (database baru, belum ada titik):

```json
{"valid":true,"blocks":0,"driver":"evm","data":{"checked":0,"tampered":[]}}
```

Lalu buka web, buat satu titik (User A), edit dari User B, dan cek detail dari
User C — hasilnya sama seperti demo lokal, hanya saja anchor-nya di Sepolia.

---

## 3. Opsi B — node Hardhat sebagai service kedua di Railway

Dipakai kalau tidak ingin bergantung pada testnet. **Batasan: state node hilang
setiap restart**, jadi anchor harus ditulis ulang.

1. Service baru di project yang sama: **New → GitHub Repo** (repo yang sama).
2. **Settings → Root Directory**: `chain`
3. **Settings → Build**: Dockerfile path `chain/Dockerfile` (relatif ke root repo
   isi `Dockerfile`). Kalau Railway menolak path bersarang, set root directory ke
   `chain` lalu Dockerfile = `Dockerfile`.
4. **Variables** service chain: `PORT=8545` (opsional, default sudah 8545).
5. **Networking**: tidak perlu domain publik. Catat nama service, misal `chain`.
6. Di service **web**, ubah:

```
CHAIN_RPC_URL=http://chain.railway.internal:8545
CHAIN_CONTRACT_ADDRESS=0x5FbDB2315678afecb367f032d93F642f64180aa3
CHAIN_DEPLOYER_KEY=0xac0974bec39a17e36ba4a6b4d238ff944bacb478cbed5efcae784d7bf4f2ff80
```

Alamat `0x5FbD…0aa3` adalah alamat kontrak pada node Hardhat yang baru (nonce 0),
sehingga tetap sama setiap node dijalankan ulang. Pastikan keluaran
`start-node.sh` di log service chain menunjukkan alamat yang sama.

7. Setiap kali service chain restart: tunggu `start-node.sh` selesai deploy, lalu
   jalankan di service web:

```bash
railway run php artisan chain:replay
```

Tanpa langkah 7, semua titik akan berstatus *Belum terdaftar*.

---

## 4. Opsi C — tanpa chain (hanya untuk melihat UI)

Deploy seperti opsi A tetapi kosongkan `CHAIN_CONTRACT_ADDRESS`. Aplikasi tetap
hidup; `/chain/verify` akan melaporkan `valid:false` dengan pesan kontrak tidak
terjangkau, dan semua titik berstatus *Belum terdaftar*. Jangan dipakai untuk
menilai fitur inti.

---

## 5. SQLite di Railway (kalau tidak mau Postgres)

SQLite hanya aman kalau file-nya berada di **volume**.

1. Service web → **Settings → Volumes → Add Volume**, mount path `/data`.
2. Variables:

```
DB_CONNECTION=sqlite
DB_DATABASE=/data/database.sqlite
```

3. `scripts/railway-start.sh` sudah membuat file database bila belum ada, lalu
   menjalankan migrasi. Pastikan hanya **1 instance** (tanpa horizontal scaling).

---

## 6. Tabel variabel penting

| Variabel | Wajib | Contoh / catatan |
|---|---|---|
| `APP_KEY` | ya | `php artisan key:generate --show` |
| `APP_ENV` | ya | `production` |
| `APP_DEBUG` | ya | `false` (jangan `true` di publik) |
| `APP_URL` | ya | `https://<domain>` (dipakai untuk cookie/session) |
| `DB_CONNECTION` + `DB_URL` | ya (Postgres) | `pgsql` + `${{Postgres.DATABASE_URL}}` |
| `CHAIN_RPC_URL` | ya | URL RPC Sepolia atau `http://chain.railway.internal:8545` |
| `CHAIN_CONTRACT_ADDRESS` | ya | hasil deploy kontrak |
| `CHAIN_DEPLOYER_KEY` | ya | private key wallet testnet |
| `LOG_CHANNEL` | disarankan | `stderr` supaya log masuk ke Railway |
| `SESSION_DRIVER` | disarankan | `database` |
| `QUEUE_CONNECTION` | opsional | `sync` (tidak ada worker terpisah) |
| `PORT` | otomatis | disuntik Railway, dibaca start script |

---

## 7. Troubleshooting

| Gejala | Penyebab | Solusi |
|---|---|---|
| Halaman 500 saat pertama dibuka | `APP_KEY` kosong | isi `APP_KEY`, redeploy |
| `SQLSTATE[08006]` / koneksi DB gagal | `DB_URL` salah atau Postgres belum siap | pakai `${{Postgres.DATABASE_URL}}`, redeploy |
| `SQLSTATE[HY000] unable to open database file` | SQLite tanpa volume | pakai Postgres, atau mount volume `/data` |
| 419 Page Expired saat submit form | `APP_URL` tidak sama dengan domain, cookie `secure` | samakan `APP_URL` dengan domain Railway |
| `/chain` menampilkan *Kontrak tidak terjangkau* | RPC URL salah, key kosong, atau kontrak belum dideploy | cek `CHAIN_*`, tes `curl` RPC dari log |
| Anchor gagal `exit 134` | proses Node kehilangan environment | sudah ditangani `EvmAnchorStore` (meneruskan `getenv()`), pastikan image memakai `Dockerfile` repo |
| `node: not found` di log | image tanpa Node | pastikan builder memakai `Dockerfile`, bukan Nixpacks |
| Build lambat / gagal di `npm ci` | lockfile tidak ikut ter-commit | commit `chain/package-lock.json` |
| Healthcheck timeout | migrate lama / DB belum siap | naikkan `healthcheckTimeout`, cek log start |
| Semua titik *Belum terdaftar* (opsi B) | node baru di-restart | jalankan `railway run php artisan chain:replay` |

---

## 8. Batasan yang sebaiknya ditulis di laporan

- `php artisan serve` untuk demo; untuk produksi nyata pakai php-fpm + nginx
  (satu container, dijalankan dengan supervisor).
- Satu instance saja: SQLite/volume dan penulisan anchor tidak dirancang untuk
  skala horizontal.
- Kontrak tanpa kontrol akses: siapa pun yang tahu alamat + punya key bisa
  menambah anchor.
- Tidak ada autentikasi pengguna; identitas A/B/C hanya untuk demonstrasi.
- Node Hardhat (opsi B) tidak persisten; testnet (opsi A) persisten tetapi
  bergantung pada RPC pihak ketiga.

---

## 9. Checklist deploy

- [ ] Repo GitHub berisi `Dockerfile`, `railway.json`, `scripts/railway-start.sh`,
      `chain/package-lock.json`.
- [ ] Kontrak sudah dideploy (Sepolia atau node Railway) dan alamatnya dicatat.
- [ ] `APP_KEY` dibuat dan disimpan di Variables.
- [ ] `APP_DEBUG=false`, `APP_ENV=production`, `LOG_CHANNEL=stderr`.
- [ ] Database Postgres terhubung, migrasi jalan di log start.
- [ ] `curl /up` → 200, `curl /chain/verify` → `valid:true`.
- [ ] Skenario A–B–C berhasil di domain publik.
- [ ] `CHAIN_DEPLOYER_KEY` **tidak** ada di repo, hanya di Variables.
- [ ] Setelah restart/redeploy, anchor masih valid (opsi A) atau sudah di-replay (opsi B).

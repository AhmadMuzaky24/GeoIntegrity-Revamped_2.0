# Demo Besok — 3 Laptop Terhubung

Rencana: **satu laptop jadi host** (menjalankan web + blockchain), **dua laptop
lain** hanya membuka browser. User A, B, C memilih identitasnya masing-masing di
header aplikasi.

```
Laptop host  ──  Laravel (php artisan serve :8000)
                 Hardhat node (127.0.0.1:8545)   ← hanya diakses host, tidak perlu tunnel
                     │
                     ├── LAN  : http://<IP-host>:8000
                     └── Tunnel: https://xxxx.trycloudflare.com
                                 │
                    Laptop B ────┴──── Laptop C
```

Blockchain tidak perlu diakses dari laptop lain. Yang dibuka hanya port web.

---

## 1. Urutan menyalakan (4 terminal di laptop host)

**Terminal 1 — blockchain**

```bash
cd chain
npx hardhat node
```

Biarkan hidup. Catat alamat kontrak yang muncul saat deploy nanti.

**Terminal 2 — deploy kontrak**

```bash
cd chain
npx hardhat run scripts/deploy.js --network localhost
```

Harus keluar `"address": "0x5FbDB2315678afecb367f032d93F642f64180aa3"` — alamat
ini sudah ada di `.env`, jadi tidak perlu diubah selama node baru.

**Terminal 3 — web (wajib 0.0.0.0)**

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

`--host=0.0.0.0` penting: tanpa itu server hanya menerima koneksi dari laptop
sendiri.

Kalau database berisi titik lama, tulis ulang anchor-nya:

```bash
php artisan chain:replay
```

**Terminal 4 — tunnel (pilih salah satu)**

```bash
"C:\Program Files (x86)\cloudflared\cloudflared.exe" tunnel --url http://localhost:8000
```

Ambil URL `https://xxxx.trycloudflare.com` dari keluarannya. URL berubah setiap
kali tunnel dijalankan ulang.

Alternatif yang sudah terpasang: `ngrok http 8000` (butuh authtoken akun ngrok
gratis, jalankan `ngrok config add-authtoken <TOKEN>` sekali saja).

---

## 2. Cara laptop B dan C terhubung

**Pilihan 1 — satu Wi-Fi (paling cepat, tanpa internet)**

Bagikan `http://192.168.1.8:8000` (ganti bila IP host berubah; cek dengan
`ipconfig`). Syarat: satu jaringan, bukan Wi-Fi tamu yang memisahkan klien.

Kalau laptop lain tidak bisa membuka, izinkan port di firewall (PowerShell
**as Administrator**):

```powershell
netsh advfirewall firewall add rule name="GeoIntegrity 8000" dir=in action=allow protocol=TCP localport=8000
```

**Pilihan 2 — tunnel cloudflared (beda jaringan / Wi-Fi kampus)**

Bagikan URL `https://xxxx.trycloudflare.com`. Tidak perlu buka firewall, tidak
perlu satu jaringan, dan jalan di HTTPS.

---

## 3. Persiapan data bersih (opsional, 1 menit)

Database saat ini berisi 4 titik lama; dua di antaranya sudah berstatus *Hash
tidak valid* karena pernah diedit. Supaya panggung bersih:

```bash
php artisan migrate:fresh --seed     # menghapus semua titik, membuat user A/B/C
php artisan chain:replay             # tidak ada titik -> tidak ada anchor
```

Kalau ingin tetap memakai titik lama, lewati langkah ini dan pakai titik baru
untuk demo.

---

## 4. Pembagian peran di tiga laptop

| Laptop | Langkah |
|---|---|
| Host (kamu) | buka URL, klik **User A**, isi form, klik peta, simpan |
| Laptop B | buka URL yang sama, klik **User B**, buka titik tadi, ubah longitude, simpan |
| Laptop C | buka URL yang sama, klik **User C**, buka detail titik, tunjukkan badge |

Jangan memakai satu browser untuk dua peran: identitas disimpan di session
browser, jadi A dan B harus di browser/profil berbeda.

---

## 5. Alur demo (3 menit)

1. **A** — buat titik `Titik Demo`, koordinat dari klik peta → simpan.
   Buka `/chain`: anchor baru muncul atas nama **User A**.
2. **B** — edit longitude titik itu → simpan.
   Buka `/chain`: **tidak ada** anchor baru (jumlah tetap).
3. **C** — buka detail titik: badge merah *Hash tidak valid*, teks *Diubah oleh
   pihak lain — pembuat User A, pengubah terakhir User B*, dan *Credential tidak
   dapat di-anchor ulang*.
4. **C** — buka `/chain/verify`: titik itu muncul di `data.tampered` dengan
   `reason: hash_mismatch`.

Kalimat penutup: *"B mengubah data di database, tetapi sidik jari di kontrak tidak
ikut berubah — dan C bisa membuktikannya tanpa perlu percaya pada A atau B."*

---

## 6. Kalau ada masalah saat demo

| Gejala | Penyebab | Perbaikan cepat |
|---|---|---|
| Halaman tidak terbuka di laptop B/C | server bind ke 127.0.0.1 | ulangi dengan `--host=0.0.0.0` |
| Tidak bisa diakses via LAN | firewall / Wi-Fi tamu | tambahkan rule firewall, atau pakai tunnel |
| 419 Page Expired saat menyimpan | cookie session hilang / ganti URL | muat ulang halaman, pastikan semua laptop memakai URL yang sama |
| Halaman `/chain` bilang *Kontrak tidak terjangkau* | node Hardhat mati | nyalakan terminal 1, deploy ulang, `php artisan chain:replay` |
| Semua titik *Belum terdaftar* | node baru dijalankan ulang | deploy + `php artisan chain:replay` |
| Tunnel mati / URL 502 | cloudflared berhenti | jalankan ulang terminal 4, bagikan URL baru |
| IP host berubah | pindah Wi-Fi | cek `ipconfig`, bagikan IP baru |
| Dua orang klik simpan bersamaan terasa lambat | `php artisan serve` satu proses | hindari submit serentak, atau pakai tunnel (bebannya sama) |

---

## 7. Pre-flight malam ini (10 menit)

- [ ] Terminal 1–4 menyala, URL tunnel terbuka di browser laptop sendiri.
- [ ] Buka `/up` → tampil "Application up".
- [ ] Buka `/chain/verify` → `{"valid":true,...}`.
- [ ] Buat satu titik uji, edit, cek badge, lalu hapus titik uji itu.
- [ ] Coba buka dari HP (Wi-Fi sama) untuk memastikan bukan hanya localhost.
- [ ] Catat di kertas: URL tunnel, IP LAN, urutan terminal.
- [ ] Pastikan laptop host tidak sleep (Settings → Power → Screen never off saat
      presentasi), dan tetap terpasang charger.

---

## 8. Catatan keamanan

- URL tunnel bersifat **publik**: siapa pun yang tahu tautannya bisa menambah dan
  mengubah titik. Untuk demo kelas ini dapat diterima; jangan taruh data sensitif.
- Private key deployer hanya ada di `.env` laptop host. Jangan ditampilkan di
  layar saat presentasi.
- Matikan tunnel (`Ctrl+C` di terminal 4) setelah demo selesai.

---

## 9. Yang sudah diuji (bukan perkiraan)

```
php artisan serve --host=0.0.0.0 --port=8135        -> LAN  http://192.168.1.8:8135/up = 200
cloudflared tunnel --url http://localhost:8135      -> https://rush-encoding-odds-prize.trycloudflare.com
  GET /up            -> 200
  GET /              -> 200  (<title>GeoIntegrity</title>)
  POST /locations    -> 302  titik dibuat atas nama User A, anchor tercatat
  PUT  /locations/12 -> 302  diedit User B, anchor TIDAK bertambah
  GET  /chain/proof  -> changed_by_other:true, data_intact:false
```

Artinya jalur lengkap (browser → tunnel HTTPS → Laravel → Hardhat → kontrak)
sudah terbukti bekerja, termasuk session dan CSRF di belakang proxy.

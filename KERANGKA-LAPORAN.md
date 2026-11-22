# Kerangka Laporan Proyek GeoIntegrity

Draft kerangka + panduan menulis. Temanmu tinggal mengisi; semua fakta teknis
yang dibutuhkan sudah disediakan di bagian 7 dan 8 supaya tidak perlu menebak.

Peta konsepnya ada di [PETA-KONSEP.md](PETA-KONSEP.md), alur teknis di
[ALUR-BLOCKCHAIN.md](ALUR-BLOCKCHAIN.md), cara menjalankan di [SETUP.md](SETUP.md).

---

## 0. Cara memakai dokumen ini

1. Baca bagian 1 untuk tahu bentuk laporan yang dituju.
2. Isi bab demi bab mengikuti bagian 4 — setiap sub-bab punya kolom *tulis apa*,
   *ambil dari mana*, dan *target panjang*.
3. Setiap kali butuh angka, versi, atau keluaran perintah, ambil dari bagian 7–8,
   **lalu jalankan ulang sendiri** perintahnya dan tempel keluaran yang benar-benar
   muncul di mesinmu (jangan menyalin angka dokumen ini tanpa verifikasi).
4. Sebelum menyerahkan, jalankan checklist bagian 10.

Aturan penting: laporan ini melaporkan sistem yang **sudah berjalan**. Jangan
menulis fitur yang tidak ada (login, jaringan publik, token, mining).

---

## 1. Bentuk laporan yang dituju

| Item | Saran |
|---|---|
| Jenis | Laporan proyek / tugas akhir mata kuliah (bukan skripsi penuh) |
| Panjang | 30–45 halaman inti (BAB I–VI), di luar lampiran |
| Bahasa | Indonesia formal, istilah asing dimiringkan (*hash*, *smart contract*) |
| Format sitasi | APA 7 (penulis, tahun) atau IEEE bernomor — pilih satu, konsisten |
| Gambar | minimal 6: arsitektur, peta konsep, sequence buat, sequence periksa, ERD/tabel, tangkapan layar UI |
| Tabel | minimal 5: kebutuhan fungsional, struktur data, fungsi kontrak, hasil uji, perbandingan status |
| Lampiran | kode inti, daftar perintah, keluaran pengujian, tautan repo |

Judul yang bisa dipakai (pilih satu, sesuaikan gaya kampus):

1. "Pemanfaatan Smart Contract untuk Menjamin Integritas Data Titik Peta pada
   Aplikasi Geospasial Berbasis Laravel"
2. "Rancang Bangun Sistem Verifikasi Integritas Data Geospasial Menggunakan
   Solidity dan Hardhat"
3. "Deteksi Perubahan Data Peta Secara Sepihak dengan Pencatatan Hash pada
   Blockchain"

---

## 2. Ringkasan proyek untuk abstrak

Bahan mentah abstrak (150–250 kata), tinggal dirangkai:

- Masalah: data titik peta di database dapat diubah tanpa jejak; tidak ada cara
  membuktikan data masih seperti saat dicatat.
- Solusi: setiap titik diberi hash SHA-256 dari seluruh isinya; hash dicatat ke
  smart contract `GeoAnchor` yang tidak memiliki fungsi ubah/hapus; verifikasi
  dilakukan dengan menghitung ulang hash dan membandingkannya dengan kontrak.
- Implementasi: Laravel 13 + SQLite untuk aplikasi, Solidity 0.8.24 + Hardhat
  untuk kontrak, ethers 6 untuk penandatanganan transaksi.
- Hasil: sistem mendeteksi perubahan pada setiap kolom data (nama, deskripsi,
  latitude, longitude, kolom hash itu sendiri) dan menampilkan tiga status
  (valid, tidak valid, belum terdaftar). Pengujian: 26 test PHP (96 asersi) dan
  6 test kontrak, seluruhnya lulus, ditambah skenario tiga pengguna A–B–C.
- Kata kunci: integritas data, blockchain, smart contract, SHA-256, geospasial.

---

## 3. Struktur laporan (peta bab)

```
Halaman judul
Abstrak
Kata pengantar
Daftar isi / daftar gambar / daftar tabel
BAB I   PENDAHULUAN
BAB II  LANDASAN TEORI
BAB III ANALISIS DAN PERANCANGAN
BAB IV  IMPLEMENTASI
BAB V   PENGUJIAN DAN PEMBAHASAN
BAB VI  PENUTUP
Daftar Pustaka
Lampiran
```

---

## 4. Kerangka per bab + panduan

### BAB I — Pendahuluan (4–5 halaman)

| Sub-bab | Tulis apa | Ambil dari | Panjang |
|---|---|---|---|
| 1.1 Latar belakang | 3 paragraf: (a) data spasial dipakai untuk keputusan, (b) data di database mudah diubah dan tidak ada bukti integritas, (c) blockchain dapat menyimpan bukti yang tidak bisa diubah | PETA-KONSEP bagian 1 | 1,5 hlm |
| 1.2 Rumusan masalah | 3 butir: bagaimana menghasilkan sidik jari data; bagaimana mencatatnya agar tidak bisa diubah; bagaimana memverifikasi dan menampilkan hasilnya | turunan 1.1 | 0,3 hlm |
| 1.3 Tujuan | 3 butir, satu-satu menjawab rumusan masalah | — | 0,3 hlm |
| 1.4 Batasan masalah | daftar nyata: node blockchain lokal (bukan jaringan publik), tanpa autentikasi (identitas dipilih manual A/B/C), satu kontrak tanpa kontrol akses, data uji terbatas | bagian 9 | 0,4 hlm |
| 1.5 Manfaat | teoretis (penerapan hash + kontrak untuk integritas data) dan praktis (bukti audit untuk data peta) | — | 0,3 hlm |
| 1.6 Sistematika penulisan | satu paragraf per bab | bagian 3 | 0,3 hlm |

Tips: rumusan masalah, tujuan, dan kesimpulan **wajib sejajar satu-satu**. Kalau
rumusan ada 3 butir, kesimpulan juga 3 butir dengan urutan sama.

### BAB II — Landasan Teori (6–8 halaman)

| Sub-bab | Tulis apa | Sumber sitasi |
|---|---|---|
| 2.1 Integritas data dan checksum | kenapa checksum di tempat yang sama dengan data tidak cukup | NIST FIPS 180-4; dokumentasi hash |
| 2.2 Fungsi hash SHA-256 | sifat: deterministik, satu arah, avalanche effect; keluaran 256 bit | NIST FIPS 180-4 |
| 2.3 Blockchain dan sifat append-only | blok, transaksi, tanda tangan, kenapa riwayat sulit diubah | Buterin (2014); Wood (2014) |
| 2.4 Smart contract dan EVM | kontrak sebagai program + state; biaya eksekusi (gas) dan kenapa di lokal gratis | dokumentasi Solidity |
| 2.5 Solidity dan Hardhat | compile, test, node lokal, deploy | dokumentasi Hardhat |
| 2.6 Laravel dan SQLite | MVC, Eloquent, migrasi | dokumentasi Laravel |
| 2.7 Penelitian terkait | 3–5 penelitian sejenis (blockchain untuk integritas data/geospasial), lalu 1 paragraf posisi proyek ini | Google Scholar, cari "blockchain data integrity", "geospatial data provenance" |

Tips: setiap sub-bab diakhiri satu kalimat "pada proyek ini, konsep tersebut
dipakai untuk …" supaya teori tidak menggantung.

### BAB III — Analisis dan Perancangan (8–10 halaman)

| Sub-bab | Tulis apa | Ambil dari |
|---|---|---|
| 3.1 Analisis kebutuhan fungsional | tabel 6–8 kebutuhan (tambah titik, anchor otomatis, lihat detail, lihat daftar anchor, verifikasi satu titik, verifikasi semua, pilih identitas) | daftar route bagian 7 |
| 3.2 Kebutuhan non-fungsional | keamanan (hash + kontrak), kinerja (jumlah panggilan per halaman), keterbatasan lingkungan | bagian 9 |
| 3.3 Arsitektur sistem | gambar 4 lapisan + penjelasan tiap lapisan | PETA-KONSEP bagian 3 |
| 3.4 Perancangan data | tabel `geo_locations` (10 kolom) + alasan tiap kolom; jelaskan kenapa `data_hash` hanya salinan | bagian 7.4 |
| 3.5 Perancangan kontrak | 5 fungsi + 1 event, alasan semua nilai fixed-size, alasan tanpa fungsi ubah/hapus | bagian 7.3 |
| 3.6 Perancangan alur | 2 diagram sequence (buat anchor, periksa titik) + narasi | ALUR-BLOCKCHAIN bagian 4–5 |
| 3.7 Perancangan pengujian | tabel kasus uji: kolom diubah → harus tidak valid; kolom hash diubah → tidak valid; titik tanpa anchor → belum terdaftar; edit lewat web → tidak menambah anchor | bagian 8 |

Tips: bab ini yang paling sering dinilai. Pastikan setiap keputusan desain punya
alasan tertulis (kenapa hash seluruh kolom, kenapa `updated_at` dikecualikan,
kenapa tidak ada tombol anchor ulang).

### BAB IV — Implementasi (8–10 halaman)

| Sub-bab | Tulis apa | Ambil dari |
|---|---|---|
| 4.1 Lingkungan pengembangan | tabel versi (PHP, Laravel, Node, Hardhat, Solidity, ethers) + cara instalasi ringkas | bagian 7.1, SETUP.md |
| 4.2 Implementasi pembuat hash | kutip ±15 baris `GeoLocationHasher::hash` + jelaskan 4 keputusan (semua kolom, kecuali `data_hash`/`updated_at`, normalisasi, `ksort`) | kode |
| 4.3 Implementasi kontrak | kutip `GeoAnchor.sol` bagian struct + `anchor()` + event; jelaskan `anchors` dan `byLocation` | kode |
| 4.4 Implementasi penghubung PHP–kontrak | jelaskan dua arah: baca `eth_call` dengan selector; tulis lewat proses Node karena PHP tidak punya penandatanganan transaksi; sertakan tabel selector | kode + bagian 7.5 |
| 4.5 Implementasi antarmuka | tangkapan layar: peta+form, tabel status, detail credential (valid), detail credential (tidak valid), halaman `/chain` | jalankan aplikasi |
| 4.6 Perintah operasional | tabel perintah: jalankan node, deploy, replay, serve, test | bagian 7.6 |
| 4.7 Alur kerja tiga pengguna | narasi A membuat, B mengubah, C memeriksa + tangkapan layar | bagian 8.3 |

Tips: kode yang dikutip di laporan maksimal 20 baris per potongan. Sisanya taruh
di lampiran dan rujuk nomornya.

### BAB V — Pengujian dan Pembahasan (6–8 halaman)

| Sub-bab | Tulis apa | Ambil dari |
|---|---|---|
| 5.1 Metode pengujian | 3 lapis: unit test PHP, feature test (HTTP), test kontrak Hardhat; plus pengujian manual skenario A–B–C | bagian 8.1 |
| 5.2 Hasil pengujian otomatis | tabel: jumlah test, asersi, status; tempel keluaran asli perintah | bagian 8.2 |
| 5.3 Pengujian deteksi perubahan | tabel kasus: kolom yang diubah → hasil yang diharapkan → hasil aktual; sertakan potongan JSON `/chain/verify` | bagian 8.2 |
| 5.4 Pengujian peran pengguna | tabel A/B/C: aksi → yang terlihat di UI | bagian 8.3 |
| 5.5 Pembahasan | kenapa verifikasi butuh dua syarat; kenapa re-anchor dilarang; kenapa data peta tidak masuk chain; biaya panggilan per halaman | ALUR-BLOCKCHAIN bagian 3–6 |
| 5.6 Keterbatasan | daftar jujur (bagian 9) + saran perbaikan | bagian 9 |

Tips: bagian keterbatasan bukan kelemahan laporan — justru menunjukkan kamu paham
batas sistem. Tulis apa adanya.

### BAB VI — Penutup (1,5–2 halaman)

- 6.1 Kesimpulan: 3 butir, satu-satu menjawab rumusan masalah di 1.2. Sebut angka
  nyata (26 test PHP, 6 test kontrak, semua kolom terdeteksi).
- 6.2 Saran: minimal 3, arahkan ke bagian 9 (autentikasi, kontrak dengan kontrol
  akses, jaringan testnet, penyimpanan hash transaksi, indeks event).

### Daftar Pustaka

Format APA 7, minimal 8 sumber, semua benar-benar dibaca. Contoh entri:

```
Buterin, V. (2014). Ethereum white paper: A next generation smart contract and
    decentralized application platform. https://ethereum.org/en/whitepaper/

Wood, G. (2014). Ethereum: A secure decentralised generalised transaction ledger
    (Yellow Paper). https://ethereum.github.io/yellowpaper/paper.pdf

National Institute of Standards and Technology. (2015). Secure hash standard
    (FIPS PUB 180-4). https://doi.org/10.6028/NIST.FIPS.180-4

Solidity documentation. (n.d.). https://docs.soliditylang.org/

Hardhat documentation. (n.d.). https://hardhat.org/docs

Laravel documentation. (n.d.). https://laravel.com/docs

SQLite documentation. (n.d.). https://sqlite.org/docs.html
```

Tambahkan 2–3 artikel jurnal (2019–2025) tentang blockchain untuk integritas data
dari Google Scholar; pastikan DOI/URL benar sebelum dicantumkan.

### Lampiran

1. Kode inti: `GeoLocationHasher.php`, `GeoIntegrity.php`, `GeoAnchor.sol`,
   `EvmAnchorStore.php` (boleh hanya bagian penting).
2. Daftar perintah lengkap (dari SETUP.md).
3. Keluaran pengujian apa adanya (`php artisan test`, `npx hardhat test`).
4. Tangkapan layar tambahan.
5. Tautan repositori.

---

## 5. Panduan gaya penulisan

- Satu paragraf = satu ide, 4–7 kalimat. Hindari paragraf satu kalimat.
- Kalimat teknis: subjek–predikat–objek, tanpa "di mana" berlebihan.
- Istilah konsisten: pilih "titik peta" (bukan campur "pin", "marker", "lokasi").
- Hash ditulis dengan font monospace dan dipotong: `a855b7c2…a237` (8 karakter
  awal + 4 akhir), lengkapnya di lampiran.
- Angka: "26 test" bukan "dua puluh enam test" untuk data pengujian.
- Setiap gambar/tabel wajib dirujuk di teks ("lihat Tabel 5.2") sebelum/sesudah
  muncul, bukan menggantung tanpa rujukan.
- Jangan menulis "kami" di laporan formal; gunakan "penulis" atau kalimat pasif.

---

## 6. Urutan kerja yang disarankan (untuk temanmu)

1. Jalankan aplikasi sampai skenario A–B–C berhasil (SETUP.md). Tanpa ini, semua
   tangkapan layar tidak bisa dibuat.
2. Ambil semua tangkapan layar + keluaran perintah, simpan di satu folder
   `laporan/bukti/`.
3. Tulis BAB III (perancangan) lebih dulu — paling banyak bahan, paling jelas.
4. Lanjut BAB IV (implementasi) sambil membuka kode.
5. BAB V memakai bukti yang sudah dikumpulkan di langkah 2.
6. BAB II (teori) setelah itu, supaya teori yang dipilih relevan dengan yang
   benar-benar diimplementasikan.
7. BAB I dan BAB VI terakhir, lalu samakan rumusan masalah ↔ tujuan ↔ kesimpulan.
8. Abstrak paling akhir, 1 paragraf, tidak boleh ada sitasi.

---

## 7. Fakta teknis siap pakai

### 7.1 Lingkungan

| Komponen | Versi minimum / terpasang | Sumber |
|---|---|---|
| PHP | `^8.3` (terpasang 8.5.9), ekstensi `pdo_sqlite`, `sqlite3` | `composer.json` |
| Laravel | `^13.17` (terpasang 13.40.0) | `composer.json` |
| Node.js | 24.21.0 (LTS terbaru saat pengujian) | `node -v` |
| Hardhat | `^2.22.17` | `chain/package.json` |
| Solidity (compiler) | 0.8.24 | `chain/hardhat.config.js` |
| ethers | `^6.13.4` | `chain/package.json` |
| chai | `^4.5.0` | `chain/package.json` |
| Database | SQLite (file `database/database.sqlite`) | `.env` |

### 7.2 Route aplikasi (10 route)

| Method | URL | Fungsi |
|---|---|---|
| GET | `/` | peta + form + tabel status |
| POST | `/locations` | simpan titik + anchor |
| GET | `/locations/{id}` | detail + verifikasi |
| GET | `/locations/{id}/edit` | form edit |
| PUT | `/locations/{id}` | simpan perubahan (tanpa anchor) |
| DELETE | `/locations/{id}` | hapus titik |
| GET | `/chain` | daftar anchor dari kontrak |
| GET | `/chain/verify` | audit semua titik (JSON) |
| GET | `/chain/proof/{id}` | bukti satu titik (JSON) |
| GET | `/actor/{user}` | pilih identitas demo A/B/C |

### 7.3 Kontrak `GeoAnchor` (5 fungsi, 1 event)

| Anggota | Jenis | Keterangan |
|---|---|---|
| `anchor(uint256,bytes32,bytes32)` | fungsi tulis | menambah entri anchor |
| `total()` | baca | jumlah seluruh anchor |
| `anchorAt(uint256)` | baca | data anchor berdasarkan indeks |
| `countFor(uint256)` | baca | jumlah anchor per titik |
| `latestFor(uint256)` | baca | anchor terbaru per titik |
| `Anchored(...)` | event | dipancarkan setiap anchor baru |
| `struct Anchor` | data | `locationId`, `dataHash`, `actor`, `timestamp` |
| `anchors[]`, `byLocation` | state | penyimpanan append-only |

### 7.4 Tabel `geo_locations` (10 kolom)

| Kolom | Tipe | Peran |
|---|---|---|
| `id` | integer | identitas titik, ikut di-hash |
| `name` | varchar(150) | nama titik |
| `description` | text nullable | deskripsi |
| `latitude` | numeric(10,7) | koordinat, dinormalkan 7 desimal saat di-hash |
| `longitude` | numeric(10,7) | koordinat, idem |
| `created_at` | datetime | waktu dibuat, ikut di-hash |
| `updated_at` | datetime | **dikecualikan** dari hash (ditulis ulang tiap save) |
| `data_hash` | varchar(64) | salinan lokal hash yang di-anchor |
| `created_by` | varchar(100) | pembuat (demo A/B/C) |
| `updated_by` | varchar(100) | pengubah terakhir |

### 7.5 Selector fungsi kontrak (untuk pembacaan JSON-RPC)

| Fungsi | Selector |
|---|---|
| `latestFor(uint256)` | `0x852c8f4c` |
| `anchorAt(uint256)` | `0x16994960` |
| `total()` | `0x2ddbd13a` |
| `countFor(uint256)` | `0x45c2d24e` |

### 7.6 Perintah operasional

```bash
cd chain && npx hardhat test                            # 6 test kontrak
cd chain && npx hardhat node                            # node lokal :8545
cd chain && npx hardhat run scripts/deploy.js --network localhost
php artisan chain:replay                                # tulis ulang anchor
php artisan serve                                       # web :8000
php artisan test                                        # test PHP
```

---

## 8. Bukti pengujian (isi ulang dengan keluaran aslimu)

### 8.1 Lapisan pengujian

| Lapis | Alat | Cakupan |
|---|---|---|
| Unit PHP | PHPUnit | encode/decode nilai kontrak, konsistensi hash |
| Feature PHP | PHPUnit + HTTP test | alur simpan, verifikasi, tamper, endpoint JSON |
| Kontrak | Hardhat + chai | penyimpanan anchor, riwayat, event, revert |
| Manual | browser | skenario tiga pengguna A–B–C |

### 8.2 Hasil yang pernah dicapai di mesin pengembang

```
php artisan test   → 26 passed, 96 assertions
npx hardhat test   → 6 passing
```

Endpoint verifikasi (4 titik, semua cocok):

```json
{"valid":true,"blocks":4,"driver":"evm","data":{"checked":4,"tampered":[]}}
```

Setelah satu titik diubah (ilustrasi bentuk jawaban; angka akan berbeda di
mesinmu karena jumlah titik dan id-nya lain):

```json
{"valid":true,"blocks":4,"driver":"evm",
 "data":{"checked":4,"tampered":[{"id":2,"name":"Abogoboga","reason":"hash_mismatch"}]}}
```

Bukti satu titik:

```json
{"registered":true,"location_id":1,"driver":"evm","index":0,
 "data_hash":"0xa855b7c2…a237","anchored_by":"User A",
 "created_by":"User A","updated_by":"User A",
 "changed_by_other":false,"data_intact":true,"chain_valid":true}
```

Kasus uji yang wajib ada di laporan:

| No | Perlakuan | Hasil yang diharapkan | Hasil aktual |
|---|---|---|---|
| 1 | ubah `name` lewat SQL | `data_intact:false` | isi sendiri |
| 2 | ubah `latitude` / `longitude` | `data_intact:false` | isi sendiri |
| 3 | ubah `description` | `data_intact:false` | isi sendiri |
| 4 | ubah `created_at` | `data_intact:false` | isi sendiri |
| 5 | ubah kolom `data_hash` saja | `data_intact:false` | isi sendiri |
| 6 | ubah `updated_at` saja | tetap `true` (metadata) | isi sendiri |
| 7 | titik tanpa anchor | 404 + `registered:false` | isi sendiri |
| 8 | edit lewat web lalu cek jumlah anchor | jumlah tidak bertambah | isi sendiri |
| 9 | akses `POST /locations/{id}/anchor` | 404 (fitur dimatikan) | isi sendiri |
| 10 | A buat, B ubah, C buka detail | badge tidak valid + `changed_by_other:true` | isi sendiri |

### 8.3 Skenario tiga pengguna (untuk bab 5.4)

| Langkah | Pelaku | Aksi | Yang terlihat |
|---|---|---|---|
| 1 | User A | isi form, klik peta, simpan | titik muncul, `/chain` menampilkan anchor atas nama User A |
| 2 | User B | edit longitude, simpan | tidak ada anchor baru |
| 3 | User C | buka detail titik | *Hash tidak valid*, *Diubah oleh pihak lain — pembuat User A, pengubah terakhir User B* |
| 4 | User C | buka `/chain/verify` | titik masuk `data.tampered` dengan `reason: hash_mismatch` |

---

## 9. Keterbatasan yang harus ditulis apa adanya

1. Blockchain berjalan lokal (Hardhat node) — bukan jaringan publik, state hilang
   saat node dimatikan.
2. Tidak ada autentikasi: identitas A/B/C dipilih manual lewat session, sehingga
   siapa pun bisa berpura-pura menjadi pengguna lain.
3. Kontrak tidak punya kontrol akses: siapa pun yang memegang private key deployer
   bisa menambah anchor.
4. Data peta tidak disimpan di blockchain; yang dijamin hanya integritas hash.
5. Hash transaksi tidak disimpan, sehingga bukti per titik tidak menampilkan
   tx hash.
6. Pembacaan kontrak dilakukan per titik (satu `eth_call` per titik) — tidak
   efisien untuk ribuan titik.
7. Hash mencakup kolom `created_at`, tetapi tidak `updated_at`; perubahan khusus
   pada `updated_at` tidak dianggap perubahan data.
8. Verifikasi bergantung pada alamat kontrak di `.env`; penggantian alamat harus
   disahkan di luar sistem.

---

## 10. Checklist sebelum menyerahkan

**Isi & logika**
- [ ] Rumusan masalah, tujuan, dan kesimpulan sejajar jumlah dan urutannya.
- [ ] Setiap klaim teknis punya rujukan ke kode/tabel/gambar di laporan.
- [ ] Semua angka pengujian berasal dari perintah yang benar-benar dijalankan.
- [ ] Bagian keterbatasan ditulis, bukan disembunyikan.
- [ ] Tidak ada fitur yang tidak ada di kode (login, token, mining, jaringan publik).

**Format**
- [ ] Nomor gambar dan tabel berurutan per bab (Gambar 3.1, Tabel 5.2).
- [ ] Semua gambar dirujuk di teks dan punya keterangan.
- [ ] Potongan kode memakai font monospace, maksimal 20 baris, bernomor.
- [ ] Sitasi dalam teks cocok dengan Daftar Pustaka (tidak ada yang menggantung).
- [ ] Daftar Pustaka minimal 8 sumber, semua dapat diakses.
- [ ] Istilah asing dimiringkan konsisten.
- [ ] Daftar isi diperbarui otomatis (Word: References → Update Table).

**Teknis**
- [ ] `php artisan test` lulus di mesin sendiri.
- [ ] `npx hardhat test` lulus.
- [ ] Skenario A–B–C berhasil diulang dari kondisi bersih.
- [ ] Repo bisa di-clone orang lain dan jalan mengikuti SETUP.md.

---

## 11. Yang tidak perlu ditulis

- Riwayat percakapan pengembangan atau berapa kali revisi dilakukan.
- Kode lengkap seluruh aplikasi (taruh di lampiran/repo, bukan di badan laporan).
- Teori blockchain umum yang tidak dipakai (mis. konsensus PoW/PoS, token ERC-20).
- Perbandingan panjang dengan 5 framework lain; cukup 3 penelitian terkait.

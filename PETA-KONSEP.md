# Peta Konsep Proyek GeoIntegrity

Dokumen ini untuk **memahami dan menjelaskan** proyek, bukan untuk menulis isi
laporan. Kerangka tulisannya ada di [KERANGKA-LAPORAN.md](KERANGKA-LAPORAN.md);
alur teknis lengkap ada di [ALUR-BLOCKCHAIN.md](ALUR-BLOCKCHAIN.md).

Cara pakai: baca tiga peta di bawah, lalu pakai tabel konsep (bagian 4) sebagai
"kamus" saat menulis bab landasan teori.

---

## 1. Peta konsep masalah → solusi

Menjawab: *kenapa proyek ini ada?*

```mermaid
graph LR
    M1[Data titik peta bisa diubah<br/>diam-diam di database] -->|menimbulkan| M2[Tidak ada bukti<br/>integritas data]
    M2 -->|diselesaikan dengan| S1[Sidik jari data<br/>hash SHA-256]
    S1 -->|harus disimpan di| S2[Tempat yang tidak<br/>bisa diubah]
    S2 -->|diwujudkan sebagai| S3[Smart contract<br/>GeoAnchor]
    S3 -->|memungkinkan| S4[Verifikasi ulang<br/>oleh pihak ketiga]
    S4 -->|menghasilkan| S5[3 status:<br/>valid / tidak valid /<br/>belum terdaftar]
    M1 -->|contoh nyata| C1[User B mengubah<br/>longitude titik]
    C1 -->|terdeteksi oleh| S4
    S3 -->|dibatasi oleh| B1[Hanya menyimpan hash<br/>bukan data peta]
```

Inti yang harus dipahami: masalahnya bukan "butuh blockchain", tetapi "butuh
tempat menyimpan bukti yang tidak bisa diubah oleh pihak yang datanya diuji".

---

## 2. Peta konsep teknis (tiga file inti)

Menjawab: *bagian mana melakukan apa?*

```mermaid
graph TD
    H[GeoLocationHasher<br/>PHP] -->|menghasilkan| HASH[Hash SHA-256<br/>64 karakter hex]
    HASH -->|disalin ke kolom| COL[(data_hash<br/>di SQLite)]
    HASH -->|dikirim ke| AS[EvmAnchorStore<br/>penghubung]
    AS -->|menjalankan proses| JS[anchor.js<br/>Node + ethers]
    JS -->|transaksi bertanda tangan| SC[GeoAnchor.sol<br/>kontrak]
    SC -->|menyimpan| ANC[anchors array<br/>byLocation mapping]
    SC -->|memancarkan| EV[event Anchored]
    GI[GeoIntegrity<br/>PHP] -->|membaca via eth_call| SC
    GI -->|meminta hash ulang| H
    GI -->|membandingkan dua nilai| HASH
    GI -->|memutuskan| ST[Status credential<br/>di halaman detail]
    COL -->|dibandingkan juga| GI
```

Baca panah sebagai kalimat: *GeoLocationHasher menghasilkan hash; hash disalin ke
kolom data_hash dan dikirim ke EvmAnchorStore; EvmAnchorStore menjalankan
anchor.js; anchor.js mengirim transaksi ke GeoAnchor.sol; GeoIntegrity membaca
kontrak dan meminta hash ulang ke GeoLocationHasher, lalu membandingkan keduanya.*

---

## 3. Peta konsep sistem (empat lapisan)

Menjawab: *apa saja bagian besar sistemnya dan bagaimana mereka terhubung?*

```mermaid
graph TD
    subgraph L1[Lapisan 1 — Browser]
        UI[Peta Leaflet + form + halaman detail]
    end
    subgraph L2[Lapisan 2 — Aplikasi Laravel]
        RT[routes/web.php]
        GLC[GeoLocationController]
        BC[BlockchainController]
        DB[(SQLite geo_locations)]
    end
    subgraph L3[Lapisan 3 — Penghubung]
        AS[EvmAnchorStore]
        JS[chain/scripts/anchor.js]
        RPC[JSON-RPC HTTP]
    end
    subgraph L4[Lapisan 4 — Blockchain lokal]
        NODE[Hardhat node :8545]
        SC[GeoAnchor.sol]
    end

    UI -->|HTTP| RT
    RT --> GLC
    RT --> BC
    GLC --> DB
    GLC --> AS
    BC --> AS
    AS -->|tulis: Process::run| JS
    AS -->|baca: eth_call| RPC
    JS -->|eth_sendRawTransaction| RPC
    RPC --> NODE
    NODE --> SC
```

Aturan yang membedakan lapisan 2 dan 4: **data peta hanya hidup di lapisan 2**;
lapisan 4 hanya menyimpan 32 byte hash per titik.

---

## 4. Kamus konsep (untuk bab landasan teori)

| Konsep | Definisi satu kalimat | Muncul di kode |
|---|---|---|
| Integritas data | Jaminan bahwa data tidak berubah sejak dicatat | seluruh sistem |
| Fungsi hash kriptografis | Fungsi satu arah yang memetakan data apa pun menjadi sidik jari berukuran tetap | `GeoLocationHasher` |
| SHA-256 | Algoritma hash 256-bit, keluaran 64 karakter hex | `GeoLocationHasher::hash` |
| Anchor | Tindakan mencatat hash ke penyimpanan permanen | `EvmAnchorStore::anchor` |
| Append-only | Struktur data yang hanya bisa ditambah, tidak diubah/dihapus | `GeoAnchor.sol` |
| Smart contract | Program yang berjalan di blockchain dan menyimpan state | `GeoAnchor.sol` |
| EVM | Mesin virtual yang mengeksekusi smart contract | Hardhat node |
| Solidity | Bahasa pemrograman smart contract | `GeoAnchor.sol` |
| Hardhat | Toolchain untuk compile, test, deploy, dan menjalankan node lokal | folder `chain/` |
| JSON-RPC | Protokol pemanggilan fungsi node blockchain lewat HTTP | `EvmAnchorStore::call` |
| `eth_call` | Panggilan baca ke kontrak tanpa transaksi dan tanpa biaya | `EvmAnchorStore` |
| Tanda tangan transaksi | Bukti kriptografis bahwa transaksi dibuat pemilik private key | `anchor.js` (ethers) |
| `bytes32` | Tipe data 32 byte, dipakai untuk hash dan nama pelaku | `GeoAnchor.sol` |
| Verifikasi | Menghitung ulang hash dan membandingkannya dengan yang di-anchor | `GeoIntegrity::intact` |
| Audit | Verifikasi menyeluruh untuk semua titik | `GeoIntegrity::audit` |
| Credential | Status keabsahan satu titik terhadap anchor-nya | halaman detail |

---

## 5. Tiga kalimat yang merangkum proyek

1. Data titik peta disimpan di database biasa, tetapi **sidik jarinya** disimpan
   di smart contract yang tidak bisa diubah.
2. Saat data diperiksa, sidik jari dihitung ulang dan dibandingkan dengan yang
   ada di kontrak; beda sedikit saja berarti data sudah diubah.
3. Karena kontrak tidak punya fungsi ubah, pihak yang mengubah data tidak bisa
   "merapikan" buktinya — dan pihak ketiga bisa membuktikannya sendiri.

---

## 6. Panduan menggambar ulang peta konsep

Kalau laporan harus berupa Word/PDF, peta mermaid di atas perlu digambar ulang.

1. Buka [draw.io](https://app.diagrams.net) (gratis, tanpa akun) atau Canva.
2. Salin struktur: kotak = konsep, panah = hubungan, **setiap panah wajib punya
   label** (kata kerja: menghasilkan, disimpan di, dibaca oleh, memutuskan).
3. Warna: satu warna untuk lapisan aplikasi (biru), satu untuk blockchain
   (oranye), satu untuk data (abu-abu). Maksimal tiga warna.
4. Ekspor `PNG` 300 dpi untuk Word, atau `SVG` kalau laporan dicetak.
5. Beri nomor gambar di laporan: *Gambar 3.1 Peta konsep masalah dan solusi*.

Kesalahan yang sering terjadi pada peta konsep mahasiswa:

- panah tanpa label (pembaca tidak tahu hubungannya apa);
- kotak berisi kalimat panjang (peta konsep bukan paragraf);
- konsep yatim: kotak yang tidak terhubung ke mana pun;
- mencampur "apa" (konsep) dengan "bagaimana" (langkah prosedur) dalam satu peta —
  prosedur masuk ke diagram alur/sequence, bukan peta konsep.

---

## 7. Hubungan dokumen di repo ini

| Dokumen | Isi | Dipakai untuk |
|---|---|---|
| `PETA-KONSEP.md` (ini) | konsep + hubungannya | bab 2, slide presentasi |
| `ALUR-BLOCKCHAIN.md` | urutan langkah + file yang terlibat | bab 3 dan 4 |
| `KERANGKA-LAPORAN.md` | kerangka bab + panduan menulis | seluruh laporan |
| `SETUP.md` | cara menjalankan | bab 4 (lingkungan) dan lampiran |
| `chain/README.md` | catatan khusus kontrak | bab 4 |

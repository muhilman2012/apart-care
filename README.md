# Apart Care

Sistem pengaduan dan perawatan fasilitas apartemen berbasis Laravel 13 dengan alur QR-first, laporan publik tanpa login, SLA monitoring, dan digital work order.

## Menjalankan dengan Docker

```bash
cd ~/code/apart-care
docker compose up -d --build
docker compose exec app php artisan migrate:fresh --seed
```

Buka:

- Pengaduan publik: `http://localhost:8080/lapor`
- Portal pengelola: `http://localhost:8080/admin/login`
- Antrean tiket: `http://localhost:8080/admin/tickets`
- Laporan SLA: `http://localhost:8080/admin/sla-report`

## Akun administrator awal

| Peran | Email | Kata sandi |
|---|---|---|
| Building Manager | `admin@apartcare.test` | `password` |

Akun ini dibuat sebagai akses awal, bukan data operasional contoh. Ubah identitas dan kata sandinya setelah masuk. Nilai awal dapat disetel melalui `ADMIN_NAME`, `ADMIN_EMAIL`, dan `ADMIN_PASSWORD` pada `.env`.

Pelapor tidak memerlukan akun. Mereka membuka formulir langsung dari QR yang ditempel pada fasilitas, barang, lokasi, atau unit.

## Pemindai dan kode manual

- Halaman `/lapor` dapat memindai QR melalui kamera belakang ponsel.
- Pustaka pemindai disimpan lokal di `public/vendor`, tanpa ketergantungan CDN.
- Jika kamera ditolak atau tidak tersedia, pelapor dapat mengetik kode yang tercetak di bawah QR.
- QR valid langsung membuka `/lapor/{uuid}` dan menampilkan identitas serta gambar referensi lokasi.

## Menyiapkan data operasional

- Tambah label QR nyata di `http://localhost:8080/admin/qr-labels/create`.
- Tambah akun TRO, teknisi, penghuni, atau manager lain di `http://localhost:8080/admin/users`.
- Setiap label memperoleh UUID unik dari aplikasi dan disimpan di MySQL.
- Kode QR mengarah langsung ke formulir publik `/lapor/{uuid}` dan tidak meminta login.
- Seeder menyertakan delapan label QR simulasi yang siap dicetak untuk Tower Leonie dan fasilitas bersama.
- UUID dan kode label seeder bersifat tetap sehingga label fisik tidak berubah setelah proses seed diulang.
- Seeder tidak membuat tiket palsu, akun TRO, teknisi, atau penghuni palsu.

## Layanan Docker

- `web`: Nginx pada port 8080
- `app`: PHP 8.4 FPM + Laravel 13
- `queue`: Laravel queue worker
- `mysql`: MySQL 8.4 pada port host 3307
- `redis`: Redis 7.4 pada port host 6380

## Cakupan fondasi

- pengaduan QR-first tanpa login dengan lokasi terkunci;
- identitas pelapor minimum: nama dan nomor WhatsApp;
- halaman pengaduan mobile-first dengan pemindai QR kamera, kode manual, dan akses kamera untuk foto kondisi;
- status publik menggunakan token acak yang tidak mudah ditebak;
- autentikasi admin terpisah dan role-based access control;
- dashboard Building Management/TRO/Teknisi;
- CRUD label QR untuk unit, fasilitas, aset, dan lokasi, termasuk UUID, status aktif, SVG, serta halaman cetak;
- CRUD akun Building Manager, TRO, teknisi, dan penghuni dengan status aktif;
- antrean tiket dinamis dengan filter status dan prioritas;
- disposisi work order oleh TRO/manager kepada teknisi aktif;
- alur teknisi dengan foto kondisi sebelum, waktu mulai, foto sesudah, dan catatan hasil;
- laporan SLA per bulan yang dihitung dari tiket dan work order aktual;
- pembuatan tiket dari lokasi QR dengan unggah foto awal dan SLA otomatis;
- skema 3NF untuk gedung, tower, unit, lokasi QR, tiket, work order, kebijakan dan log SLA, lampiran, histori status, notifikasi, serta ulasan;
- aset font Plus Jakarta Sans, pustaka pemindai, dan gambar referensi disimpan secara lokal;
- diagram relasi tersedia sebagai sumber Mermaid di `docs/ERD.md` dan visual statis di `public/docs/erd.svg`.

Tahap lanjutan yang didukung struktur data: CRUD aset/unit, disposisi interaktif, unggah foto before/after, verifikasi hasil, rating, notifikasi WhatsApp/email, serta laporan SLA.

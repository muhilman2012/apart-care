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

## Notifikasi WhatsApp unofficial

Service `whatsapp` menggunakan Node.js, `whatsapp-web.js`, Chromium headless, dan sesi `LocalAuth` yang disimpan pada volume Docker. Integrasi ini ditujukan untuk demonstrasi capstone, bukan penggunaan produksi atau pengiriman massal.

1. Jalankan `docker compose up -d --build`.
2. Migrasikan database dengan `docker compose exec app php artisan migrate`.
3. Masuk sebagai manager/TRO lalu buka menu **WhatsApp**.
4. Pindai QR menggunakan **WhatsApp > Perangkat tertaut** pada nomor khusus pengujian.
5. Setelah status `READY`, perubahan status tiket akan dikirim melalui queue Laravel.

Pesan masuk tidak dijawab otomatis. Petugas membuka aplikasi WhatsApp yang sama dan membalas secara manual. Ubah `WHATSAPP_API_TOKEN` pada `.env` sebelum demo di jaringan bersama.

## Akun administrator awal

| Peran | Email | Kata sandi |
|---|---|---|
| Building Manager lokal | `admin@apartcare.test` | `password` |

Akun tersebut hanya untuk pengembangan lokal. Tampilan kredensial demo otomatis hilang pada `APP_ENV=production`, dan seeder production akan berhenti jika `ADMIN_PASSWORD` belum diisi atau masih memakai `password`. Tetapkan `ADMIN_NAME`, `ADMIN_EMAIL`, dan kata sandi unik minimal 12 karakter pada `.env` VPS.

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
- `mysql`: MySQL 8.4 pada `127.0.0.1:3307` (tidak terbuka ke jaringan)
- `redis`: Redis 7.4 pada `127.0.0.1:6380` (tidak terbuka ke jaringan)

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

## Keamanan yang sudah diterapkan

- CSRF Laravel pada seluruh form mutasi dan regenerasi session ID setelah login.
- RBAC terpisah untuk manager, TRO, dan teknisi; teknisi hanya dapat membuka tiket yang ditugaskan kepadanya.
- UUID untuk URL tiket internal dan token publik acak 64 karakter untuk status pelapor.
- Rate limit untuk login, pengiriman laporan, kode manual, verifikasi, dan permintaan perbaikan ulang.
- Header CSP, anti-clickjacking, `nosniff`, referrer policy, permissions policy, dan `noindex` untuk area privat.
- Foto laporan/bukti pekerjaan disimpan di storage privat dan disajikan melalui controller yang memeriksa token tiket atau session pengguna.
- Upload dibatasi ke JPG/JPEG/PNG/WebP maksimal 4 MB; Nginx membatasi request body 5 MB.
- Secret WhatsApp wajib berasal dari `.env`; API Node hanya berada di jaringan internal Docker dan menggunakan token.
- Database dan Redis hanya bind ke loopback host.

## Checklist sebelum VPS

1. Gunakan `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=warning`, dan `APP_URL=https://apartcare.site`.
2. Buat `APP_KEY`, password database, `ADMIN_PASSWORD`, serta `WHATSAPP_API_TOKEN` yang panjang dan unik. Jangan unggah `.env` ke Git.
3. Aktifkan HTTPS melalui reverse proxy dan set `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, serta `SESSION_DOMAIN` sesuai domain.
4. Jalankan `composer install --no-dev --classmap-authoritative`, migrasi dengan `php artisan migrate --force`, lalu `php artisan optimize`.
5. Jalankan queue worker dengan restart policy/supervisor, jadwalkan backup MySQL dan volume sesi WhatsApp, dan uji pemulihan backup.
6. Jangan publikasikan port MySQL, Redis, PHP-FPM, atau API WhatsApp. Hanya port 80/443 reverse proxy yang boleh terbuka.
7. Jalankan kembali `composer audit`, `npm audit`, test suite, dan Lighthouse setelah domain HTTPS aktif.

Engine WhatsApp tetap bersifat unofficial dan cocok untuk prototipe/capstone. Untuk produksi nyata, risiko pemblokiran akun dan perubahan protokol harus diterima atau migrasikan ke WhatsApp Business Platform resmi.

## Domain production

Domain resmi aplikasi adalah `https://apartcare.site`. Pada VPS, salin `.env.production.example` menjadi `.env`, isi seluruh secret, lalu jalankan Docker dengan `APP_BIND_IP=127.0.0.1` agar container web hanya dapat diakses melalui Nginx host. Contoh virtual host Cloudflare Origin TLS tersedia di `deploy/nginx/apartcare.site.conf`.

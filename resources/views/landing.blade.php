<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>APART-CARE · Lapor kerusakan cukup scan QR</title>
    @section('meta_title', 'APART-CARE · Lapor Kerusakan Cukup Scan QR')
    @section('meta_description', 'Scan QR fasilitas, kirim laporan tanpa login, terima pembaruan WhatsApp, dan pantau penanganan sampai beres bersama APART-CARE.')
    @include('layouts.brand-meta')
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v={{ filemtime(public_path('css/landing.css')) }}">
</head>
<body>
<header class="landing-header">
    <div class="landing-nav">
        <a class="landing-logo" href="#top" aria-label="APART-CARE beranda"><img src="{{ asset('images/brand/apartcare-logo-horizontal.svg') }}" alt="APART-CARE"></a>
        <nav aria-label="Menu utama"><a href="#cara-kerja">Cara kerja</a><a href="#fitur">Fitur</a><a href="#untuk-siapa">Untuk siapa</a></nav>
        <a class="landing-button landing-button-small" href="{{ route('complaint.scan') }}">Scan QR <span>→</span></a>
    </div>
</header>

<main>
    <section class="landing-hero" id="top">
        <div class="landing-container hero-grid">
            <div class="hero-copy">
                <div class="landing-pill"><i></i> Pengaduan fasilitas apartemen berbasis web</div>
                <h1>Lapor kerusakan cukup <em>scan QR</em>. Sisanya kami pantau sampai beres.</h1>
                <p>APART-CARE mengubah keluhan yang tercecer di chat dan kertas menjadi tiket terukur: lokasi terbaca otomatis, teknisi bekerja dalam batas SLA, dan penghuni ikut memastikan hasilnya.</p>
                <div class="hero-actions"><a class="landing-button" href="{{ route('complaint.scan') }}">Scan QR sekarang <span>→</span></a><a class="landing-button landing-button-ghost" href="#cara-kerja">Lihat cara kerjanya</a></div>
                <ul class="hero-trust"><li>Tanpa install aplikasi</li><li>Update lewat WhatsApp</li><li>Lokasi terbaca otomatis</li></ul>
            </div>
            <div class="hero-device" aria-label="Pratinjau proses scan QR">
                <div class="device-shell">
                    <div class="device-screen">
                        <div class="device-browser"><span></span>apartcare.site/lapor</div>
                        <div class="device-scan">
                            <img src="{{ asset('images/brand/apartcare-logo-horizontal-putih.svg') }}" alt="APART-CARE">
                            <strong>Arahkan kamera ke QR</strong>
                            <div class="scan-visual"><b>⌗</b><i></i></div>
                            <small>QR fasilitas akan membuka formulir laporan secara otomatis.</small>
                            <a href="{{ route('complaint.scan') }}">Buka pemindai QR</a>
                        </div>
                    </div>
                </div>
                <div class="device-caption"><span>01</span><div><strong>Mulai tanpa login</strong><small>Kamera atau kode manual</small></div></div>
            </div>
        </div>
    </section>

    <section class="landing-section steps-section" id="cara-kerja">
        <div class="landing-container">
            <div class="section-heading"><span>CARA KERJA</span><h2>Scan. Lapor. Beres.</h2><p>Empat langkah dari stiker QR di fasilitas sampai penghuni menyatakan hasilnya sesuai.</p></div>
            <ol class="step-grid">
                <li><b>1</b><h3>Scan QR di fasilitas</h3><p>QR unik mengenali gedung, lantai, lokasi, dan fasilitas yang akan dilaporkan.</p></li>
                <li><b>2</b><h3>Isi laporan singkat</h3><p>Tulis masalah, tingkat urgensi, foto kondisi, dan nomor WhatsApp tanpa membuat akun.</p></li>
                <li><b>3</b><h3>Teknisi bekerja</h3><p>Pengelola menugaskan teknisi berdasarkan kapasitas dan memantau batas SLA secara langsung.</p></li>
                <li><b>4</b><h3>Konfirmasi hasil</h3><p>Penghuni memeriksa foto hasil. Tiket ditutup jika sesuai atau dikembalikan untuk diperbaiki.</p></li>
            </ol>
        </div>
    </section>

    <section class="landing-section roles-section" id="untuk-siapa">
        <div class="landing-container">
            <div class="section-heading"><span>UNTUK SIAPA</span><h2>Satu sistem, semua pihak tetap terhubung.</h2><p>Setiap orang melihat informasi yang dibutuhkan tanpa menambah kerumitan baru.</p></div>
            <div class="role-grid">
                <article><i>01</i><h3>Penghuni</h3><p>Lapor tanpa login, pantau status dari tautan WhatsApp, dan konfirmasi hasil pekerjaan.</p></article>
                <article><i>02</i><h3>TRO</h3><p>Verifikasi laporan, lihat beban teknisi, dan alihkan work order dengan riwayat yang jelas.</p></article>
                <article><i>03</i><h3>Teknisi</h3><p>Terima lokasi presisi, instruksi kerja, batas SLA, dan unggah bukti sebelum-sesudah.</p></article>
                <article><i>04</i><h3>Manager</h3><p>Pantau antrean, kepatuhan SLA, aktivitas tim, serta laporan operasional dari Dashboard.</p></article>
            </div>
        </div>
    </section>

    <section class="landing-section feature-section" id="fitur">
        <div class="landing-container feature-grid">
            <div class="feature-copy">
                <div class="section-heading"><span>FITUR UTAMA</span><h2>Dibuat untuk laporan nyata, bukan sekadar formulir.</h2></div>
                <ul><li><b>QR unik per fasilitas</b><small>Setiap label menyimpan UUID dan lokasi yang berbeda.</small></li><li><b>Hitung mundur SLA dalam WIB</b><small>Darurat 2 jam, tinggi 8 jam, dan reguler 24 jam.</small></li><li><b>Notifikasi lintas peran</b><small>WhatsApp untuk pelapor, notifikasi internal untuk pengelola dan teknisi.</small></li><li><b>Bukti kerja yang dapat diperiksa</b><small>Foto kondisi awal dan hasil pekerjaan tersimpan bersama tiket.</small></li></ul>
            </div>
            <div class="ticket-preview">
                <header><div><span></span><strong>Tiket aktif</strong></div><small>Dashboard · WIB</small></header>
                <div class="preview-row urgent"><div><strong>Pompa booster mati</strong><small>Ruang Pompa B1 · Tower A</small></div><span>Darurat</span><b>00:48</b></div>
                <div class="preview-row high"><div><strong>Pintu lift tidak menutup</strong><small>Lift Penumpang A · Lantai 12</small></div><span>Tinggi</span><b>05:42</b></div>
                <div class="preview-row regular"><div><strong>Lampu koridor padam</strong><small>Koridor Timur · Lantai 18</small></div><span>Reguler</span><b>23:09</b></div>
                <footer><i></i> Data diperbarui otomatis tanpa refresh</footer>
            </div>
        </div>
    </section>

    <section class="landing-proof">
        <div class="landing-container proof-grid"><div><strong>QR</strong><span>langsung ke lokasi laporan</span></div><div><strong>24/7</strong><span>kanal pengaduan tersedia</span></div><div><strong>4 peran</strong><span>penghuni, TRO, teknisi, manager</span></div><div><strong>1 alur</strong><span>scan hingga tiket selesai</span></div></div>
    </section>

    <section class="landing-cta">
        <div class="landing-container cta-card-landing"><div><span>MULAI MELAPOR</span><h2>Temukan QR fasilitas, lalu biarkan APART-CARE mengurus alurnya.</h2><p>Jika kamera tidak tersedia, Anda tetap dapat memasukkan kode label secara manual.</p></div><a class="landing-button landing-button-light" href="{{ route('complaint.scan') }}">Scan QR sekarang <span>→</span></a></div>
    </section>
</main>

<footer class="landing-footer"><div class="landing-container"><img src="{{ asset('images/brand/apartcare-logo-horizontal-putih.svg') }}" alt="APART-CARE"><span>Scan · Lapor · Beres</span><small>Capstone Project STSI4440 · Kelompok C · Universitas Terbuka 2026</small></div></footer>
</body>
</html>

@extends('layouts.public-mobile')
@section('title', 'Pindai QR — Apart Care')
@section('content')
<div class="mobile-content scan-content">
    <section class="scan-intro">
        <span class="eyebrow">Pengaduan tanpa login</span>
        <h1>Pindai label di lokasi masalah.</h1>
        <p class="lead">Izinkan kamera, lalu arahkan ke QR Apart Care. Lokasi dan fasilitas akan terisi otomatis.</p>
    </section>

    <section class="scanner-card" aria-labelledby="scanner-title">
        <div class="scanner-head"><div><span class="section-kicker">Opsi utama</span><h2 id="scanner-title">Gunakan kamera</h2></div><span class="live-dot">Siap</span></div>
        <div id="qr-reader" class="qr-reader" aria-live="polite">
            <div class="scan-placeholder"><div class="scan-frame" aria-hidden="true"><div class="qr-glyph">▦</div></div><p>Kamera belum dinyalakan</p></div>
        </div>
        <p id="scanner-status" class="scanner-status">Kamera hanya digunakan saat proses pemindaian.</p>
        <div class="scanner-actions"><button id="start-scanner" class="button" type="button">Nyalakan kamera</button><button id="stop-scanner" class="button button-quiet" type="button" hidden>Matikan</button></div>
    </section>

    <div class="divider"><span>atau</span></div>

    <section class="manual-card">
        <span class="section-kicker">Kamera tidak tersedia?</span>
        <h2>Masukkan kode label</h2>
        <p>Kode tercetak di bawah QR, misalnya <strong>LEO-LIFT-A</strong>.</p>
        <form method="post" action="{{ route('complaint.code') }}">@csrf
            <label for="location_code">Kode lokasi</label>
            <div class="manual-row"><input id="location_code" name="location_code" value="{{ old('location_code') }}" maxlength="50" autocapitalize="characters" autocomplete="off" required placeholder="LEO-LIFT-A"><button type="submit" aria-label="Buka formulir laporan">→</button></div>
            @error('location_code')<div class="error">{{ $message }}</div>@enderror
        </form>
    </section>

    <div class="steps"><div class="step"><b>1</b>Pindai / ketik kode</div><div class="step"><b>2</b>Isi kondisi</div><div class="step"><b>3</b>Kirim laporan</div></div>
    <a class="admin-link" href="{{ route('login') }}">Portal pengelola →</a>
</div>
@endsection
@push('scripts')
<script src="{{ asset('vendor/html5-qrcode.min.js') }}" defer></script>
<script src="{{ asset('js/qr-scanner.js') }}" defer></script>
@endpush

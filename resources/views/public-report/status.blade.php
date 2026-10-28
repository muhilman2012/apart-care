@extends('layouts.public-mobile')
@section('title', 'Laporan Diterima — Apart Care')
@section('content')
<div class="mobile-content" style="text-align:center"><div class="success-mark">✓</div><span class="eyebrow">Laporan diterima</span><h1>Terima kasih, {{ $ticket->reporter_name }}.</h1><p class="lead">Tim pengelola akan memeriksa laporan dan mengirim pembaruan melalui kontak Anda.</p><section class="ticket-box" style="text-align:left"><div class="ticket-number">{{ $ticket->ticket_number }}</div><h2 style="font-size:1.15rem;margin:8px 0">{{ $ticket->title }}</h2><p class="lead">{{ $ticket->qrLocation?->name }} · Tower {{ $ticket->qrLocation?->tower_name }}</p><div class="status-line"><strong>Menunggu verifikasi pengelola</strong><div class="help">Target penanganan {{ $ticket->target_sla_at->translatedFormat('d M Y, H.i') }}</div></div></section><p class="privacy">Simpan halaman ini untuk melihat nomor laporan Anda.</p></div>
@endsection

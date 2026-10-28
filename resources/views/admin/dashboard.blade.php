@extends('layouts.app')
@section('title', 'Pusat Kendali — Apart Care')
@section('content')
<header class="page-head"><div><span class="eyebrow">Operasional gedung</span><h1>Pusat kendali</h1></div><div class="date-line">{{ now()->translatedFormat('l, d F Y') }} · Data langsung</div></header>
<section class="stats">
    <div class="stat"><small>Tiket aktif</small><strong>{{ $stats['open'] }}</strong><em>perlu perhatian</em></div>
    <div class="stat"><small>Darurat terbuka</small><strong>{{ $stats['emergency'] }}</strong><em>prioritas utama</em></div>
    <div class="stat"><small>Kepatuhan SLA</small><strong>{{ $stats['sla'] }}%</strong><em>semua tiket</em></div>
    <div class="stat"><small>Selesai</small><strong>{{ $stats['closed'] }}</strong><em>terverifikasi</em></div>
</section>
<div class="grid-main">
    <section class="panel" id="tickets">
        <div class="panel-head"><h2>Tiket terbaru</h2><a class="table-link" href="{{ route('admin.tickets.index') }}">Buka antrean lengkap →</a></div>
        <div class="ticket-list">
            @forelse($tickets->take(5) as $ticket)
                <a class="ticket-row ticket-row-link" href="{{ route('admin.tickets.show',$ticket) }}">
                    <div><div class="ticket-id">{{ $ticket->ticket_number }}</div><div class="badge badge-{{ $ticket->priority_level }}">{{ $ticket->priority_level }}</div></div>
                    <div><div class="ticket-title">{{ $ticket->title }}</div><div class="ticket-meta">{{ $ticket->qrLocation?->name ?? $ticket->facility?->facility_name ?? $ticket->unit?->unit_code }} · {{ $ticket->reporter_display_name }}</div></div>
                    <div><div class="status" data-status="{{ $ticket->status }}">{{ str_replace('_', ' ', ucfirst($ticket->status)) }}</div><div class="ticket-meta">{{ $ticket->workOrder?->technician?->name ?? 'Belum ditugaskan' }}</div></div>
                    <div><strong>{{ $ticket->target_sla_at->isFuture() ? $ticket->target_sla_at->diffForHumans(['parts'=>2]) : 'Terlambat' }}</strong><div class="ticket-meta">Batas {{ $ticket->target_sla_at->format('d M, H.i') }}</div></div>
                </a>
            @empty
                <div class="empty">Belum ada pengaduan. Tiket akan muncul setelah QR dipindai dan formulir dikirim.</div>
            @endforelse
        </div>
    </section>
    <aside>
        <div class="cta-card"><span class="eyebrow" style="color:#ffb28e">Tindakan cepat</span><h2>Disposisi tanpa kertas</h2><p>Buka antrean, periksa lokasi dan bukti foto, lalu pilih teknisi aktif yang sesuai.</p><a class="button button-primary" href="{{ route('admin.tickets.index',['status'=>'submitted']) }}">Buka laporan baru</a></div>
        <section class="panel"><div class="panel-head"><h2>Alur penanganan</h2></div><div class="panel-body"><ol class="timeline"><li><strong>Laporan diterima</strong><div class="ticket-meta">Lokasi QR dan SLA tercatat.</div></li><li><strong>TRO meneruskan</strong><div class="ticket-meta">Teknisi dan catatan kerja dipilih.</div></li><li><strong>Teknisi bekerja</strong><div class="ticket-meta">Foto sebelum dan sesudah wajib.</div></li><li><strong>Verifikasi hasil</strong><div class="ticket-meta">Tiket siap ditutup atau dikerjakan ulang.</div></li></ol></div></section>
    </aside>
</div>
@endsection

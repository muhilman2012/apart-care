@extends('layouts.app')
@section('title', 'Label QR — Apart Care')
@section('content')
<header class="page-head"><div><span class="eyebrow">Identitas lokasi tersimpan</span><h1>Label QR</h1><p class="muted">Setiap kartu memuat lokasi, kode manual, UUID, dan QR yang siap dicetak.</p></div><div class="head-actions"><button class="button" type="button" onclick="window.print()">Cetak semua</button><a class="button button-primary" href="{{ route('admin.qr.create') }}">＋ Tambah label</a></div></header>
@if($locations->isEmpty())
<section class="panel empty-state"><div class="empty-icon">▦</div><h2>Belum ada label QR</h2><p>Tambahkan unit, fasilitas, barang, atau titik lokasi pertama Anda.</p><a class="button button-primary" href="{{ route('admin.qr.create') }}">Tambah label pertama</a></section>
@else
<section class="qr-grid qr-card-grid">
@foreach($locations as $location)
<article class="qr-card {{ $location->is_active ? '' : 'is-inactive' }}">
    <header class="qr-card-head"><div><span class="qr-type">{{ ['facility'=>'Fasilitas','asset'=>'Aset','unit'=>'Unit','location'=>'Lokasi'][$location->type] }}</span><h2>{{ $location->name }}</h2></div><span class="qr-state {{ $location->is_active ? 'is-active' : '' }}">{{ $location->is_active ? 'Aktif' : 'Nonaktif' }}</span></header>
    <div class="qr-card-location"><strong>{{ $location->tower_name }}{{ $location->floor_number !== null ? ' · Lantai '.$location->floor_number : '' }}</strong><span>{{ $location->zone ?: 'Zona belum ditentukan' }}</span></div>
    <div class="qr-visuals">@if($location->reference_image_url)<figure><img src="{{ $location->reference_image_url }}" alt="Referensi {{ $location->name }}"><figcaption>Referensi lokasi</figcaption></figure>@endif<figure class="qr-figure"><img src="{{ route('admin.qr.svg', $location) }}" alt="QR {{ $location->name }}"><figcaption>Pindai untuk melapor</figcaption></figure></div>
    <div class="manual-code"><span>Kode manual</span><strong>{{ $location->code }}</strong></div>
    <details class="uuid-detail"><summary>Lihat UUID</summary><code>{{ $location->uuid }}</code></details>
    <footer class="qr-card-actions"><a class="button button-small" href="{{ route('admin.qr.edit', $location) }}">Edit</a><a class="button button-small button-light" target="_blank" href="{{ route('complaint.create', $location) }}">Uji tautan</a><form method="post" action="{{ route('admin.qr.toggle', $location) }}">@csrf @method('PATCH')<button class="text-action" type="submit">{{ $location->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form></footer>
</article>
@endforeach
</section>
@endif
@endsection

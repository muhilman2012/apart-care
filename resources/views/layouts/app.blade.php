<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title', 'Apart Care')</title>
    @include('layouts.brand-meta')
    <link rel="stylesheet" href="{{ asset('css/apart-care.css') }}?v={{ filemtime(public_path('css/apart-care.css')) }}">
</head>
<body>
<header class="app-header">
    <a class="wordmark" href="{{ route('admin.dashboard') }}" aria-label="APART-CARE Dashboard"><img src="{{ asset('images/brand/apartcare-logo-horizontal.svg') }}" alt="APART-CARE"></a>
    <nav class="nav" aria-label="Navigasi utama">
        <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
        <a class="{{ request()->routeIs('admin.tickets.*') ? 'active' : '' }}" href="{{ route('admin.tickets.index') }}">{{ auth()->user()->role === 'technician' ? 'Tugas saya' : 'Tiket' }}</a>
        <a class="{{ request()->routeIs('admin.qr.*') ? 'active' : '' }}" href="{{ route('admin.qr.index') }}">Fasilitas & QR</a>
        @if(auth()->user()->role === 'manager')<a class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">Tim</a>@endif
        @if(in_array(auth()->user()->role, ['manager','tro']))<a class="{{ request()->routeIs('admin.sla.*') ? 'active' : '' }}" href="{{ route('admin.sla.index') }}">Laporan SLA</a>@endif
        @if(in_array(auth()->user()->role, ['manager','tro']))<a class="{{ request()->routeIs('admin.whatsapp') ? 'active' : '' }}" href="{{ route('admin.whatsapp') }}">WhatsApp</a>@endif
    </nav>
    <div class="account">
        <div class="notification-center" data-index-url="{{ route('admin.notifications.index') }}">
            <button class="notification-button" type="button" aria-label="Buka notifikasi" aria-expanded="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg><span class="notification-count" hidden>0</span></button>
            <div class="notification-panel" hidden><div class="notification-panel-head"><strong>Notifikasi</strong><small>Pembaruan tiket terbaru</small></div><div class="notification-list"><div class="notification-empty">Tidak ada notifikasi baru.</div></div></div>
        </div>
        <span>{{ auth()->user()->name }} · {{ strtoupper(auth()->user()->role) }}</span><form method="post" action="{{ route('logout') }}">@csrf<button class="logout">Keluar</button></form>
    </div>
</header>
<main class="content">@if(session('success'))<div class="flash">{{ session('success') }}</div>@endif @yield('content')</main>
<script src="{{ asset('js/admin-live.js') }}?v={{ filemtime(public_path('js/admin-live.js')) }}" defer></script>
</body></html>

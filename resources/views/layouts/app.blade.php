<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title', 'Apart Care')</title>
    <link rel="stylesheet" href="{{ asset('css/apart-care.css') }}?v={{ filemtime(public_path('css/apart-care.css')) }}">
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="brand"><span class="brand-mark">A</span><span>apart care</span></div>
        <nav class="nav">
            <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Pusat kendali</a>
            <a class="{{ request()->routeIs('admin.tickets.*') ? 'active' : '' }}" href="{{ route('admin.tickets.index') }}">{{ auth()->user()->role === 'technician' ? 'Tugas saya' : 'Antrean tiket' }}</a><a class="{{ request()->routeIs('admin.qr.*') ? 'active' : '' }}" href="{{ route('admin.qr.index') }}">Label QR</a>@if(auth()->user()->role === 'manager')<a class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">Tim & pengguna</a>@endif @if(in_array(auth()->user()->role, ['manager','tro']))<a class="{{ request()->routeIs('admin.sla.*') ? 'active' : '' }}" href="{{ route('admin.sla.index') }}">Laporan SLA</a>@endif
        </nav>
        <div class="sidebar-foot"><div class="user-row"><strong>{{ auth()->user()->name }}</strong>{{ ucfirst(auth()->user()->role) }}</div><form method="post" action="{{ route('logout') }}">@csrf<button class="logout">Keluar dari sistem</button></form></div>
    </aside>
    <main class="content">@if(session('success'))<div class="flash">{{ session('success') }}</div>@endif @yield('content')</main>
</div>
</body></html>

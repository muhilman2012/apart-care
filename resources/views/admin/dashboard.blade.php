@extends('layouts.app')
@section('title', 'Dashboard — Apart Care')
@section('content')
<header class="page-head"><div><span class="eyebrow">Operasional gedung</span><h1>Dashboard</h1></div><div class="date-line">{{ now()->wib()->translatedFormat('l, d F Y') }} · <span id="dashboard-updated">Data langsung · WIB</span></div></header>
<div id="dashboard-live" data-feed-url="{{ route('admin.dashboard.feed') }}">
    @include('admin.partials.dashboard-data')
</div>
@endsection

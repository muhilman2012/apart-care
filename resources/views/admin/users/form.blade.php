@extends('layouts.app')
@section('title', ($managedUser->exists ? 'Edit' : 'Tambah').' Pengguna — Apart Care')
@section('content')
@php
    $roles = [
        'tro' => 'TRO / Customer Service',
        'technician' => 'Teknisi',
        'manager' => 'Building Manager',
        'resident' => 'Penghuni',
    ];
    $isSelf = $managedUser->exists && auth()->user()->is($managedUser);
@endphp
<div class="user-form-page">
    <a class="back-link user-back-link" href="{{ route('admin.users.index') }}">← Kembali ke daftar tim</a>

    <header class="page-head user-form-heading">
        <div>
            <span class="eyebrow">Akun operasional</span>
            <h1>{{ $managedUser->exists ? 'Edit pengguna' : 'Tambah pengguna' }}</h1>
            <p class="muted">Atur identitas, peran, dan akses masuk pengguna.</p>
        </div>
        @if($managedUser->exists)
            <div class="user-identity-chip" aria-label="Ringkasan pengguna">
                <span>{{ strtoupper(mb_substr($managedUser->name, 0, 1)) }}</span>
                <div><strong>{{ $managedUser->name }}</strong><small>{{ $roles[$managedUser->role] ?? $managedUser->role }}</small></div>
            </div>
        @endif
    </header>

    <form class="admin-form user-edit-form" method="post" action="{{ $managedUser->exists ? route('admin.users.update', $managedUser) : route('admin.users.store') }}">
        @csrf
        @if($managedUser->exists) @method('PUT') @endif

        <section class="user-form-card">
            <header class="user-form-card-head">
                <span>01</span>
                <div><h2>Informasi akun</h2><p>Data yang digunakan untuk mengenali dan menghubungi pengguna.</p></div>
            </header>
            <div class="user-form-card-body">
                <div class="form-grid">
                    <div class="field">
                        <label for="name">Nama lengkap</label>
                        <input id="name" name="name" value="{{ old('name', $managedUser->name) }}" autocomplete="name" required>
                        @error('name')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="field">
                        <label for="role">Peran</label>
                        <select id="role" name="role" required @disabled($isSelf)>
                            @foreach($roles as $value => $label)
                                <option value="{{ $value }}" @selected(old('role', $managedUser->role) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @if($isSelf)
                            <input type="hidden" name="role" value="manager">
                            <p class="form-hint">Peran akun yang sedang digunakan tidak dapat diturunkan.</p>
                        @endif
                        @error('role')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $managedUser->email) }}" autocomplete="email" required>
                        @error('email')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="field">
                        <label for="phone_number">Nomor telepon</label>
                        <input id="phone_number" name="phone_number" type="tel" value="{{ old('phone_number', $managedUser->phone_number) }}" autocomplete="tel" placeholder="Contoh: 081234567890">
                        @error('phone_number')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </section>

        <section class="user-form-card">
            <header class="user-form-card-head">
                <span>02</span>
                <div><h2>Keamanan akun</h2><p>{{ $managedUser->exists ? 'Kata sandi lama tetap berlaku jika opsi ini tidak diaktifkan.' : 'Buat kata sandi awal untuk pengguna.' }}</p></div>
            </header>
            <div class="user-form-card-body password-card-body">
                @if($managedUser->exists)
                    <input type="hidden" name="change_password" value="0">
                    <input class="password-toggle-control" id="change_password" name="change_password" type="checkbox" value="1" @checked(old('change_password'))>
                    <label class="password-toggle" for="change_password">
                        <span><strong>Ubah kata sandi</strong><small>Aktifkan untuk mengganti kata sandi pengguna ini.</small></span>
                        <i aria-hidden="true"></i>
                    </label>
                @endif

                <div class="form-grid password-fields">
                    <div class="field">
                        <label for="password">{{ $managedUser->exists ? 'Kata sandi baru' : 'Kata sandi' }}</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" @required(!$managedUser->exists)>
                        <p class="form-hint">Minimal 12 karakter serta mengandung huruf dan angka.</p>
                        @error('password')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="field">
                        <label for="password_confirmation">Ulangi kata sandi</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" @required(!$managedUser->exists)>
                    </div>
                </div>
            </div>
        </section>

        <section class="user-form-card access-card">
            <header class="user-form-card-head">
                <span>03</span>
                <div><h2>Status akses</h2><p>Nonaktifkan akun untuk menghentikan akses tanpa menghapus riwayat pekerjaan.</p></div>
            </header>
            <div class="user-form-card-body">
                <input type="hidden" name="is_active" value="0">
                <label class="access-toggle {{ $isSelf ? 'is-locked' : '' }}">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $managedUser->exists ? $managedUser->is_active : true)) @disabled($isSelf)>
                    @if($isSelf)<input type="hidden" name="is_active" value="1">@endif
                    <span><strong>Akun aktif</strong><small>Pengguna dapat masuk dan menggunakan menu sesuai perannya.</small></span>
                </label>
                @if($isSelf)<p class="form-hint">Akun yang sedang digunakan tidak dapat dinonaktifkan.</p>@endif
            </div>
        </section>

        <div class="user-form-actions">
            <a class="button" href="{{ route('admin.users.index') }}">Batal</a>
            <button class="button button-primary" type="submit">{{ $managedUser->exists ? 'Simpan perubahan' : 'Tambah pengguna' }}</button>
        </div>
    </form>
</div>
@endsection

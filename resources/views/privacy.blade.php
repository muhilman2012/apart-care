@extends('layouts.public-mobile')

@section('title', 'Kebijakan Privasi — Apart Care')

@section('content')
    <header class="screen-header">
        <a href="{{ route('landing') }}" aria-label="Ke beranda">‹</a>
        <strong>APART-CARE</strong>
    </header>

    <article class="mobile-content privacy-policy">
        <h1>Kebijakan Privasi</h1>
        <p>APART-CARE dikelola oleh tim APART-CARE, Kelompok C Capstone Project STSI4440. Pemberitahuan ini menjelaskan cara kami menangani data yang Anda berikan saat membuat dan memantau aduan fasilitas.</p>

        <h2>Data dan tujuan penggunaan</h2>
        <p>Kami memproses nama, nomor WhatsApp, lokasi fasilitas, dan isi aduan untuk mencatat, menugaskan, serta menindaklanjuti laporan. Foto yang Anda tambahkan, alasan perbaikan ulang, dan penilaian layanan digunakan untuk memeriksa dan mendokumentasikan pekerjaan. Hindari menyertakan data pribadi orang lain dalam keterangan atau foto jika tidak diperlukan.</p>
        <p>Nomor WhatsApp digunakan untuk komunikasi terkait tiket. Jika notifikasi aktif, layanan WhatsApp memproses nomor dan informasi terkait tiket untuk mengirim tautan status serta pembaruan. Tautan status juga tersedia langsung setelah laporan dikirim.</p>

        <h2>Akses dan penyimpanan</h2>
        <p>Petugas dan teknisi yang berwenang dapat mengakses data terkait penanganan aduan. Siapa pun yang memegang tautan status dapat melihat informasi yang tersedia pada tautan tersebut, jadi simpan dan bagikan tautan hanya kepada pihak yang berkepentingan.</p>
        <p>Data dapat tersimpan pada tiket dan catatan pengiriman notifikasi untuk tindak lanjut serta penelusuran penanganan. Penghapusan ditangani oleh pengelola berdasarkan kebutuhan penanganan, permintaan Anda, dan ketentuan yang berlaku.</p>
        <p>Aplikasi menggunakan kuki sesi agar formulir dapat dikirim dan pengguna portal dapat tetap masuk.</p>

        <h2>Permintaan terkait data pribadi</h2>
        <p>Anda dapat menghubungi pengelola untuk meminta informasi atau akses, koreksi, pembatasan penggunaan, atau penghapusan data sesuai ketentuan yang berlaku.</p>
        @if (config('privacy.contact_email'))
            <p>Kirim permintaan ke <a href="mailto:{{ config('privacy.contact_email') }}">{{ config('privacy.contact_email') }}</a>.</p>
        @else
            <p>Hubungi petugas pengelola yang menyediakan label QR fasilitas atau balas pesan WhatsApp pembaruan tiket jika Anda menerimanya.</p>
        @endif
        <p>Sertakan nomor tiket jika ada. Pengelola dapat meminta verifikasi sebelum menindaklanjuti permintaan.</p>
    </article>
@endsection

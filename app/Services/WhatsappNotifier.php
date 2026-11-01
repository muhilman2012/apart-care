<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\Http;

class WhatsappNotifier
{
    public function send(Ticket $ticket, string $event): array
    {
        if (! config('services.whatsapp.enabled')) {
            return ['sent' => false, 'skipped' => true, 'error' => 'Integrasi WhatsApp dinonaktifkan.'];
        }

        $response = Http::asJson()
            ->withHeader('X-Api-Token', config('services.whatsapp.token'))
            ->timeout(30)
            ->post(rtrim(config('services.whatsapp.url'), '/').'/send', [
                'phone' => $ticket->reporter_phone,
                'message' => $this->message($ticket, $event),
            ]);

        if (! $response->successful()) {
            return ['sent' => false, 'error' => $response->json('error') ?: 'Engine WhatsApp tidak tersedia.'];
        }

        return $response->json();
    }

    public function connection(): array
    {
        try {
            return Http::withHeader('X-Api-Token', config('services.whatsapp.token'))
                ->timeout(5)
                ->get(rtrim(config('services.whatsapp.url'), '/').'/health')
                ->throw()
                ->json();
        } catch (\Throwable $exception) {
            return ['state' => 'offline', 'ready' => false, 'last_error' => $exception->getMessage()];
        }
    }

    public function qr(): ?string
    {
        try {
            return Http::withHeader('X-Api-Token', config('services.whatsapp.token'))
                ->timeout(5)
                ->get(rtrim(config('services.whatsapp.url'), '/').'/qr')
                ->json('qr_data_url');
        } catch (\Throwable) {
            return null;
        }
    }

    private function message(Ticket $ticket, string $event): string
    {
        $ticket->loadMissing(['qrLocation', 'workOrder.technician']);
        $update = [
            'created' => ['Laporan berhasil diterima', 'Petugas akan memeriksa laporan Anda dan menugaskan teknisi yang sesuai.'],
            'dispatched' => ['Teknisi sudah ditugaskan', 'Laporan Anda telah diteruskan kepada '.($ticket->workOrder?->technician?->name ?? 'teknisi').'.'],
            'started' => ['Perbaikan sedang dikerjakan', 'Teknisi sudah mulai menangani fasilitas yang Anda laporkan.'],
            'completed' => ['Perbaikan sudah selesai', 'Silakan periksa hasil pekerjaan dan beri konfirmasi melalui tautan status.'],
            'rework' => ['Perbaikan ulang sedang diproses', 'Permintaan Anda telah diteruskan kembali kepada teknisi.'],
            'closed' => ['Laporan sudah selesai', 'Hasil perbaikan telah disetujui. Terima kasih atas laporan Anda.'],
        ][$event] ?? ['Ada pembaruan laporan', 'Silakan lihat perkembangan terbaru melalui tautan status.'];

        $status = [
            'submitted' => 'Menunggu pemeriksaan petugas',
            'verified' => 'Teknisi sudah ditugaskan',
            'in_progress' => 'Sedang dikerjakan teknisi',
            'pending_verification' => 'Menunggu konfirmasi Anda',
            'rework' => 'Sedang diperbaiki ulang',
            'closed' => 'Selesai',
        ][$ticket->status] ?? 'Sedang diproses';

        return "*APART-CARE*\n\n*{$update[0]}*\n{$update[1]}\n\n*Nomor tiket:* {$ticket->ticket_number}\n*Masalah:* {$ticket->title}\n*Lokasi:* ".($ticket->qrLocation?->location_label ?? 'Apartemen')."\n*Status saat ini:* {$status}\n\n*Lihat status laporan:*\n".route('complaint.status', $ticket->public_token)."\n\nBalas pesan ini jika ingin menghubungi petugas. Balasan ditangani secara manual.";
    }
}

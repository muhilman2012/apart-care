<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketActivityNotification;
use Illuminate\Support\Facades\Notification;

class TicketInternalNotifier
{
    public function send(Ticket $ticket, string $event, ?User $actor = null): void
    {
        $ticket->loadMissing('workOrder.technician');
        [$title, $message] = $this->content($ticket, $event);

        $recipients = match ($event) {
            'created', 'started', 'completed' => User::query()
                ->whereIn('role', ['manager', 'tro'])
                ->where('is_active', true)
                ->get(),
            'dispatched', 'rework' => User::query()
                ->whereKey($ticket->workOrder?->technician_user_id)
                ->where('is_active', true)
                ->get(),
            'closed' => User::query()
                ->where('is_active', true)
                ->where(function ($query) use ($ticket): void {
                    $query->whereIn('role', ['manager', 'tro'])
                        ->orWhere('id', $ticket->workOrder?->technician_user_id);
                })
                ->get(),
            default => collect(),
        };

        $recipients = $recipients
            ->when($actor, fn ($users) => $users->reject(fn (User $user) => $user->is($actor)))
            ->unique('id')
            ->values();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new TicketActivityNotification($ticket, $title, $message));
        }
    }

    public function reassign(Ticket $ticket, User $previous, User $replacement, User $actor): void
    {
        if (! $previous->is($actor)) {
            $previous->notify(new TicketActivityNotification(
                $ticket,
                'Penugasan dialihkan',
                "{$ticket->ticket_number} telah dialihkan kepada teknisi lain.",
            ));
        }

        if (! $replacement->is($actor)) {
            $replacement->notify(new TicketActivityNotification(
                $ticket,
                'Tugas pengganti untuk Anda',
                "{$ticket->ticket_number} dialihkan kepada Anda untuk dilanjutkan.",
            ));
        }
    }

    private function content(Ticket $ticket, string $event): array
    {
        return match ($event) {
            'created' => ['Laporan baru masuk', "{$ticket->ticket_number} perlu diperiksa dan ditugaskan."],
            'dispatched' => ['Tugas baru untuk Anda', "{$ticket->ticket_number} telah dialihkan untuk ditangani."],
            'started' => ['Pengerjaan dimulai', "Teknisi mulai mengerjakan {$ticket->ticket_number}."],
            'completed' => ['Hasil kerja menunggu verifikasi', "{$ticket->ticket_number} sudah diselesaikan teknisi dan perlu diverifikasi."],
            'rework' => ['Perbaikan ulang diminta', "{$ticket->ticket_number} dikembalikan untuk dikerjakan ulang."],
            'closed' => ['Tiket sudah ditutup', "{$ticket->ticket_number} telah disetujui dan dinyatakan selesai."],
            default => ['Pembaruan tiket', "Ada pembaruan pada {$ticket->ticket_number}."],
        };
    }
}

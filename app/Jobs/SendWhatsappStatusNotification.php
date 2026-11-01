<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Models\WhatsappMessageLog;
use App\Services\WhatsappNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendWhatsappStatusNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $ticketId, public string $event) {}

    public function handle(WhatsappNotifier $notifier): void
    {
        $ticket = Ticket::find($this->ticketId);
        if (! $ticket || ! $ticket->reporter_phone) {
            return;
        }

        $log = WhatsappMessageLog::create([
            'ticket_id' => $ticket->id,
            'event' => $this->event,
            'phone' => $ticket->reporter_phone,
            'status' => 'queued',
        ]);

        try {
            $result = $notifier->send($ticket, $this->event);
            $log->update([
                'status' => ($result['sent'] ?? false) ? 'sent' : (($result['skipped'] ?? false) ? 'skipped' : 'failed'),
                'external_message_id' => $result['message_id'] ?? null,
                'error' => $result['error'] ?? null,
                'sent_at' => ($result['sent'] ?? false) ? now() : null,
            ]);
        } catch (Throwable $exception) {
            $log->update(['status' => 'failed', 'error' => $exception->getMessage()]);
            throw $exception;
        }
    }
}

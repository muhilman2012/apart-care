<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SecureMediaController extends Controller
{
    public function showPublic(Ticket $ticket, TicketAttachment $attachment): BinaryFileResponse
    {
        abort_unless($attachment->ticket_id === $ticket->id, 404);
        abort_unless(in_array($attachment->kind, ['before', 'after'], true), 404);

        return $this->file($attachment);
    }

    public function showAdmin(Request $request, TicketAttachment $attachment): BinaryFileResponse
    {
        if ($request->user()->role === 'technician') {
            abort_unless(
                $attachment->ticket->workOrder?->technician_user_id === $request->user()->id,
                403,
            );
        }

        return $this->file($attachment);
    }

    private function file(TicketAttachment $attachment): BinaryFileResponse
    {
        $disk = Storage::disk('local')->exists($attachment->file_path) ? 'local' : 'public';
        abort_unless(Storage::disk($disk)->exists($attachment->file_path), 404);

        return response()->file(Storage::disk($disk)->path($attachment->file_path), [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
            'Content-Disposition' => 'inline; filename="evidence-'.(int) $attachment->id.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

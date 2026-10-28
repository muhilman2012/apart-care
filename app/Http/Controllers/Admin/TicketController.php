<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $query = Ticket::query()
            ->with(['qrLocation', 'reporter', 'workOrder.technician'])
            ->latest();

        if ($request->user()->role === 'technician') {
            $query->whereHas('workOrder', fn ($workOrder) => $workOrder->where('technician_user_id', $request->user()->id));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('priority')) {
            $query->where('priority_level', $request->string('priority'));
        }

        return view('admin.tickets.index', [
            'tickets' => $query->paginate(20)->withQueryString(),
            'counts' => [
                'new' => Ticket::where('status', 'submitted')->count(),
                'assigned' => Ticket::whereIn('status', ['verified', 'in_progress'])->count(),
                'verification' => Ticket::where('status', 'pending_verification')->count(),
                'overdue' => Ticket::where('status', '!=', 'closed')->where('target_sla_at', '<', now())->count(),
            ],
        ]);
    }

    public function show(Request $request, Ticket $ticket): View
    {
        $ticket->load([
            'qrLocation',
            'reporter',
            'attachments',
            'statusLogs' => fn ($query) => $query->with('changedBy')->latest('changed_at'),
            'workOrder.technician',
            'workOrder.tro',
        ]);

        if ($request->user()->role === 'technician') {
            abort_unless($ticket->workOrder?->technician_user_id === $request->user()->id, 403);
        }

        return view('admin.tickets.show', [
            'ticket' => $ticket,
            'technicians' => User::where('role', 'technician')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function dispatch(Request $request, Ticket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'technician_user_id' => ['required', 'exists:users,id'],
            'dispatch_notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $technician = User::whereKey($data['technician_user_id'])
            ->where('role', 'technician')
            ->where('is_active', true)
            ->firstOrFail();

        $fromStatus = $ticket->status;
        $ticket->workOrder()->updateOrCreate(['ticket_id' => $ticket->id], [
            'tro_user_id' => $request->user()->id,
            'technician_user_id' => $technician->id,
            'dispatch_notes' => $data['dispatch_notes'] ?? null,
            'dispatched_at' => now(),
        ]);
        $ticket->update(['status' => 'verified']);
        $ticket->statusLogs()->create([
            'changed_by_user_id' => $request->user()->id,
            'from_status' => $fromStatus,
            'to_status' => 'verified',
            'notes' => 'Work order diteruskan kepada '.$technician->name.'.',
            'changed_at' => now(),
        ]);

        return back()->with('success', 'Aduan berhasil diteruskan kepada '.$technician->name.'.');
    }

    public function start(Request $request, Ticket $ticket): RedirectResponse
    {
        $workOrder = $ticket->workOrder;
        abort_unless($workOrder?->technician_user_id === $request->user()->id, 403);
        $request->validate(['before_photo' => ['required', 'image', 'max:4096']]);

        $path = $request->file('before_photo')->store('work-orders/before', 'public');
        $workOrder->update(['before_photo_path' => $path, 'started_at' => now()]);
        $ticket->attachments()->create([
            'uploaded_by_user_id' => $request->user()->id,
            'kind' => 'before',
            'file_path' => $path,
            'original_name' => $request->file('before_photo')->getClientOriginalName(),
            'mime_type' => $request->file('before_photo')->getMimeType(),
            'file_size' => $request->file('before_photo')->getSize(),
        ]);
        $this->changeStatus($ticket, 'in_progress', $request->user()->id, 'Teknisi mulai mengerjakan dan mengunggah foto kondisi awal.');

        return back()->with('success', 'Pengerjaan dimulai dan waktu mulai telah dicatat.');
    }

    public function complete(Request $request, Ticket $ticket): RedirectResponse
    {
        $workOrder = $ticket->workOrder;
        abort_unless($workOrder?->technician_user_id === $request->user()->id, 403);
        $data = $request->validate([
            'after_photo' => ['required', 'image', 'max:4096'],
            'technician_notes' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        $path = $request->file('after_photo')->store('work-orders/after', 'public');
        $workOrder->update([
            'after_photo_path' => $path,
            'technician_notes' => $data['technician_notes'],
            'completed_at' => now(),
        ]);
        $ticket->attachments()->create([
            'uploaded_by_user_id' => $request->user()->id,
            'kind' => 'after',
            'file_path' => $path,
            'original_name' => $request->file('after_photo')->getClientOriginalName(),
            'mime_type' => $request->file('after_photo')->getMimeType(),
            'file_size' => $request->file('after_photo')->getSize(),
        ]);
        $this->changeStatus($ticket, 'pending_verification', $request->user()->id, 'Pekerjaan selesai dan menunggu verifikasi hasil.');

        return back()->with('success', 'Hasil pekerjaan dikirim untuk verifikasi.');
    }

    public function verify(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->status === 'pending_verification', 422);
        $data = $request->validate([
            'decision' => ['required', 'in:approve,rework'],
            'verification_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['decision'] === 'rework') {
            $ticket->workOrder?->update([
                'started_at' => null,
                'completed_at' => null,
                'after_photo_path' => null,
            ]);
            $this->changeStatus(
                $ticket,
                'rework',
                $request->user()->id,
                $data['verification_notes'] ?: 'Hasil belum disetujui dan dikembalikan kepada teknisi.',
            );

            return back()->with('success', 'Tiket dikembalikan kepada teknisi untuk perbaikan ulang.');
        }

        $this->changeStatus(
            $ticket,
            'closed',
            $request->user()->id,
            $data['verification_notes'] ?: 'Hasil pekerjaan disetujui dan tiket ditutup.',
        );
        $ticket->slaLogs()->create([
            'breach_status' => now()->lte($ticket->target_sla_at) ? 'compliant' : 'breached',
            'resolution_duration_minutes' => (int) $ticket->created_at->diffInMinutes(now()),
        ]);

        return back()->with('success', 'Hasil pekerjaan disetujui dan tiket telah ditutup.');
    }

    private function changeStatus(Ticket $ticket, string $status, int $userId, string $notes): void
    {
        $fromStatus = $ticket->status;
        $ticket->update(['status' => $status]);
        $ticket->statusLogs()->create([
            'changed_by_user_id' => $userId,
            'from_status' => $fromStatus,
            'to_status' => $status,
            'notes' => $notes,
            'changed_at' => now(),
        ]);
    }
}

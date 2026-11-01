<?php

namespace App\Http\Controllers\PublicReport;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsappStatusNotification;
use App\Models\QrLocation;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Services\TicketInternalNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    public function __construct(private readonly TicketInternalNotifier $notifier) {}

    public function index(): View
    {
        return view('public-report.scan');
    }

    public function resolveCode(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'location_code' => ['required', 'string', 'max:50'],
        ]);

        $location = QrLocation::query()
            ->where('is_active', true)
            ->whereRaw('UPPER(code) = ?', [Str::upper(trim($data['location_code']))])
            ->first();

        if (! $location) {
            return back()->withInput()->withErrors([
                'location_code' => 'Kode tidak ditemukan atau label sudah tidak aktif.',
            ]);
        }

        return redirect()->route('complaint.create', $location);
    }

    public function create(QrLocation $qrLocation): View
    {
        abort_unless($qrLocation->is_active, 404);

        return view('public-report.create', ['location' => $qrLocation]);
    }

    public function store(Request $request, QrLocation $qrLocation): RedirectResponse
    {
        abort_unless($qrLocation->is_active, 404);
        $data = $request->validate([
            'reporter_name' => ['required', 'string', 'max:120'],
            'reporter_phone' => ['required', 'string', 'max:30'],
            'reporter_email' => ['nullable', 'email', 'max:255'],
            'title' => ['required', 'string', 'max:140'],
            'issue_description' => ['required', 'string', 'min:10', 'max:3000'],
            'priority_level' => ['required', 'in:emergency,high,regular'],
            'initial_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $fallbackMinutes = ['emergency' => 120, 'high' => 480, 'regular' => 1440][$data['priority_level']];
        $resolutionMinutes = SlaPolicy::query()
            ->where('priority_level', $data['priority_level'])
            ->where('is_active', true)
            ->value('resolution_minutes') ?? $fallbackMinutes;
        $ticket = Ticket::create([
            'reporter_user_id' => null,
            'reporter_name' => $data['reporter_name'],
            'reporter_phone' => $data['reporter_phone'],
            'reporter_email' => $data['reporter_email'] ?? null,
            'qr_location_id' => $qrLocation->id,
            'public_token' => Str::random(64),
            'ticket_number' => 'AC-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
            'ticket_type' => $qrLocation->type,
            'title' => $data['title'],
            'issue_description' => $data['issue_description'],
            'initial_photo_path' => $request->file('initial_photo')?->store('tickets/initial', 'local'),
            'priority_level' => $data['priority_level'],
            'target_sla_at' => now()->addMinutes($resolutionMinutes),
        ]);

        if ($ticket->initial_photo_path) {
            $ticket->attachments()->create([
                'kind' => 'report',
                'file_path' => $ticket->initial_photo_path,
                'original_name' => $request->file('initial_photo')?->getClientOriginalName(),
                'mime_type' => $request->file('initial_photo')?->getMimeType(),
                'file_size' => $request->file('initial_photo')?->getSize(),
            ]);
        }

        $ticket->statusLogs()->create([
            'to_status' => 'submitted',
            'notes' => 'Laporan dibuat dari label QR '.$qrLocation->code.'.',
            'changed_at' => now(),
        ]);
        SendWhatsappStatusNotification::dispatch($ticket->id, 'created')->afterResponse();
        $this->notifier->send($ticket, 'created');

        return redirect()->route('complaint.status', $ticket->public_token)->with('success', 'Pengaduan berhasil dikirim.');
    }

    public function status(Ticket $ticket): View
    {
        $ticket->load(['qrLocation', 'workOrder.technician', 'statusLogs', 'review']);

        return view('public-report.status', compact('ticket'));
    }

    public function review(Ticket $ticket): View
    {
        abort_unless($ticket->status === 'pending_verification', 404);
        $ticket->load(['qrLocation', 'workOrder.technician', 'attachments']);

        return view('public-report.review', compact('ticket'));
    }

    public function storeReview(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->status === 'pending_verification', 422);
        $data = $request->validate([
            'rating_stars' => ['required', 'integer', 'between:1,5'],
            'review_comment' => ['nullable', 'string', 'max:1000'],
        ]);
        $ticket->review()->create([
            'rating_stars' => $data['rating_stars'],
            'review_comment' => $data['review_comment'] ?? null,
            'verified_at' => now(),
        ]);
        $ticket->update(['status' => 'closed']);
        $ticket->statusLogs()->create([
            'from_status' => 'pending_verification',
            'to_status' => 'closed',
            'notes' => 'Hasil perbaikan disetujui oleh pelapor.',
            'changed_at' => now(),
        ]);
        $ticket->slaLogs()->create([
            'breach_status' => now()->lte($ticket->target_sla_at) ? 'compliant' : 'breached',
            'resolution_duration_minutes' => (int) $ticket->created_at->diffInMinutes(now()),
        ]);
        SendWhatsappStatusNotification::dispatch($ticket->id, 'closed')->afterResponse();
        $this->notifier->send($ticket, 'closed');

        return redirect()->route('complaint.status', $ticket->public_token);
    }

    public function rework(Ticket $ticket): View
    {
        abort_unless($ticket->status === 'pending_verification', 404);

        return view('public-report.rework', compact('ticket'));
    }

    public function storeRework(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->status === 'pending_verification', 422);
        $data = $request->validate([
            'reason' => ['required', 'in:Masih rusak,Rusak sebagian,Hasil kurang rapi,Muncul masalah baru'],
            'rework_notes' => ['required', 'string', 'min:10', 'max:2000'],
            'current_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);
        if ($request->hasFile('current_photo')) {
            $path = $request->file('current_photo')->store('tickets/rework', 'local');
            $ticket->attachments()->create([
                'kind' => 'other',
                'file_path' => $path,
                'original_name' => $request->file('current_photo')->getClientOriginalName(),
                'mime_type' => $request->file('current_photo')->getMimeType(),
                'file_size' => $request->file('current_photo')->getSize(),
            ]);
        }
        $ticket->workOrder?->update(['started_at' => null, 'completed_at' => null, 'after_photo_path' => null]);
        $ticket->update(['status' => 'rework']);
        $ticket->statusLogs()->create([
            'from_status' => 'pending_verification',
            'to_status' => 'rework',
            'notes' => $data['reason'].': '.$data['rework_notes'],
            'changed_at' => now(),
        ]);
        SendWhatsappStatusNotification::dispatch($ticket->id, 'rework')->afterResponse();
        $this->notifier->send($ticket, 'rework');

        return redirect()->route('complaint.status', $ticket->public_token)->with('success_rework', 'Permintaan perbaikan ulang berhasil dikirim.');
    }
}

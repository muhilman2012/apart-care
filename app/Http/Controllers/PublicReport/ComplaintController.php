<?php

namespace App\Http\Controllers\PublicReport;

use App\Http\Controllers\Controller;
use App\Models\QrLocation;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ComplaintController extends Controller
{
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
            'initial_photo' => ['nullable', 'image', 'max:4096'],
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
            'initial_photo_path' => $request->file('initial_photo')?->store('tickets/initial', 'public'),
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

        return redirect()->route('complaint.status', $ticket->public_token)->with('success', 'Pengaduan berhasil dikirim.');
    }

    public function status(Ticket $ticket): View
    {
        return view('public-report.status', compact('ticket'));
    }
}

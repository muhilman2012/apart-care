<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('admin.dashboard', $this->data($request));
    }

    public function feed(Request $request): JsonResponse
    {
        return response()->json([
            'html' => view('admin.partials.dashboard-data', $this->data($request))->render(),
            'updated_at' => now()->wib()->format('H.i.s').' WIB',
        ]);
    }

    private function data(Request $request): array
    {
        $base = Ticket::query();
        if ($request->user()->role === 'technician') {
            $base->whereHas('workOrder', fn ($workOrder) => $workOrder->where('technician_user_id', $request->user()->id));
        }

        $allTickets = (clone $base)->get();
        $activeTickets = (clone $base)
            ->with(['reporter', 'qrLocation', 'unit', 'facility', 'workOrder.technician'])
            ->where('status', '!=', 'closed')
            ->latest()
            ->get();
        $total = max($allTickets->count(), 1);
        $onTime = $allTickets->filter(fn ($ticket) => $ticket->status === 'closed' || $ticket->target_sla_at->isFuture())->count();

        return [
            'tickets' => $activeTickets,
            'stats' => [
                'open' => $activeTickets->count(),
                'emergency' => $activeTickets->where('priority_level', 'emergency')->count(),
                'sla' => (int) round(($onTime / $total) * 100),
                'closed' => $allTickets->where('status', 'closed')->count(),
            ],
        ];
    }
}

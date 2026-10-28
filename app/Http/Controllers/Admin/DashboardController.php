<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = Ticket::with(['reporter', 'qrLocation', 'unit', 'facility', 'workOrder.technician'])->latest();
        if ($request->user()->role === 'technician') {
            $query->whereHas('workOrder', fn ($workOrder) => $workOrder->where('technician_user_id', $request->user()->id));
        }
        $tickets = $query->get();
        $total = max($tickets->count(), 1);
        $closed = $tickets->where('status', 'closed')->count();
        $onTime = $tickets->filter(fn ($ticket) => $ticket->status === 'closed' || $ticket->target_sla_at->isFuture())->count();

        return view('admin.dashboard', [
            'tickets' => $tickets,
            'stats' => [
                'open' => $tickets->whereNotIn('status', ['closed'])->count(),
                'emergency' => $tickets->where('priority_level', 'emergency')->where('status', '!=', 'closed')->count(),
                'sla' => (int) round(($onTime / $total) * 100),
                'closed' => $closed,
            ],
        ]);
    }
}

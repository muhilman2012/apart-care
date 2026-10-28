<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SlaReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $month = $request->string('month')->toString() ?: now()->format('Y-m');
        try {
            $period = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable) {
            $period = now()->startOfMonth();
            $month = $period->format('Y-m');
        }

        $tickets = Ticket::with(['qrLocation', 'workOrder.technician'])
            ->whereBetween('created_at', [$period, $period->copy()->endOfMonth()])
            ->get();
        $completed = $tickets->filter(fn ($ticket) => $ticket->workOrder?->completed_at);
        $onTime = $tickets->filter(fn ($ticket) => $ticket->workOrder?->completed_at
            ? $ticket->workOrder->completed_at->lte($ticket->target_sla_at)
            : $ticket->target_sla_at->isFuture());
        $breached = $tickets->count() - $onTime->count();
        $mttr = $completed->count()
            ? (int) round($completed->avg(fn ($ticket) => $ticket->created_at->diffInMinutes($ticket->workOrder->completed_at)))
            : 0;

        $technicians = User::where('role', 'technician')->orderBy('name')->get()->map(function (User $technician) use ($tickets) {
            $assigned = $tickets->filter(fn ($ticket) => $ticket->workOrder?->technician_user_id === $technician->id);

            return [
                'name' => $technician->name,
                'assigned' => $assigned->count(),
                'completed' => $assigned->filter(fn ($ticket) => $ticket->workOrder?->completed_at)->count(),
                'on_time' => $assigned->filter(fn ($ticket) => $ticket->workOrder?->completed_at?->lte($ticket->target_sla_at))->count(),
            ];
        })->filter(fn ($row) => $row['assigned'] > 0);

        return view('admin.sla-report', [
            'month' => $month,
            'tickets' => $tickets,
            'technicians' => $technicians,
            'stats' => [
                'total' => $tickets->count(),
                'on_time' => $onTime->count(),
                'breached' => $breached,
                'compliance' => $tickets->count() ? (int) round(($onTime->count() / $tickets->count()) * 100) : 0,
                'mttr' => $mttr,
            ],
        ]);
    }
}

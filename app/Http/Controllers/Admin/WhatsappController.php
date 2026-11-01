<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsappMessageLog;
use App\Services\WhatsappNotifier;
use Illuminate\View\View;

class WhatsappController extends Controller
{
    public function __invoke(WhatsappNotifier $notifier): View
    {
        return view('admin.whatsapp', [
            'connection' => $notifier->connection(),
            'qrDataUrl' => $notifier->qr(),
            'logs' => WhatsappMessageLog::with('ticket')->latest()->limit(25)->get(),
        ]);
    }
}

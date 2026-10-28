<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\QrLabelController;
use App\Http\Controllers\Admin\SlaReportController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\PublicReport\ComplaintController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/lapor');
Route::get('/lapor', [ComplaintController::class, 'index'])->name('complaint.scan');
Route::post('/lapor/kode', [ComplaintController::class, 'resolveCode'])->name('complaint.code');
Route::get('/lapor/{qrLocation:uuid}', [ComplaintController::class, 'create'])->name('complaint.create');
Route::post('/lapor/{qrLocation:uuid}', [ComplaintController::class, 'store'])->name('complaint.store');
Route::get('/status/{ticket:public_token}', [ComplaintController::class, 'status'])->name('complaint.status');

Route::get('/admin/login', [AuthenticatedSessionController::class, 'create'])->middleware('guest')->name('login');
Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])->middleware('guest')->name('admin.login.store');
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:tro,technician,manager'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::middleware('role:tro,manager')->group(function () {
        Route::post('/tickets/{ticket}/dispatch', [TicketController::class, 'dispatch'])->name('tickets.dispatch');
        Route::post('/tickets/{ticket}/verify', [TicketController::class, 'verify'])->name('tickets.verify');
        Route::get('/sla-report', SlaReportController::class)->name('sla.index');
    });
    Route::middleware('role:technician')->group(function () {
        Route::post('/tickets/{ticket}/start', [TicketController::class, 'start'])->name('tickets.start');
        Route::post('/tickets/{ticket}/complete', [TicketController::class, 'complete'])->name('tickets.complete');
    });
    Route::get('/qr-labels', [QrLabelController::class, 'index'])->name('qr.index');
    Route::get('/qr-labels/create', [QrLabelController::class, 'create'])->name('qr.create');
    Route::post('/qr-labels', [QrLabelController::class, 'store'])->name('qr.store');
    Route::get('/qr-labels/{qrLocation:uuid}/edit', [QrLabelController::class, 'edit'])->name('qr.edit');
    Route::put('/qr-labels/{qrLocation:uuid}', [QrLabelController::class, 'update'])->name('qr.update');
    Route::patch('/qr-labels/{qrLocation:uuid}/toggle', [QrLabelController::class, 'toggle'])->name('qr.toggle');
    Route::get('/qr/{qrLocation:uuid}.svg', [QrLabelController::class, 'svg'])->name('qr.svg');

    Route::middleware('role:manager')->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
    });
});

<?php

use App\Http\Controllers\ContractController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PaymentTrackerController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VendorController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Contract Manager Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    // Vendors
    Route::get('vendors/list', [VendorController::class, 'list'])->name('vendors.list');
    Route::resource('vendors', VendorController::class);

    // Contracts
    Route::get('contracts/list', [ContractController::class, 'list'])->name('contracts.list');
    Route::resource('contracts', ContractController::class);
    Route::post('contracts/{contract}/approvers', [ContractController::class, 'syncApprovers'])->name('contracts.sync-approvers');

    // Payment Tracker
    Route::get('payment-tracker', [PaymentTrackerController::class, 'index'])->name('payment-tracker.index');
    Route::get('payment-tracker/{ticket}', [PaymentTrackerController::class, 'show'])->name('payment-tracker.show');
    Route::post('payment-tracker/{ticket}/submit', [PaymentTrackerController::class, 'submit'])->name('payment-tracker.submit');
    Route::post('payment-tracker/{ticket}/approve', [PaymentTrackerController::class, 'approve'])->name('payment-tracker.approve');
    Route::post('payment-tracker/{ticket}/reject', [PaymentTrackerController::class, 'reject'])->name('payment-tracker.reject');
    Route::post('payment-tracker/{ticket}/mark-paid', [PaymentTrackerController::class, 'markPaid'])->name('payment-tracker.mark-paid');

    // Tickets
    Route::get('tickets/view', [TicketController::class, 'view'])->name('tickets.view');
    Route::resource('tickets', TicketController::class);

    // Documents
    Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('documents/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::post('tickets/{ticket}/documents', [DocumentController::class, 'uploadMultiple'])->name('documents.upload-multiple');

    // Role Groups (renamed from Roles)
    Route::resource('roles', RoleController::class);

    // Users (role management)
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('users/{user}/roles', [UserController::class, 'updateRoles'])->name('users.update-roles');
});

require __DIR__.'/settings.php';

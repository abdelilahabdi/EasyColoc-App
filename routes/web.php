<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\SettlementController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ColocationController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\AdminController;
use App\Models\Invitation;


//use Illuminate\Support\Facades\Mail;


require __DIR__ . '/auth.php';

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::resource('colocations', ColocationController::class);

    // Category, Expense, Settlement and Payment routes - grouped under colocations/{colocation}
    Route::prefix('colocations/{colocation}')->group(function () {
        Route::resource('categories', CategoryController::class)->only(['store', 'destroy']);
        Route::resource('expenses', ExpenseController::class)->only(['create', 'store', 'destroy']);
        Route::post('settlements', [SettlementController::class, 'store'])->name('settlements.store');
        Route::post('invite', [InvitationController::class, 'invite'])->name('colocations.invite');

        // Payment routes
        Route::get('payments', [PaymentController::class, 'index'])->name('colocations.payments.index');
        Route::get('payments/create', [PaymentController::class, 'create'])->name('colocations.payments.create');
        Route::post('payments', [PaymentController::class, 'store'])->name('colocations.payments.store');
        Route::post('payments/{payment}/mark-as-paid', [PaymentController::class, 'markAsPaid'])->name('colocations.payments.markAsPaid');
    });

    // Settlement confirmation route (outside colocation prefix)
    Route::post('settlements/{settlement}/confirm', [SettlementController::class, 'confirm'])->name('settlements.confirm');

    // Invitation routes
    Route::get('invitations/{invitation}', [InvitationController::class, 'show'])->name('invitations.show');
    Route::post('invitations/{invitation}/accept', [InvitationController::class, 'accept'])->name('invitations.accept');
    Route::post('invitations/{invitation}/decline', [InvitationController::class, 'decline'])->name('invitations.decline');

    // Member management routes
    Route::post('colocations/{colocation}/leave', [ColocationController::class, 'leave'])->name('colocations.leave');
    Route::delete('colocations/{colocation}/members/{memberId}', [ColocationController::class, 'removeMember'])->name('colocations.members.destroy');
    Route::post('colocations/{colocation}/cancel', [ColocationController::class, 'cancel'])->name('colocations.cancel');

    // Admin routes
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::post('/admin/users/{user}/ban', [AdminController::class, 'ban'])->name('admin.users.ban');
    Route::post('/admin/users/{user}/unban', [AdminController::class, 'unban'])->name('admin.users.unban');

    


    
    


// Route::get('/test-mail', function () {
//     try {
//         Mail::raw('Test email wac from EasyColoc', function ($message) {
//             $message->to('anything@test.com')
//                     ->subject('Test Mailtrap');
//         });

//         return 'Email sent successfully!';
//     } catch (\Exception $e) {
//         return $e->getMessage();
//     }
// });
});

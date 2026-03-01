<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\UserController;
use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login'); // redirect to login
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('users', UserController::class)->except(['create', 'edit'])->middleware('role:' . RoleEnum::ADMIN->value); // Only admin can manage users
    Route::resource('leads', \App\Http\Controllers\LeadController::class)->except(['create', 'edit']);
    Route::resource('deals', \App\Http\Controllers\DealController::class)->except(['create', 'edit']);
    Route::resource('clients', ClientController::class)->except(['create', 'edit']);
        Route::middleware('role:' . RoleEnum::ADMIN->value . '|' . RoleEnum::LOAN_OFFICER->value)->prefix('loans')->name('loans.')->group(function () {
        Route::get('/borrower-profile', [LoanController::class, 'borrowerProfile'])->name('borrower-profile');
        Route::put('/borrower-profile/{deal}', [LoanController::class, 'updateBorrowerProfile'])->name('borrower-profile.update');
        Route::get('/detail/{deal}', [LoanController::class, 'loanDetail'])->name('detail');
        Route::get('/detail/by-loan/{loanId}', [LoanController::class, 'loanDetailByLoanId'])->name('detail.by-loan');

        Route::get('/pre-qualification', [LoanController::class, 'preQualification'])->name('pre-qualification');
        Route::put('/pre-qualification/{deal}', [LoanController::class, 'updatePreQualification'])->name('pre-qualification.update');

        Route::get('/bank-submission-tracking', [LoanController::class, 'bankSubmissionTracking'])->name('bank-submission-tracking');
        Route::post('/bank-submission-tracking/{deal}', [LoanController::class, 'storeBankSubmission'])->name('bank-submission-tracking.store');
        Route::put('/bank-submission-tracking/submissions/{submission}', [LoanController::class, 'updateBankSubmission'])->name('bank-submission-tracking.update');

        Route::get('/approval-analysis', [LoanController::class, 'approvalAnalysis'])->name('approval-analysis');
        Route::post('/approval-analysis/{deal}', [LoanController::class, 'storeApprovalAnalysis'])->name('approval-analysis.store');
        Route::put('/approval-analysis/{deal}', [LoanController::class, 'updateApprovalAnalysis'])->name('approval-analysis.update');

        Route::get('/disbursement', [LoanController::class, 'disbursement'])->name('disbursement');
        Route::put('/disbursement/{deal}', [LoanController::class, 'updateDisbursement'])->name('disbursement.update');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

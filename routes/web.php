<?php

use App\Enums\RoleEnum;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CommissionController;
use App\Http\Controllers\DashboardChartController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\UserController;
use App\Services\LoanNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login'); // redirect to login
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard/charts', DashboardChartController::class)->name('dashboard.charts');
    Route::get('/dashboard/pipeline-details', [DashboardChartController::class, 'pipelineDetails'])->name('dashboard.pipeline-details');
    Route::get('/dashboard/sales', [DashboardController::class, 'sales'])
        ->middleware('role:'.RoleEnum::SALESPERSON->value.'|'.RoleEnum::LEADER->value)
        ->name('dashboard.sales');
    Route::get('/dashboard/deals', [DashboardController::class, 'deals'])
        ->middleware('role:'.RoleEnum::ADMIN->value)
        ->name('dashboard.deals');
    Route::get('/dashboard/salespeople', [DashboardController::class, 'salespeople'])
        ->middleware('role:'.RoleEnum::ADMIN->value.'|'.RoleEnum::LEADER->value)
        ->name('dashboard.salespeople');
});

Route::middleware('auth')->group(function () {
    Route::get('/notifications/count', function (Request $request) {
        $user = $request->user();
        abort_if(! $user, 403);

        $roleFingerprint = md5($user->getRoleNames()->sort()->implode('|'));
        $cacheKey = sprintf('notifications_%d_%s', $user->id, $roleFingerprint);

        $badges = Cache::remember($cacheKey, now()->addSeconds(30), function () use ($user) {
            return app(LoanNotificationService::class)->forUser($user);
        });

        $badges['loan_submission'] = (int) ($badges['bank_submission'] ?? 0);
        $badges['legal'] = (int) ($badges['legal_new'] ?? 0);

        return response()->json($badges);
    })->name('notifications.count');

    Route::resource('users', UserController::class)->except(['create', 'edit'])->middleware('role:'.RoleEnum::ADMIN->value); // Only admin can manage users
    Route::resource('leads', LeadController::class)
        ->except(['create', 'edit']);
    Route::resource('deals', DealController::class)
        ->except(['create', 'edit']);
    Route::resource('clients', ClientController::class)
        ->only(['index', 'show'])
        ->parameters(['clients' => 'lead']);

    Route::prefix('commissions')->name('commissions.')->group(function () {
        Route::get('/', [CommissionController::class, 'index'])->name('index');
        Route::put('/{commission}', [CommissionController::class, 'update'])->name('update');
    });

    Route::prefix('legals')->name('legals.')->group(function () {
        Route::get('/', [LegalController::class, 'index'])->name('index');
        Route::put('/{deal}', [LegalController::class, 'update'])->name('update');
    });

    Route::prefix('loans')->name('loans.')->group(function () {
        Route::get('/notifications', [LoanController::class, 'notifications'])->name('notifications');
        Route::get('/borrower-profile', [LoanController::class, 'borrowerProfile'])->name('borrower-profile');
        Route::put('/borrower-profile/{deal}', [LoanController::class, 'updateBorrowerProfile'])->name('borrower-profile.update');
        Route::get('/detail/{deal}', [LoanController::class, 'loanDetail'])->name('detail');
        Route::get('/detail/by-loan/{loanId}', [LoanController::class, 'loanDetailByLoanId'])->name('detail.by-loan');

        Route::get('/pre-qualification', [LoanController::class, 'preQualification'])->name('pre-qualification');
        Route::put('/pre-qualification/{deal}', [LoanController::class, 'updatePreQualification'])->name('pre-qualification.update');

        Route::get('/bank-submission-tracking', [LoanController::class, 'bankSubmissionTracking'])->name('bank-submission-tracking');
        Route::post('/bank-submission-tracking', [LoanController::class, 'storeBankSubmission'])->name('bank-submission-tracking.store');
        Route::put('/bank-submission-tracking/submissions/{submission}', [LoanController::class, 'updateBankSubmission'])->name('bank-submission-tracking.update');

        Route::get('/approval-analysis', [LoanController::class, 'approvalAnalysis'])->name('approval-analysis');
        Route::post('/approval-analysis/{deal}', [LoanController::class, 'storeApprovalAnalysis'])->name('approval-analysis.store');
        Route::put('/approval-analysis/{deal}', [LoanController::class, 'updateApprovalAnalysis'])->name('approval-analysis.update');

        Route::get('/disbursement', [LoanController::class, 'disbursement'])->name('disbursement');
        Route::put('/disbursement/{deal}', [LoanController::class, 'updateDisbursement'])->name('disbursement.update');
    });

});

require __DIR__.'/settings.php';

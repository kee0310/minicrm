<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\RoleEnum;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login'); // redirect to login
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('users', UserController::class)->middleware('role:' . RoleEnum::ADMIN->value); // Only admin can manage users
    Route::resource('leads', \App\Http\Controllers\LeadsController::class);
    Route::resource('deals', \App\Http\Controllers\DealController::class);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

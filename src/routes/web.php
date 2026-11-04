<?php

use App\Http\Controllers\LureController;
use App\Http\Controllers\MyPageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShippingAddressController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ログイン後のリダイレクト先（Breezeのデフォルト）をマイページとして使う
Route::get('/dashboard', [MyPageController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    // プロフィール設定
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 配送先住所管理
    Route::resource('shipping-addresses', ShippingAddressController::class)
        ->except(['show']);

    // ルアー出品・管理
    Route::resource('lures', LureController::class);
    Route::patch('lures/{lure}/suspend', [LureController::class, 'suspend'])->name('lures.suspend');
    Route::patch('lures/{lure}/resume', [LureController::class, 'resume'])->name('lures.resume');
    Route::patch('lures/{lure}/mark-down', [LureController::class, 'markDown'])->name('lures.mark-down');
});

require __DIR__.'/auth.php';

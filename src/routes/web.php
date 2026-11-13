<?php

use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\LureController;
use App\Http\Controllers\MyPageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ShippingAddressController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WithdrawalController;
use App\Models\Favorite;
use App\Models\Lure;
use Illuminate\Support\Facades\Route;

// ----- 公開ルート（ゲストOK） -----

// トップページ（新着商品一覧）
Route::get('/', function () {
    $newArrivals = Lure::active()
        ->with(['images' => fn ($q) => $q->where('display_order', 0), 'brand'])
        ->withCount('favorites')
        ->latest()
        ->limit(12)
        ->get();

    $userFavoriteIds = auth()->check()
        ? Favorite::where('user_id', auth()->id())->pluck('lure_id')->toArray()
        : [];

    return view('welcome', compact('newArrivals', 'userFavoriteIds'));
})->name('home');

// 商品一覧・検索
Route::get('/search', [SearchController::class, 'index'])->name('search.index');

// ログイン後のリダイレクト先（マイページ）
Route::get('/dashboard', [MyPageController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// ----- 認証必須ルート -----
// ※ ルート登録順が重要: /lures/create など固定パスを先に登録し、
//   その後 /lures/{lure}（動的パス）を登録することで意図しないマッチを防ぐ
Route::middleware('auth')->group(function () {
    // プロフィール設定
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 配送先住所管理
    Route::resource('shipping-addresses', ShippingAddressController::class)
        ->except(['show']);

    // ルアー出品・管理（show のみ下で公開ルートとして登録）
    Route::post('/lures/images/temp', [LureController::class, 'storeTempImage'])->name('lures.images.temp');
    Route::post('/lures/videos/temp', [LureController::class, 'storeTempVideo'])->name('lures.videos.temp');
    Route::resource('lures', LureController::class)->except(['show']);
    Route::patch('lures/{lure}/suspend', [LureController::class, 'suspend'])->name('lures.suspend');
    Route::patch('lures/{lure}/resume', [LureController::class, 'resume'])->name('lures.resume');
    Route::patch('lures/{lure}/mark-down', [LureController::class, 'markDown'])->name('lures.mark-down');

    // いいね（お気に入り）
    Route::post('/lures/{lure}/favorite', [FavoriteController::class, 'toggle'])->name('lures.favorite.toggle');
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');

    // 購入フロー
    Route::get('/purchase', [TransactionController::class, 'create'])->name('transactions.create');
    // throttle:5,60 = 60分間に最大5回まで（クレジットマスター攻撃対策）
    Route::post('/transactions', [TransactionController::class, 'store'])->middleware('throttle:5,60')->name('transactions.store');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
    Route::patch('/transactions/{transaction}/status', [TransactionController::class, 'updateStatus'])->name('transactions.updateStatus');
    // PAY.JP Checkout 完了後のコールバック
    Route::get('/transactions/{transaction}/return', [TransactionController::class, 'returnCallback'])->name('transactions.return');
    // PAY.JP Checkout キャンセル後のコールバック（cancel_url）
    Route::get('/transactions/{transaction}/cancel', [TransactionController::class, 'cancelCallback'])->name('transactions.cancel');

    // 振込先口座管理
    Route::resource('bank-accounts', BankAccountController::class)->except(['show']);

    // 振込申請
    Route::get('/withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::get('/withdrawals/create', [WithdrawalController::class, 'create'])->name('withdrawals.create');
    Route::post('/withdrawals', [WithdrawalController::class, 'store'])->name('withdrawals.store');
});

// ----- 認証不要の動的ルート（固定パスより後に登録する必要がある） -----
// /lures/create など lures リソースの固定パスが先に登録済みなので
// /lures/{lure} はそれらとの衝突なくゲストに公開できる
Route::get('/lures/{lure}', [LureController::class, 'show'])->name('lures.show');

require __DIR__.'/auth.php';

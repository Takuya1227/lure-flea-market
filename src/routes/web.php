<?php

use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\LureController;
use App\Http\Controllers\MyPageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ShippingAddressController;
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
    Route::resource('lures', LureController::class)->except(['show']);
    Route::patch('lures/{lure}/suspend', [LureController::class, 'suspend'])->name('lures.suspend');
    Route::patch('lures/{lure}/resume', [LureController::class, 'resume'])->name('lures.resume');
    Route::patch('lures/{lure}/mark-down', [LureController::class, 'markDown'])->name('lures.mark-down');

    // いいね（お気に入り）
    Route::post('/lures/{lure}/favorite', [FavoriteController::class, 'toggle'])->name('lures.favorite.toggle');
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
});

// ----- 認証不要の動的ルート（固定パスより後に登録する必要がある） -----
// /lures/create など lures リソースの固定パスが先に登録済みなので
// /lures/{lure} はそれらとの衝突なくゲストに公開できる
Route::get('/lures/{lure}', [LureController::class, 'show'])->name('lures.show');

require __DIR__.'/auth.php';

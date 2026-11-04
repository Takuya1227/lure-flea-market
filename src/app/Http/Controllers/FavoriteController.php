<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Lure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    // いいねのトグル（Ajax レスポンス）
    public function toggle(Request $request, Lure $lure): JsonResponse
    {
        // 出品中以外の商品にはいいねできない
        abort_if($lure->status !== 'active', 404);

        $userId = $request->user()->id;
        $existing = Favorite::where('user_id', $userId)->where('lure_id', $lure->id)->first();

        if ($existing) {
            $existing->delete();
            $favorited = false;
        } else {
            Favorite::create(['user_id' => $userId, 'lure_id' => $lure->id]);
            $favorited = true;
        }

        $count = Favorite::where('lure_id', $lure->id)->count();

        return response()->json(['favorited' => $favorited, 'count' => $count]);
    }

    // いいね一覧
    public function index(Request $request): View
    {
        $favoriteIds = Favorite::where('user_id', $request->user()->id)
            ->pluck('lure_id');

        $lures = Lure::whereIn('id', $favoriteIds)
            ->whereNotIn('status', ['deleted'])
            ->with(['images' => fn ($q) => $q->where('display_order', 0), 'brand'])
            ->withCount('favorites')
            ->latest()
            ->paginate(24);

        // いいね済み ID（カード内のハート状態の初期値に使う）
        $userFavoriteIds = $favoriteIds->toArray();

        return view('favorites.index', compact('lures', 'userFavoriteIds'));
    }
}

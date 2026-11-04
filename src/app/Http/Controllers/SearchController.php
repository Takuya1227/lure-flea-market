<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Favorite;
use App\Models\Lure;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        // ホワイトリスト方式で入力を sanitize（Eloquent の parameterized query で SQL インジェクションは防止済み）
        $q         = trim($request->string('q')->value());
        $category  = in_array($request->input('category'), ['bass', 'trout', 'salt'])
                         ? $request->input('category') : null;
        $conditions = array_values(array_intersect(
                         (array) $request->input('condition', []),
                         ['new_unopened', 'new_opened', 'like_new', 'good', 'fair', 'poor']
                     ));
        $brandIds   = array_values(array_filter(
                         array_map('intval', (array) $request->input('brand_id', []))
                     ));
        $priceMin   = $request->filled('price_min') ? (int) $request->input('price_min') : null;
        $priceMax   = $request->filled('price_max') ? (int) $request->input('price_max') : null;
        $sizeMin    = $request->filled('size_min')  ? (float) $request->input('size_min')  : null;
        $sizeMax    = $request->filled('size_max')  ? (float) $request->input('size_max')  : null;
        $weightMin  = $request->filled('weight_min') ? (float) $request->input('weight_min') : null;
        $weightMax  = $request->filled('weight_max') ? (float) $request->input('weight_max') : null;
        $sort       = in_array($request->input('sort'), ['newest', 'price_asc', 'price_desc', 'popular'])
                         ? $request->input('sort') : 'newest';

        $query = Lure::active()
            ->with(['images' => fn ($q) => $q->where('display_order', 0), 'brand'])
            ->withCount('favorites')
            ->when($q, function ($query) use ($q) {
                // 全角・半角スペースで分割してAND検索（例: "ジャッカル ミノー" → 両方含む商品のみ）
                $terms = preg_split('/[\s　]+/u', $q, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($terms as $term) {
                    $query->where(function ($query) use ($term) {
                        $query->where('model_name', 'like', "%{$term}%")
                              ->orWhereHas('brand', fn ($q2) => $q2->where('name', 'like', "%{$term}%")
                                                                    ->orWhere('name_en', 'like', "%{$term}%"))
                              ->orWhere('brand_name_custom', 'like', "%{$term}%")
                              ->orWhere('description', 'like', "%{$term}%");
                    });
                }
            })
            ->when($category, fn ($query) => $query->where('category', $category))
            ->when($conditions, fn ($query) => $query->whereIn('condition', $conditions))
            ->when($brandIds, fn ($query) => $query->whereIn('brand_id', $brandIds))
            ->when($priceMin !== null, fn ($query) => $query->where('price', '>=', $priceMin))
            ->when($priceMax !== null, fn ($query) => $query->where('price', '<=', $priceMax))
            ->when($sizeMin !== null, fn ($query) => $query->where('size_mm', '>=', $sizeMin))
            ->when($sizeMax !== null, fn ($query) => $query->where('size_mm', '<=', $sizeMax))
            ->when($weightMin !== null, fn ($query) => $query->where('weight_g', '>=', $weightMin))
            ->when($weightMax !== null, fn ($query) => $query->where('weight_g', '<=', $weightMax));

        match ($sort) {
            'price_asc'  => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'popular'    => $query->orderByDesc('favorites_count'),
            default      => $query->latest(),
        };

        $lures  = $query->paginate(24)->withQueryString();
        $brands = Brand::active()->orderBy('name_kana')->get();

        $userFavoriteIds = auth()->check()
            ? Favorite::where('user_id', auth()->id())->pluck('lure_id')->toArray()
            : [];

        $filters = compact(
            'q', 'category', 'conditions', 'brandIds',
            'priceMin', 'priceMax', 'sizeMin', 'sizeMax', 'weightMin', 'weightMax'
        );

        return view('search.index', compact('lures', 'brands', 'userFavoriteIds', 'sort', 'filters'));
    }
}

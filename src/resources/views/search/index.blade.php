<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">商品一覧</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- カテゴリタブ --}}
            <div class="flex gap-2 mb-6 overflow-x-auto pb-1">
                @php
                    $cats = [null => 'すべて', 'bass' => 'バス', 'trout' => 'トラウト', 'salt' => 'ソルト'];
                @endphp
                @foreach ($cats as $key => $label)
                    <a href="{{ route('search.index', array_merge(request()->except('category', 'page'), $key ? ['category' => $key] : [])) }}"
                       class="flex-shrink-0 px-5 py-2 rounded-full text-sm font-medium border transition
                              {{ $filters['category'] === $key
                                 ? 'bg-indigo-600 text-white border-indigo-600'
                                 : 'bg-white text-gray-600 border-gray-300 hover:border-indigo-400' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="flex gap-6">

                {{-- ===== フィルターサイドバー（PC） ===== --}}
                <aside class="hidden lg:block w-56 flex-shrink-0">
                    <form method="GET" action="{{ route('search.index') }}" id="filter-form">
                        {{-- カテゴリ（URL タブと同期するために hidden で保持） --}}
                        @if ($filters['category'])
                            <input type="hidden" name="category" value="{{ $filters['category'] }}">
                        @endif

                        {{-- キーワード --}}
                        <div class="mb-5">
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                                キーワード
                            </label>
                            <input type="text" name="q" value="{{ $filters['q'] }}"
                                   placeholder="商品名・ブランド・説明"
                                   class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>

                        {{-- コンディション --}}
                        <div class="mb-5">
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                                コンディション
                            </label>
                            @php
                                $conditionOptions = [
                                    'new_unopened' => '新品未開封',
                                    'new_opened'   => '新品開封済み',
                                    'like_new'     => '未使用に近い',
                                    'good'         => '目立った傷なし',
                                    'fair'         => 'やや傷あり',
                                    'poor'         => '傷・汚れあり',
                                ];
                            @endphp
                            <div class="space-y-1.5">
                                @foreach ($conditionOptions as $val => $label)
                                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                        <input type="checkbox" name="condition[]" value="{{ $val }}"
                                               {{ in_array($val, $filters['conditions']) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- ブランド --}}
                        @if ($brands->isNotEmpty())
                        <div class="mb-5">
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                                ブランド
                            </label>
                            <div class="space-y-1.5 max-h-48 overflow-y-auto">
                                @foreach ($brands->take(30) as $brand)
                                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                        <input type="checkbox" name="brand_id[]" value="{{ $brand->id }}"
                                               {{ in_array($brand->id, $filters['brandIds']) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        {{ $brand->name }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        {{-- 価格帯 --}}
                        <div class="mb-5">
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                                価格（円）
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="number" name="price_min" value="{{ $filters['priceMin'] }}"
                                       placeholder="下限" min="0"
                                       class="w-24 text-sm border border-gray-300 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <span class="text-gray-400 text-sm">〜</span>
                                <input type="number" name="price_max" value="{{ $filters['priceMax'] }}"
                                       placeholder="上限" min="0"
                                       class="w-24 text-sm border border-gray-300 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            </div>
                        </div>

                        {{-- サイズ --}}
                        <div class="mb-5">
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                                サイズ（mm）
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="number" name="size_min" value="{{ $filters['sizeMin'] }}"
                                       placeholder="下限" min="0" step="0.1"
                                       class="w-24 text-sm border border-gray-300 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <span class="text-gray-400 text-sm">〜</span>
                                <input type="number" name="size_max" value="{{ $filters['sizeMax'] }}"
                                       placeholder="上限" min="0" step="0.1"
                                       class="w-24 text-sm border border-gray-300 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            </div>
                        </div>

                        {{-- 重量 --}}
                        <div class="mb-5">
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                                重量（g）
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="number" name="weight_min" value="{{ $filters['weightMin'] }}"
                                       placeholder="下限" min="0" step="0.1"
                                       class="w-24 text-sm border border-gray-300 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <span class="text-gray-400 text-sm">〜</span>
                                <input type="number" name="weight_max" value="{{ $filters['weightMax'] }}"
                                       placeholder="上限" min="0" step="0.1"
                                       class="w-24 text-sm border border-gray-300 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            </div>
                        </div>

                        <input type="hidden" name="sort" value="{{ $sort }}">

                        <button type="submit"
                                class="w-full py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                            絞り込む
                        </button>
                        <a href="{{ route('search.index') }}"
                           class="block text-center mt-2 text-xs text-gray-400 hover:text-gray-600">
                            条件をリセット
                        </a>
                    </form>
                </aside>

                {{-- ===== 検索結果エリア ===== --}}
                <div class="flex-1 min-w-0">

                    {{-- 結果カウント + ソート --}}
                    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                        <p class="text-sm text-gray-500">
                            {{ number_format($lures->total()) }}件
                            @if ($filters['q'])
                                「<span class="font-medium text-gray-800">{{ $filters['q'] }}</span>」の検索結果
                            @endif
                        </p>
                        <form method="GET" action="{{ route('search.index') }}" class="flex items-center gap-2">
                            {{-- 現在のフィルターを維持 --}}
                            @foreach (request()->except('sort', 'page') as $key => $value)
                                @if (is_array($value))
                                    @foreach ($value as $v)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach
                            <label class="text-sm text-gray-500">並び順:</label>
                            <select name="sort" onchange="this.form.submit()"
                                    class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <option value="newest"    {{ $sort === 'newest'    ? 'selected' : '' }}>新着順</option>
                                <option value="price_asc" {{ $sort === 'price_asc' ? 'selected' : '' }}>価格が安い順</option>
                                <option value="price_desc"{{ $sort === 'price_desc'? 'selected' : '' }}>価格が高い順</option>
                                <option value="popular"   {{ $sort === 'popular'   ? 'selected' : '' }}>いいね数順</option>
                            </select>
                        </form>
                    </div>

                    {{-- スマホ用フィルタートグル --}}
                    <div class="lg:hidden mb-4" x-data="{ open: false }">
                        <button @click="open = !open"
                                class="w-full flex items-center justify-center gap-2 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                            絞り込み
                            @php
                                $activeFilterCount = collect([
                                    $filters['q'],
                                    $filters['conditions'],
                                    $filters['brandIds'],
                                    $filters['priceMin'],
                                    $filters['priceMax'],
                                    $filters['sizeMin'],
                                    $filters['sizeMax'],
                                    $filters['weightMin'],
                                    $filters['weightMax'],
                                ])->filter()->count();
                            @endphp
                            @if ($activeFilterCount > 0)
                                <span class="inline-flex items-center justify-center w-5 h-5 bg-indigo-600 text-white text-xs rounded-full">
                                    {{ $activeFilterCount }}
                                </span>
                            @endif
                        </button>
                        <div x-show="open" class="mt-3 p-4 bg-white border border-gray-200 rounded-lg">
                            {{-- スマホ用フィルター（サイドバーと同じ内容） --}}
                            <form method="GET" action="{{ route('search.index') }}">
                                @if ($filters['category'])
                                    <input type="hidden" name="category" value="{{ $filters['category'] }}">
                                @endif
                                <div class="mb-4">
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">キーワード</label>
                                    <input type="text" name="q" value="{{ $filters['q'] }}"
                                           class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none">
                                </div>
                                <div class="mb-4">
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">コンディション</label>
                                    <div class="grid grid-cols-2 gap-1">
                                        @foreach ($conditionOptions as $val => $label)
                                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                                <input type="checkbox" name="condition[]" value="{{ $val }}"
                                                       {{ in_array($val, $filters['conditions']) ? 'checked' : '' }}
                                                       class="rounded border-gray-300 text-indigo-600">
                                                {{ $label }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">価格（円）</label>
                                    <div class="flex items-center gap-2">
                                        <input type="number" name="price_min" value="{{ $filters['priceMin'] }}" placeholder="下限"
                                               class="w-full text-sm border border-gray-300 rounded px-2 py-1.5 focus:outline-none">
                                        <span class="text-gray-400">〜</span>
                                        <input type="number" name="price_max" value="{{ $filters['priceMax'] }}" placeholder="上限"
                                               class="w-full text-sm border border-gray-300 rounded px-2 py-1.5 focus:outline-none">
                                    </div>
                                </div>
                                <input type="hidden" name="sort" value="{{ $sort }}">
                                <div class="flex gap-2">
                                    <button type="submit"
                                            class="flex-1 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                                        絞り込む
                                    </button>
                                    <a href="{{ route('search.index') }}"
                                       class="flex-1 py-2 border border-gray-300 text-gray-600 text-sm text-center rounded-lg hover:bg-gray-50">
                                        リセット
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- 商品グリッド --}}
                    @if ($lures->isEmpty())
                        <div class="text-center py-16 text-gray-400">
                            <svg class="mx-auto h-16 w-16 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                      d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-lg font-medium">該当する商品が見つかりませんでした</p>
                            <a href="{{ route('search.index') }}"
                               class="mt-3 inline-block text-indigo-600 hover:underline text-sm">
                                検索条件をリセットする
                            </a>
                        </div>
                    @else
                        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-4">
                            @foreach ($lures as $lure)
                                @include('partials.lure-card', ['lure' => $lure, 'userFavoriteIds' => $userFavoriteIds])
                            @endforeach
                        </div>

                        <div class="mt-8">
                            {{ $lures->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

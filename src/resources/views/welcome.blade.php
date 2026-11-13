<x-app-layout>
    {{-- ヒーローセクション --}}
    <section class="bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-900 text-white py-16 sm:py-24">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h1 class="text-4xl sm:text-5xl font-bold tracking-tight">釣りルアー専用フリマ</h1>
            <p class="mt-3 text-blue-200 text-lg">バス・トラウト・ソルト対応 — ハンドメイドルアーも大歓迎</p>
            <form action="{{ route('search.index') }}" method="GET"
                  class="mt-10 flex max-w-xl mx-auto shadow-lg">
                <input type="text" name="q" placeholder="商品名・ブランドで検索..."
                       class="flex-1 rounded-l-xl px-5 py-4 text-gray-900 text-sm focus:outline-none">
                <button type="submit"
                        class="px-6 py-4 bg-orange-500 hover:bg-orange-600 text-white rounded-r-xl font-semibold text-sm transition">
                    検索
                </button>
            </form>
        </div>
    </section>

    {{-- カテゴリナビ --}}
    <section class="bg-white border-b">
        <div class="max-w-5xl mx-auto px-4">
            <div class="flex gap-2 py-4 overflow-x-auto scrollbar-hide">
                <a href="{{ route('search.index') }}"
                   class="flex-shrink-0 px-5 py-2 rounded-full text-sm font-medium border transition
                          {{ !request()->routeIs('search.index') ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:border-indigo-400' }}">
                    すべて
                </a>
                <a href="{{ route('search.index', ['category' => 'bass']) }}"
                   class="flex-shrink-0 px-5 py-2 rounded-full text-sm font-medium border border-gray-300 bg-white text-gray-600 hover:border-indigo-400 transition">
                    🎣 バス
                </a>
                <a href="{{ route('search.index', ['category' => 'trout']) }}"
                   class="flex-shrink-0 px-5 py-2 rounded-full text-sm font-medium border border-gray-300 bg-white text-gray-600 hover:border-indigo-400 transition">
                    🐟 トラウト
                </a>
                <a href="{{ route('search.index', ['category' => 'salt']) }}"
                   class="flex-shrink-0 px-5 py-2 rounded-full text-sm font-medium border border-gray-300 bg-white text-gray-600 hover:border-indigo-400 transition">
                    🌊 ソルト
                </a>
                <a href="{{ route('search.index', ['category' => 'bass']) }}"
                   class="flex-shrink-0 px-5 py-2 rounded-full text-sm font-medium border border-gray-300 bg-white text-gray-600 hover:border-indigo-400 transition">
                    🪵 ハンドメイド
                </a>
            </div>
        </div>
    </section>

    {{-- 新着商品 --}}
    <section class="max-w-5xl mx-auto px-4 py-10">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-900">新着商品</h2>
            <a href="{{ route('search.index') }}"
               class="text-sm text-indigo-600 hover:underline">もっと見る →</a>
        </div>

        @if ($newArrivals->isEmpty())
            <div class="text-center py-16 text-gray-400">
                <p class="text-lg">まだ出品されている商品はありません</p>
                @auth
                    <a href="{{ route('lures.create') }}"
                       class="mt-4 inline-block text-indigo-600 hover:underline text-sm">
                        最初のルアーを出品する
                    </a>
                @else
                    <a href="{{ route('register') }}"
                       class="mt-4 inline-block text-indigo-600 hover:underline text-sm">
                        新規登録して出品する
                    </a>
                @endauth
            </div>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                @foreach ($newArrivals as $lure)
                    @include('partials.lure-card', ['lure' => $lure, 'userFavoriteIds' => $userFavoriteIds])
                @endforeach
            </div>
        @endif
    </section>

    {{-- 出品促進バナー --}}
    @guest
    <section class="bg-indigo-50 border-t border-b border-indigo-100">
        <div class="max-w-4xl mx-auto px-4 py-10 text-center">
            <h2 class="text-2xl font-bold text-indigo-900">ルアーを売りたい方へ</h2>
            <p class="mt-2 text-indigo-700 text-sm">無料で出品できます。手数料は売れた時だけ（10%）。</p>
            <a href="{{ route('register') }}"
               class="mt-6 inline-block px-8 py-3 bg-indigo-600 text-white rounded-xl font-semibold hover:bg-indigo-700 transition">
                無料で始める
            </a>
        </div>
    </section>
    @endguest
</x-app-layout>

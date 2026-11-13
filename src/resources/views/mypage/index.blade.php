<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            マイページ
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- プロフィールサマリーカード --}}
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex items-center gap-5">
                    {{-- アバター --}}
                    @if ($user->profile_image_path)
                        <img src="{{ Storage::disk('public')->url($user->profile_image_path) }}"
                             alt="プロフィール画像"
                             class="h-20 w-20 rounded-full object-cover flex-shrink-0">
                    @else
                        <div class="h-20 w-20 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-3xl font-bold flex-shrink-0">
                            {{ mb_substr($user->display_name, 0, 1) }}
                        </div>
                    @endif

                    <div class="flex-1 min-w-0">
                        <h3 class="text-xl font-semibold text-gray-900 truncate">{{ $user->display_name }}</h3>

                        {{-- 評価 --}}
                        <div class="flex items-center gap-1 mt-1">
                            @if ($user->rating_count > 0)
                                <span class="text-yellow-400 text-sm">
                                    @for ($i = 1; $i <= 5; $i++)
                                        {{ $i <= round($user->rating_avg) ? '★' : '☆' }}
                                    @endfor
                                </span>
                                <span class="text-sm text-gray-600">
                                    {{ number_format($user->rating_avg, 1) }}（{{ $user->rating_count }}件）
                                </span>
                            @else
                                <span class="text-sm text-gray-400">評価なし</span>
                            @endif
                        </div>

                        @if ($user->bio)
                            <p class="mt-2 text-sm text-gray-600 line-clamp-2">{{ $user->bio }}</p>
                        @endif
                    </div>

                    <a href="{{ route('profile.edit') }}"
                       class="flex-shrink-0 text-sm text-indigo-600 hover:underline">
                        プロフィール編集
                    </a>
                </div>

                {{-- 統計バー --}}
                <div class="mt-5 pt-5 border-t border-gray-100 grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $user->sales_count }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">販売実績</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">¥{{ number_format($user->balance) }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">売上残高</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $activeLures->count() }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">出品中</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $activeTransactionCount }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">取引中</p>
                    </div>
                </div>
            </div>

            {{-- 出品中の商品 --}}
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h4 class="font-medium text-gray-900">出品中の商品</h4>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('lures.create') }}"
                           class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition">
                            新規出品
                        </a>
                        <a href="{{ route('lures.index') }}"
                           class="text-xs text-indigo-600 hover:underline">すべて見る</a>
                    </div>
                </div>
                @if ($activeLures->isEmpty())
                    <div class="p-8 text-center text-gray-400">
                        <p class="text-sm">出品中の商品はありません</p>
                        <a href="{{ route('lures.create') }}"
                           class="mt-2 inline-block text-indigo-600 hover:underline text-xs">
                            最初のルアーを出品する
                        </a>
                    </div>
                @else
                    <div class="divide-y divide-gray-100">
                        @foreach ($activeLures as $lure)
                            <div class="px-6 py-3 flex items-center gap-3">
                                {{-- サムネイル --}}
                                <div class="w-12 h-12 flex-shrink-0 bg-gray-100 rounded-lg overflow-hidden">
                                    @if ($lure->thumbnailUrl)
                                        <img src="{{ $lure->thumbnailUrl }}" alt="{{ $lure->model_name }}"
                                             class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-gray-300 text-xs">
                                            No image
                                        </div>
                                    @endif
                                </div>
                                {{-- 情報 --}}
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $lure->model_name ?? '（タイトル未設定）' }}</p>
                                    <p class="text-xs text-gray-500">¥{{ number_format($lure->price ?? 0) }}</p>
                                </div>
                                {{-- ステータス --}}
                                <span @class([
                                    'inline-block px-2 py-0.5 text-xs rounded-full font-medium flex-shrink-0',
                                    'bg-green-100 text-green-700' => $lure->status === 'active',
                                    'bg-gray-100 text-gray-500'   => $lure->status === 'draft',
                                    'bg-yellow-100 text-yellow-700' => $lure->status === 'suspended',
                                ])>{{ $lure->statusLabel }}</span>
                                <a href="{{ route('lures.show', $lure) }}"
                                   class="text-xs text-indigo-600 hover:underline flex-shrink-0">詳細</a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- 販売履歴 --}}
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h4 class="font-medium text-gray-900">販売履歴</h4>
                    <span class="text-xs text-gray-400">直近5件</span>
                </div>
                @if ($salesHistory->isEmpty())
                    <div class="p-8 text-center text-gray-400">
                        <p class="text-sm">販売履歴はありません</p>
                    </div>
                @else
                    <div class="divide-y divide-gray-100">
                        @foreach ($salesHistory as $tx)
                            <div class="px-6 py-3 flex items-center gap-3">
                                <div class="w-12 h-12 flex-shrink-0 bg-gray-100 rounded-lg overflow-hidden">
                                    @if ($tx->lure->images->isNotEmpty())
                                        <img src="{{ $tx->lure->images->first()->url }}" class="w-full h-full object-cover">
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $tx->lure->model_name }}</p>
                                    <p class="text-xs text-gray-500">¥{{ number_format($tx->price) }}</p>
                                </div>
                                <span @class([
                                    'inline-block px-2 py-0.5 text-xs rounded-full font-medium flex-shrink-0',
                                    'bg-blue-100 text-blue-700'     => $tx->status === 'paid',
                                    'bg-indigo-100 text-indigo-700' => $tx->status === 'shipped',
                                    'bg-purple-100 text-purple-700' => $tx->status === 'review_pending',
                                    'bg-green-100 text-green-700'   => $tx->status === 'completed',
                                    'bg-gray-100 text-gray-500'     => $tx->status === 'cancelled',
                                ])>{{ $tx->statusLabel }}</span>
                                <a href="{{ route('transactions.show', $tx) }}"
                                   class="text-xs text-indigo-600 hover:underline flex-shrink-0">詳細</a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- 購入履歴 --}}
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h4 class="font-medium text-gray-900">購入履歴</h4>
                    <span class="text-xs text-gray-400">直近5件</span>
                </div>
                @if ($purchaseHistory->isEmpty())
                    <div class="p-8 text-center text-gray-400">
                        <p class="text-sm">購入履歴はありません</p>
                    </div>
                @else
                    <div class="divide-y divide-gray-100">
                        @foreach ($purchaseHistory as $tx)
                            <div class="px-6 py-3 flex items-center gap-3">
                                <div class="w-12 h-12 flex-shrink-0 bg-gray-100 rounded-lg overflow-hidden">
                                    @if ($tx->lure->images->isNotEmpty())
                                        <img src="{{ $tx->lure->images->first()->url }}" class="w-full h-full object-cover">
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $tx->lure->model_name }}</p>
                                    <p class="text-xs text-gray-500">¥{{ number_format($tx->price) }}</p>
                                </div>
                                <span @class([
                                    'inline-block px-2 py-0.5 text-xs rounded-full font-medium flex-shrink-0',
                                    'bg-yellow-100 text-yellow-700' => $tx->status === 'pending_payment',
                                    'bg-blue-100 text-blue-700'     => $tx->status === 'paid',
                                    'bg-indigo-100 text-indigo-700' => $tx->status === 'shipped',
                                    'bg-purple-100 text-purple-700' => $tx->status === 'review_pending',
                                    'bg-green-100 text-green-700'   => $tx->status === 'completed',
                                    'bg-gray-100 text-gray-500'     => $tx->status === 'cancelled',
                                ])>{{ $tx->statusLabel }}</span>
                                <a href="{{ route('transactions.show', $tx) }}"
                                   class="text-xs text-indigo-600 hover:underline flex-shrink-0">詳細</a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- 受けたレビュー --}}
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h4 class="font-medium text-gray-900">受けたレビュー</h4>
                </div>
                <div class="p-8 text-center text-gray-400">
                    <p class="text-sm">まだレビューがありません</p>
                </div>
            </div>

            {{-- クイックリンク --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h4 class="font-medium text-gray-900 mb-4">設定・管理</h4>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <a href="{{ route('profile.edit') }}"
                       class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 text-sm text-gray-700 transition">
                        プロフィール設定
                    </a>
                    <a href="{{ route('shipping-addresses.index') }}"
                       class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 text-sm text-gray-700 transition">
                        配送先住所
                    </a>
                    <a href="{{ route('withdrawals.index') }}"
                       class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 text-sm text-gray-700 transition">
                        振込申請
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

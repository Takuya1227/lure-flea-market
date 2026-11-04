<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight truncate">
                {{ $lure->model_name }}
            </h2>
            @can('update', $lure)
                <a href="{{ route('lures.edit', $lure) }}"
                   class="text-sm text-indigo-600 hover:underline flex-shrink-0 ml-4">編集する</a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            {{-- ステータスバナー（非公開の場合） --}}
            @if ($lure->status !== 'active')
                <div class="p-3 bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-lg text-sm text-center">
                    この商品は現在「{{ $lure->statusLabel }}」です
                </div>
            @endif

            {{-- ===== 画像ギャラリー ===== --}}
            <div class="bg-white shadow rounded-lg overflow-hidden">
                @if ($lure->images->isNotEmpty())
                    @php $imageCount = $lure->images->count(); @endphp
                    <div x-data="{
                            active: 0,
                            total: {{ $imageCount }},
                            touchStartX: 0,
                            touchStartY: 0,
                            prev() { if (this.active > 0) this.active--; },
                            next() { if (this.active < this.total - 1) this.active++; },
                            swipeStart(e) {
                                this.touchStartX = e.touches[0].clientX;
                                this.touchStartY = e.touches[0].clientY;
                            },
                            swipeEnd(e) {
                                const dx = e.changedTouches[0].clientX - this.touchStartX;
                                const dy = e.changedTouches[0].clientY - this.touchStartY;
                                // 水平50px以上かつ垂直より大きい移動をスワイプと判定
                                if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) {
                                    dx < 0 ? this.next() : this.prev();
                                }
                            },
                        }">
                        {{-- メイン画像 --}}
                        <div class="relative bg-gray-100 h-72 sm:h-96 flex items-center justify-center overflow-hidden select-none"
                             @touchstart.passive="swipeStart($event)"
                             @touchend.passive="swipeEnd($event)">
                            @foreach ($lure->images as $i => $image)
                                <img src="{{ $image->url }}" alt="画像{{ $i + 1 }}"
                                     x-show="active === {{ $i }}"
                                     class="max-w-full max-h-full object-contain pointer-events-none">
                            @endforeach

                            @if ($imageCount > 1)
                                <button @click="prev" x-show="active > 0"
                                        class="absolute left-2 top-1/2 -translate-y-1/2 bg-black bg-opacity-30 hover:bg-opacity-50 text-white rounded-full w-9 h-9 flex items-center justify-center transition">
                                    &#8249;
                                </button>
                                <button @click="next" x-show="active < total - 1"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 bg-black bg-opacity-30 hover:bg-opacity-50 text-white rounded-full w-9 h-9 flex items-center justify-center transition">
                                    &#8250;
                                </button>
                                <div class="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1.5">
                                    @foreach ($lure->images as $i => $image)
                                        <button @click="active = {{ $i }}"
                                                :class="active === {{ $i }} ? 'bg-white' : 'bg-white bg-opacity-50'"
                                                class="w-2 h-2 rounded-full transition"></button>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- サムネイル行（PC） --}}
                        @if ($imageCount > 1)
                            <div class="hidden sm:flex gap-2 p-3 overflow-x-auto">
                                @foreach ($lure->images as $i => $image)
                                    <button @click="active = {{ $i }}"
                                            :class="active === {{ $i }} ? 'ring-2 ring-indigo-500' : ''"
                                            class="w-16 h-16 flex-shrink-0 rounded overflow-hidden bg-gray-100">
                                        <img src="{{ $image->url }}" alt="" class="w-full h-full object-cover">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @else
                    <div class="aspect-square max-h-72 bg-gray-100 flex items-center justify-center text-gray-300">
                        画像なし
                    </div>
                @endif
            </div>

            {{-- ===== 基本情報 + 購入/いいねボタン ===== --}}
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 text-sm text-gray-500 flex-wrap">
                            <span class="inline-block px-2 py-0.5 bg-gray-100 rounded text-xs">{{ $lure->categoryLabel }}</span>
                            @if ($lure->lure_type)
                                <span>{{ $lure->lure_type }}</span>
                            @endif
                            @if ($lure->is_handmade)
                                <span class="inline-block px-2 py-0.5 bg-orange-100 text-orange-700 rounded text-xs">ハンドメイド</span>
                            @endif
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $lure->model_name }}</h1>
                        <p class="text-gray-500 mt-0.5">{{ $lure->brandDisplayName }}</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-3xl font-bold text-gray-900">¥{{ number_format($lure->price) }}</p>
                        <p class="text-xs text-gray-400 mt-1">送料{{ $lure->shipping_cost_type === 'included' ? '込み' : '着払い' }}</p>
                    </div>
                </div>

                {{-- コンディション --}}
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <span class="text-sm text-gray-500">コンディション: </span>
                    <span class="text-sm font-medium text-gray-800">{{ $lure->conditionLabel }}</span>
                </div>

                {{-- 説明 --}}
                @if ($lure->description)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">商品説明</h3>
                        <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ $lure->description }}</p>
                    </div>
                @endif

                {{-- 購入 / いいねボタン --}}
                @if ($lure->status === 'active')
                    <div class="mt-6 pt-4 border-t border-gray-100 flex gap-3">
                        @can('update', $lure)
                            {{-- 自分の商品には購入ボタンを表示しない --}}
                        @else
                            {{-- 購入ボタン（フェーズ5で実装） --}}
                            @auth
                                <button disabled
                                        class="flex-1 py-3 bg-orange-500 text-white font-semibold rounded-xl opacity-60 cursor-not-allowed text-sm">
                                    購入する（近日公開）
                                </button>
                            @else
                                <a href="{{ route('login') }}"
                                   class="flex-1 py-3 bg-orange-500 text-white font-semibold rounded-xl hover:bg-orange-600 transition text-sm text-center">
                                    ログインして購入
                                </a>
                            @endauth
                        @endcan

                        {{-- いいねボタン --}}
                        <div x-data="{
                            favorited: {{ $isFavorited ? 'true' : 'false' }},
                            count: {{ $lure->favorites_count }},
                            isLoggedIn: {{ auth()->check() ? 'true' : 'false' }},
                            async toggle() {
                                if (!this.isLoggedIn) {
                                    window.location.href = '{{ route('login') }}';
                                    return;
                                }
                                const res = await fetch('{{ route('lures.favorite.toggle', $lure) }}', {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                        'Accept': 'application/json',
                                    }
                                });
                                if (res.ok) {
                                    const data = await res.json();
                                    this.favorited = data.favorited;
                                    this.count = data.count;
                                }
                            }
                        }">
                            <button @click="toggle"
                                    class="flex items-center gap-2 px-5 py-3 rounded-xl border-2 font-medium text-sm transition"
                                    :class="favorited ? 'border-red-400 text-red-500 bg-red-50' : 'border-gray-200 text-gray-500 hover:border-red-300 hover:text-red-400'">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                     :fill="favorited ? 'currentColor' : 'none'"
                                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                                <span x-text="count"></span>
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            {{-- ===== スペック詳細 ===== --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-medium text-gray-900 mb-4">商品詳細</h3>
                <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    @if ($lure->colorDisplayName)
                        <dt class="text-gray-500">カラー</dt>
                        <dd class="text-gray-800">{{ $lure->colorDisplayName }}</dd>
                    @endif
                    @if ($lure->size_mm)
                        <dt class="text-gray-500">サイズ</dt>
                        <dd class="text-gray-800">{{ $lure->size_mm }}mm</dd>
                    @endif
                    @if ($lure->weight_g)
                        <dt class="text-gray-500">重量</dt>
                        <dd class="text-gray-800">{{ $lure->weight_g }}g</dd>
                    @endif
                    @if ($lure->material)
                        <dt class="text-gray-500">素材</dt>
                        <dd class="text-gray-800">{{ $lure->material }}</dd>
                    @endif
                    @if ($lure->action)
                        <dt class="text-gray-500">アクション</dt>
                        <dd class="text-gray-800">{{ $lure->action }}</dd>
                    @endif
                    @if ($lure->buoyancy)
                        <dt class="text-gray-500">浮力</dt>
                        <dd class="text-gray-800">{{ $lure->buoyancy }}</dd>
                    @endif
                    @if ($lure->hook_size)
                        <dt class="text-gray-500">フックサイズ</dt>
                        <dd class="text-gray-800">{{ $lure->hook_size }}</dd>
                    @endif
                    @if ($lure->hook_condition)
                        <dt class="text-gray-500">フック状態</dt>
                        <dd class="text-gray-800">{{ $lure->hook_condition }}</dd>
                    @endif
                    @if ($lure->accessories)
                        <dt class="text-gray-500">付属品</dt>
                        <dd class="text-gray-800">{{ $lure->accessories }}</dd>
                    @endif
                    @if ($lure->shipping_prefecture)
                        <dt class="text-gray-500">発送元</dt>
                        <dd class="text-gray-800">{{ $lure->shipping_prefecture }}</dd>
                    @endif
                    @if ($lure->shipping_days)
                        <dt class="text-gray-500">発送日数</dt>
                        <dd class="text-gray-800">
                            @if ($lure->shipping_days === '1_2_days') 1〜2日
                            @elseif ($lure->shipping_days === '3_7_days') 3〜7日
                            @else 7日以上
                            @endif
                        </dd>
                    @endif
                    @if ($lure->shipping_method)
                        <dt class="text-gray-500">配送方法</dt>
                        <dd class="text-gray-800">{{ $lure->shipping_method }}</dd>
                    @endif
                </dl>
            </div>

            {{-- ===== 出品者情報 ===== --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-medium text-gray-900 mb-4">出品者</h3>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-gray-200 overflow-hidden flex-shrink-0">
                        @if ($lure->seller?->profile_image_path)
                            <img src="{{ Storage::disk('public')->url($lure->seller->profile_image_path) }}"
                                 alt="{{ $lure->seller->displayName }}"
                                 class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-400 text-lg font-bold">
                                {{ mb_substr($lure->seller?->displayName ?? '?', 0, 1) }}
                            </div>
                        @endif
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">{{ $lure->seller?->displayName ?? '退会済みユーザー' }}</p>
                        @if ($lure->seller?->bio)
                            <p class="text-sm text-gray-500 mt-0.5 line-clamp-2">{{ $lure->seller->bio }}</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ===== 出品者アクション ===== --}}
            @can('update', $lure)
                <div class="bg-white shadow rounded-lg p-6 space-y-4">
                    <h3 class="font-medium text-gray-900">出品者メニュー</h3>

                    @if ($lure->isActive())
                        <form method="POST" action="{{ route('lures.mark-down', $lure) }}"
                              x-data="{ open: false }">
                            @csrf @method('PATCH')
                            <button type="button" @click="open = !open"
                                    class="text-sm text-indigo-600 hover:underline">値下げする</button>
                            <div x-show="open" class="mt-3 flex gap-3 items-end">
                                <div>
                                    <label class="text-xs text-gray-500">新しい価格（円）</label>
                                    <input type="number" name="price" min="1" max="{{ $lure->price - 1 }}"
                                           class="mt-1 block w-36 border-gray-300 rounded-md text-sm">
                                </div>
                                <button type="submit"
                                        class="px-3 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                                    確定
                                </button>
                            </div>
                        </form>
                    @endif

                    @if ($lure->status === 'active')
                        <form method="POST" action="{{ route('lures.suspend', $lure) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="text-sm text-yellow-600 hover:underline">
                                出品を一時停止する
                            </button>
                        </form>
                    @elseif ($lure->status === 'suspended')
                        <form method="POST" action="{{ route('lures.resume', $lure) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="text-sm text-green-600 hover:underline">
                                出品を再開する
                            </button>
                        </form>
                    @endif
                </div>
            @endcan

            {{-- ===== 関連商品 ===== --}}
            @if ($similarLures->isNotEmpty())
                <div>
                    <h3 class="font-medium text-gray-900 mb-4">同じカテゴリの商品</h3>
                    @php
                        $userFavoriteIds = auth()->check()
                            ? \App\Models\Favorite::where('user_id', auth()->id())
                                ->pluck('lure_id')->toArray()
                            : [];
                    @endphp
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        @foreach ($similarLures as $lure)
                            @include('partials.lure-card', ['lure' => $lure, 'userFavoriteIds' => $userFavoriteIds])
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>

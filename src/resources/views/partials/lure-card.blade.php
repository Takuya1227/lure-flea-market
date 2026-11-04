{{--
  ルアーカード（商品一覧・検索・いいね一覧で共通使用）
  必要な変数: $lure, $userFavoriteIds
--}}
@php $cardFavorited = in_array($lure->id, $userFavoriteIds); @endphp

<div class="group bg-white rounded-xl shadow-sm hover:shadow-md transition overflow-hidden">
    {{-- サムネイル --}}
    <a href="{{ route('lures.show', $lure) }}" class="block aspect-square overflow-hidden bg-gray-100">
        @if ($lure->thumbnailUrl)
            <img src="{{ $lure->thumbnailUrl }}" alt="{{ $lure->model_name }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
        @else
            <div class="w-full h-full flex items-center justify-center text-gray-300">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        @endif
    </a>

    {{-- 情報 --}}
    <div class="p-3">
        <p class="text-xs text-gray-400 truncate">{{ $lure->brandDisplayName }}</p>
        <a href="{{ route('lures.show', $lure) }}"
           class="block font-medium text-gray-900 truncate text-sm mt-0.5 hover:text-indigo-600">
            {{ $lure->model_name }}
        </a>
        <p class="text-xs text-gray-400 mt-0.5">{{ $lure->conditionLabel }}</p>

        <div class="flex items-center justify-between mt-2">
            <span class="font-bold text-gray-900 text-sm">¥{{ number_format($lure->price) }}</span>

            {{-- いいねボタン --}}
            <div x-data="{
                favorited: {{ $cardFavorited ? 'true' : 'false' }},
                count: {{ $lure->favorites_count ?? 0 }},
                async toggle() {
                    @auth
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
                    @else
                    window.location.href = '{{ route('login') }}';
                    @endauth
                }
            }">
                <button @click.prevent="toggle"
                        class="flex items-center gap-1 text-xs transition"
                        :class="favorited ? 'text-red-500' : 'text-gray-400 hover:text-red-400'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                         :fill="favorited ? 'currentColor' : 'none'"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                    <span x-text="count"></span>
                </button>
            </div>
        </div>
    </div>
</div>

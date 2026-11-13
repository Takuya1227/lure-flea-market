<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">出品管理</h2>
            <a href="{{ route('lures.create') }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                新規出品
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if ($lures->isEmpty())
                <div class="bg-white shadow rounded-lg p-12 text-center text-gray-400">
                    <p class="text-lg">出品中の商品はありません</p>
                    <a href="{{ route('lures.create') }}"
                       class="mt-4 inline-block text-indigo-600 hover:underline text-sm">
                        最初のルアーを出品する
                    </a>
                </div>
            @else
                <div class="bg-white shadow rounded-lg divide-y divide-gray-100">
                    @foreach ($lures as $lure)
                        <div class="p-4 flex items-center gap-4">
                            {{-- サムネイル --}}
                            <div class="w-20 h-20 flex-shrink-0 bg-gray-100 rounded-lg overflow-hidden">
                                @if ($lure->thumbnailUrl)
                                    <img src="{{ $lure->thumbnailUrl }}" alt="{{ $lure->model_name }}"
                                         class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-300 text-xs">
                                        No image
                                    </div>
                                @endif
                            </div>

                            {{-- 商品情報 --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span @class([
                                        'inline-block px-2 py-0.5 text-xs rounded-full font-medium',
                                        'bg-green-100 text-green-700' => $lure->status === 'active',
                                        'bg-gray-100 text-gray-500'   => $lure->status === 'draft',
                                        'bg-yellow-100 text-yellow-700' => $lure->status === 'suspended',
                                        'bg-blue-100 text-blue-700'   => $lure->status === 'sold',
                                    ])>{{ $lure->statusLabel }}</span>
                                    <span class="text-xs text-gray-400">{{ $lure->categoryLabel }}</span>
                                </div>
                                <p class="mt-1 font-medium text-gray-900 truncate">{{ $lure->model_name ?? '（タイトル未設定）' }}</p>
                                <p class="text-sm text-gray-500">{{ $lure->brandDisplayName }}</p>
                                <p class="text-sm font-semibold text-gray-900 mt-1">¥{{ number_format($lure->price ?? 0) }}</p>
                            </div>

                            {{-- アクション --}}
                            <div class="flex-shrink-0 flex flex-col gap-2 items-end">
                                @if ($lure->isEditable())
                                    <a href="{{ route('lures.edit', $lure) }}"
                                       class="text-sm text-indigo-600 hover:underline">編集</a>
                                @endif

                                @if ($lure->status === 'active')
                                    <form method="POST" action="{{ route('lures.suspend', $lure) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="text-sm text-yellow-600 hover:underline">一時停止</button>
                                    </form>
                                @endif

                                @if ($lure->status === 'suspended')
                                    <form method="POST" action="{{ route('lures.resume', $lure) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="text-sm text-green-600 hover:underline">再開</button>
                                    </form>
                                @endif

                                @if (!in_array($lure->status, ['sold', 'deleted']))
                                    <form method="POST" action="{{ route('lures.destroy', $lure) }}"
                                          onsubmit="return confirm('この商品を削除しますか？')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-sm text-red-500 hover:underline">削除</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $lures->links() }}
                </div>
            @endif

        </div>
    </div>
</x-app-layout>

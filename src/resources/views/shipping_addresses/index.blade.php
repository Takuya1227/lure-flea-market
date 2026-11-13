<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                配送先住所の管理
            </h2>
            <a href="{{ route('shipping-addresses.create') }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                + 住所を追加
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            @if (session('status') === 'address-created')
                <div class="mb-4 p-4 bg-green-50 text-green-700 rounded-lg">住所を追加しました。</div>
            @elseif (session('status') === 'address-updated')
                <div class="mb-4 p-4 bg-green-50 text-green-700 rounded-lg">住所を更新しました。</div>
            @elseif (session('status') === 'address-deleted')
                <div class="mb-4 p-4 bg-green-50 text-green-700 rounded-lg">住所を削除しました。</div>
            @endif

            @if ($addresses->isEmpty())
                <div class="bg-white shadow rounded-lg p-8 text-center text-gray-500">
                    <p>登録されている住所はありません。</p>
                    <a href="{{ route('shipping-addresses.create') }}"
                       class="mt-4 inline-block text-indigo-600 hover:underline">
                        住所を追加する
                    </a>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($addresses as $address)
                        <div class="bg-white shadow rounded-lg p-6">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-gray-900">{{ $address->name }}</span>
                                        @if ($address->is_default)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700">
                                                デフォルト
                                            </span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-sm text-gray-600">{{ $address->full_address }}</p>
                                    <p class="text-sm text-gray-600">{{ $address->phone_number }}</p>
                                </div>
                                <div class="flex items-center gap-3 ml-4">
                                    <a href="{{ route('shipping-addresses.edit', $address) }}"
                                       class="text-sm text-indigo-600 hover:underline">編集</a>
                                    <form method="post" action="{{ route('shipping-addresses.destroy', $address) }}"
                                          onsubmit="return confirm('この住所を削除してよろしいですか？')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="text-sm text-red-500 hover:underline">削除</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>
</x-app-layout>

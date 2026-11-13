<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">振込先口座</h2>
            <a href="{{ route('bank-accounts.create') }}"
               class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition">
                + 口座を追加
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ session('error') }}</div>
            @endif

            <div class="bg-white shadow rounded-lg">
                @if ($bankAccounts->isEmpty())
                    <div class="p-10 text-center text-gray-400">
                        <p class="text-sm">振込先口座が登録されていません</p>
                        <a href="{{ route('bank-accounts.create') }}"
                           class="mt-2 inline-block text-indigo-600 hover:underline text-sm">口座を追加する</a>
                    </div>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach ($bankAccounts as $account)
                            <li class="px-6 py-4 flex items-center justify-between gap-4">
                                <div>
                                    <p class="font-medium text-gray-900 text-sm">{{ $account->bank_name }} {{ $account->branch_name }}</p>
                                    <p class="text-sm text-gray-600">{{ $account->accountTypeLabel }} {{ $account->maskedAccountNumber }}</p>
                                    <p class="text-xs text-gray-500">{{ $account->account_holder_name }}</p>
                                </div>
                                <div class="flex items-center gap-3 flex-shrink-0">
                                    <a href="{{ route('bank-accounts.edit', $account) }}"
                                       class="text-xs text-indigo-600 hover:underline">編集</a>
                                    <form action="{{ route('bank-accounts.destroy', $account) }}" method="POST"
                                          onsubmit="return confirm('この口座を削除しますか？')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-red-500 hover:underline">削除</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <p class="mt-4 text-xs text-gray-500 text-center">
                口座番号はセキュリティのため下4桁のみ表示しています。
            </p>
        </div>
    </div>
</x-app-layout>

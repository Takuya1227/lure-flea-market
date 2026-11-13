<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">振込申請</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-lg mx-auto sm:px-6 lg:px-8">

            @if ($errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                    @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                </div>
            @endif

            {{-- 残高表示 --}}
            <div class="bg-white shadow rounded-lg p-5 mb-5">
                <p class="text-sm text-gray-500">現在の売上残高</p>
                <p class="text-3xl font-bold text-gray-900">¥{{ number_format($balance) }}</p>
            </div>

            @if ($bankAccounts->isEmpty())
                <div class="bg-white shadow rounded-lg p-6 text-center">
                    <p class="text-sm text-gray-600 mb-3">振込申請には振込先口座の登録が必要です。</p>
                    <a href="{{ route('bank-accounts.create') }}"
                       class="inline-block px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition">
                        口座を登録する
                    </a>
                </div>
            @elseif ($balance < 1000)
                <div class="bg-white shadow rounded-lg p-6 text-center">
                    <p class="text-sm text-gray-600">最低振込申請額は1,000円です。</p>
                    <p class="text-sm text-gray-500 mt-1">売上を増やして再度申請してください。</p>
                </div>
            @else
                <div class="bg-white shadow rounded-lg p-6">
                    <form action="{{ route('withdrawals.store') }}" method="POST">
                        @csrf

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">振込先口座 <span class="text-red-500">*</span></label>
                                <select name="bank_account_id"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    @foreach ($bankAccounts as $account)
                                        <option value="{{ $account->id }}" {{ old('bank_account_id') == $account->id ? 'selected' : '' }}>
                                            {{ $account->bank_name }} {{ $account->branch_name }} {{ $account->accountTypeLabel }} {{ $account->maskedAccountNumber }}（{{ $account->account_holder_name }}）
                                        </option>
                                    @endforeach
                                </select>
                                @error('bank_account_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">申請金額 <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">¥</span>
                                    <input type="number" name="amount" value="{{ old('amount', $balance) }}"
                                           min="1000" max="{{ $balance }}" step="1"
                                           class="w-full border border-gray-300 rounded-lg pl-7 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('amount') border-red-400 @enderror">
                                </div>
                                <p class="mt-1 text-xs text-gray-500">最低1,000円・残高¥{{ number_format($balance) }}まで</p>
                                @error('amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <p class="mt-4 text-xs text-gray-500">通常3〜5営業日でご指定の口座にお振込みします。</p>

                        <div class="mt-6 flex gap-3">
                            <a href="{{ route('withdrawals.index') }}"
                               class="flex-1 py-2.5 border border-gray-300 text-gray-600 rounded-lg text-sm text-center hover:bg-gray-50 transition">
                                キャンセル
                            </a>
                            <button type="submit"
                                    class="flex-1 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                                申請する
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

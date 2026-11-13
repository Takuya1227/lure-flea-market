<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">振込申請履歴</h2>
            <a href="{{ route('withdrawals.create') }}"
               class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition">
                新規申請
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow rounded-lg">
                @if ($withdrawalRequests->isEmpty())
                    <div class="p-10 text-center text-gray-400">
                        <p class="text-sm">振込申請履歴はありません</p>
                    </div>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach ($withdrawalRequests as $req)
                            <li class="px-6 py-4 flex items-center justify-between gap-4">
                                <div>
                                    <p class="font-medium text-gray-900 text-sm">¥{{ number_format($req->amount) }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ $req->bankAccount->bank_name }} {{ $req->bankAccount->accountTypeLabel }}
                                        {{ $req->bankAccount->maskedAccountNumber }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $req->created_at->format('Y/m/d') }}</p>
                                </div>
                                <span @class([
                                    'inline-block px-2 py-0.5 text-xs rounded-full font-medium flex-shrink-0',
                                    'bg-yellow-100 text-yellow-700' => $req->status === 'pending',
                                    'bg-blue-100 text-blue-700'    => $req->status === 'processing',
                                    'bg-green-100 text-green-700'  => $req->status === 'completed',
                                    'bg-red-100 text-red-700'      => $req->status === 'rejected',
                                ])>{{ $req->statusLabel }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <div class="px-6 py-3">
                        {{ $withdrawalRequests->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

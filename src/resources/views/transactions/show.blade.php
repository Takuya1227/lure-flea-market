<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">取引詳細</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-5">

            @if (session('success'))
                <div class="flex items-start gap-3 p-4 bg-white border border-gray-200 border-l-4 border-l-green-500 rounded-lg shadow-sm">
                    <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm text-gray-800">{{ session('success') }}</span>
                </div>
            @endif
            @if (session('error'))
                <div class="flex items-start gap-3 p-4 bg-white border border-gray-200 border-l-4 border-l-red-500 rounded-lg shadow-sm">
                    <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm text-gray-800">{{ session('error') }}</span>
                </div>
            @endif

            {{-- ステータスバナー --}}
            @php
                $isBuyer  = auth()->id() === $transaction->buyer_id;
                $isSeller = auth()->id() === $transaction->seller_id;
                $colors = [
                    'pending_payment' => 'yellow',
                    'paid'            => 'blue',
                    'shipped'         => 'indigo',
                    'review_pending'  => 'purple',
                    'completed'       => 'green',
                    'cancelled'       => 'gray',
                    'disputed'        => 'red',
                ];
                $c = $colors[$transaction->status] ?? 'gray';
            @endphp
            <div class="p-4 rounded-lg border text-center font-semibold
                {{ $c === 'yellow' ? 'bg-yellow-50 border-yellow-200 text-yellow-800' : '' }}
                {{ $c === 'blue'   ? 'bg-blue-50 border-blue-200 text-blue-800' : '' }}
                {{ $c === 'indigo' ? 'bg-indigo-50 border-indigo-200 text-indigo-800' : '' }}
                {{ $c === 'purple' ? 'bg-purple-50 border-purple-200 text-purple-800' : '' }}
                {{ $c === 'green'  ? 'bg-green-50 border-green-200 text-green-800' : '' }}
                {{ $c === 'gray'   ? 'bg-gray-50 border-gray-200 text-gray-700' : '' }}
                {{ $c === 'red'    ? 'bg-red-50 border-red-200 text-red-800' : '' }}
            ">
                {{ $transaction->statusLabel }}
            </div>

            {{-- 次のアクション案内 --}}
            @php
                $nextAction = null;
                if ($isBuyer) {
                    $nextAction = match($transaction->status) {
                        'paid'           => '出品者が商品を発送するまでお待ちください。発送完了後に通知されます。',
                        'shipped'        => '商品が届いたら「受け取りました」ボタンを押してください。',
                        'review_pending' => '「取引完了にする」ボタンを押して取引を完了してください。',
                        default          => null,
                    };
                } elseif ($isSeller) {
                    $nextAction = match($transaction->status) {
                        'paid'           => '商品を梱包・発送し、「発送しました」ボタンを押してください。',
                        'shipped'        => '購入者が商品を受け取るまでお待ちください。',
                        'review_pending' => '購入者が取引を完了するまでお待ちください。',
                        default          => null,
                    };
                }
            @endphp
            @if ($nextAction)
                <div class="flex items-start gap-3 p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
                    <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ $nextAction }}</span>
                </div>
            @endif

            {{-- 商品情報 --}}
            <div class="bg-white shadow rounded-lg p-5">
                <h3 class="text-sm font-medium text-gray-500 mb-3">商品</h3>
                <div class="flex gap-4">
                    @if ($transaction->lure->images->isNotEmpty())
                        <img src="{{ $transaction->lure->images->first()->url }}" alt="{{ $transaction->lure->model_name }}"
                             class="w-20 h-20 object-cover rounded-lg flex-shrink-0">
                    @else
                        <div class="w-20 h-20 bg-gray-100 rounded-lg flex-shrink-0 flex items-center justify-center text-gray-300 text-xs">画像なし</div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-gray-900 truncate">{{ $transaction->lure->model_name }}</p>
                        <p class="text-sm text-gray-500">{{ $transaction->lure->brandDisplayName }}</p>
                        <p class="text-lg font-bold text-gray-900 mt-1">¥{{ number_format($transaction->price) }}</p>
                    </div>
                </div>
            </div>

            {{-- 取引情報 --}}
            <div class="bg-white shadow rounded-lg p-5">
                <h3 class="text-sm font-medium text-gray-500 mb-3">取引情報</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">取引ID</dt>
                        <dd class="text-gray-900">{{ $transaction->order_number }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">購入者</dt>
                        <dd class="text-gray-900">{{ $transaction->buyer->display_name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">出品者</dt>
                        <dd class="text-gray-900">{{ $transaction->seller->display_name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">商品価格</dt>
                        <dd class="text-gray-900">¥{{ number_format($transaction->price) }}</dd>
                    </div>
                    @auth
                        @if (auth()->id() === $transaction->seller_id)
                            <div class="flex justify-between border-t border-gray-100 pt-2">
                                <dt class="text-gray-500">手数料（10%）</dt>
                                <dd class="text-gray-900">-¥{{ number_format($transaction->fee_amount) }}</dd>
                            </div>
                            <div class="flex justify-between font-bold">
                                <dt class="text-gray-700">受取金額</dt>
                                <dd class="text-gray-900">¥{{ number_format($transaction->seller_earnings) }}</dd>
                            </div>
                        @endif
                    @endauth
                    <div class="flex justify-between border-t border-gray-100 pt-2">
                        <dt class="text-gray-500">購入日時</dt>
                        <dd class="text-gray-900">{{ $transaction->created_at->format('Y/m/d H:i') }}</dd>
                    </div>
                    @if ($transaction->paid_at)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">支払完了</dt>
                            <dd class="text-gray-900">{{ $transaction->paid_at->format('Y/m/d H:i') }}</dd>
                        </div>
                    @endif
                    @if ($transaction->shipped_at)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">発送日時</dt>
                            <dd class="text-gray-900">{{ $transaction->shipped_at->format('Y/m/d H:i') }}</dd>
                        </div>
                    @endif
                    @if ($transaction->completed_at)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">取引完了</dt>
                            <dd class="text-gray-900">{{ $transaction->completed_at->format('Y/m/d H:i') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- 配送先 --}}
            <div class="bg-white shadow rounded-lg p-5">
                <h3 class="text-sm font-medium text-gray-500 mb-3">配送先</h3>
                @php $addr = $transaction->shippingAddress; @endphp
                <p class="text-sm text-gray-900 font-medium">{{ $addr->name }}</p>
                <p class="text-sm text-gray-600">〒{{ $addr->postal_code }} {{ $addr->prefecture }}{{ $addr->city }}{{ $addr->address_line1 }}</p>
                @if ($addr->address_line2)
                    <p class="text-sm text-gray-500">{{ $addr->address_line2 }}</p>
                @endif
                <p class="text-sm text-gray-500">{{ $addr->phone_number }}</p>
            </div>

            {{-- アクションボタン --}}

            <div class="space-y-3">
                {{-- 出品者：発送しましたボタン（支払い完了後） --}}
                @if ($isSeller && $transaction->status === 'paid')
                    <form action="{{ route('transactions.updateStatus', $transaction) }}" method="POST">
                        @csrf @method('PATCH')
                        <input type="hidden" name="action" value="shipped">
                        <button type="submit"
                                class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition">
                            発送しました
                        </button>
                    </form>
                @endif

                {{-- 購入者：受け取りましたボタン（発送完了後） --}}
                @if ($isBuyer && $transaction->status === 'shipped')
                    <form action="{{ route('transactions.updateStatus', $transaction) }}" method="POST">
                        @csrf @method('PATCH')
                        <input type="hidden" name="action" value="received">
                        <button type="submit"
                                class="w-full py-3 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-xl transition">
                            受け取りました
                        </button>
                    </form>
                @endif

                {{-- 購入者：取引完了ボタン（レビュー待ち） --}}
                @if ($isBuyer && $transaction->status === 'review_pending')
                    <div class="p-4 bg-purple-50 border border-purple-200 rounded-xl text-sm text-purple-700">
                        レビュー機能は近日実装予定です。
                    </div>
                    <form action="{{ route('transactions.updateStatus', $transaction) }}" method="POST">
                        @csrf @method('PATCH')
                        <input type="hidden" name="action" value="complete">
                        <button type="submit"
                                class="w-full py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl transition">
                            取引完了にする
                        </button>
                    </form>
                @endif

                {{-- キャンセルボタン --}}
                @if ($transaction->isCancellable())
                    @if (($isBuyer && $transaction->status === 'pending_payment') ||
                         ($isSeller && in_array($transaction->status, ['pending_payment', 'paid'])))
                        <div x-data="{ open: false }">
                            <button @click="open = true"
                                    class="w-full py-3 border border-gray-300 text-gray-600 hover:bg-gray-50 font-medium rounded-xl transition">
                                キャンセルを申請する
                            </button>
                            <div x-show="open" class="mt-3 p-4 bg-gray-50 border border-gray-200 rounded-xl">
                                <form action="{{ route('transactions.updateStatus', $transaction) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="action" value="cancel">
                                    <div class="mb-3">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">キャンセル理由（任意）</label>
                                        <textarea name="cancel_reason" rows="2"
                                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400"
                                                  placeholder="キャンセル理由を入力してください"></textarea>
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="button" @click="open = false"
                                                class="flex-1 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm">
                                            戻る
                                        </button>
                                        <button type="submit"
                                                class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition">
                                            キャンセルを確定
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                @endif
            </div>

        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">商品の購入</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- エラーメッセージ --}}
            <div id="page-error" class="hidden p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm"></div>

            {{-- 商品サマリー --}}
            <div class="bg-white shadow rounded-lg p-5 flex gap-4">
                @if ($lure->images->isNotEmpty())
                    <img src="{{ $lure->images->first()->url }}" alt="{{ $lure->model_name }}"
                         class="w-24 h-24 object-cover rounded-lg flex-shrink-0">
                @else
                    <div class="w-24 h-24 bg-gray-100 rounded-lg flex-shrink-0 flex items-center justify-center text-gray-300 text-xs">画像なし</div>
                @endif
                <div class="flex-1 min-w-0">
                    <p class="text-xs text-gray-500">{{ $lure->brandDisplayName }}</p>
                    <p class="font-semibold text-gray-900 truncate">{{ $lure->model_name }}</p>
                    <p class="text-xs text-gray-500 mt-1">コンディション: {{ $lure->conditionLabel }}</p>
                    <p class="text-sm text-gray-500 mt-1">送料{{ $lure->shipping_cost_type === 'included' ? '込み' : '着払い' }}</p>
                    <p class="text-xl font-bold text-gray-900 mt-2">¥{{ number_format($lure->price) }}</p>
                </div>
            </div>

            {{-- 購入フォーム --}}
            <form id="purchase-form" novalidate>

                {{-- 配送先住所 --}}
                <div class="bg-white shadow rounded-lg p-5 mb-4">
                    <h3 class="font-medium text-gray-900 mb-4">配送先住所</h3>

                    @if ($shippingAddresses->isEmpty())
                        <p class="text-sm text-gray-500 mb-3">配送先住所が登録されていません。</p>
                        <a href="{{ route('shipping-addresses.create') }}"
                           class="inline-block text-sm text-indigo-600 hover:underline">
                            + 住所を追加する
                        </a>
                    @else
                        <div class="space-y-3">
                            @foreach ($shippingAddresses as $address)
                                <label class="flex items-start gap-3 p-3 border rounded-lg cursor-pointer hover:border-indigo-400 transition">
                                    <input type="radio" name="shipping_address_id" value="{{ $address->id }}"
                                           class="mt-0.5 text-indigo-600"
                                           {{ $loop->first ? 'checked' : '' }}>
                                    <div class="text-sm">
                                        <p class="font-medium text-gray-900">{{ $address->name }}</p>
                                        <p class="text-gray-600">〒{{ $address->postal_code }} {{ $address->prefecture }}{{ $address->city }}{{ $address->address_line1 }}</p>
                                        @if ($address->address_line2)
                                            <p class="text-gray-500">{{ $address->address_line2 }}</p>
                                        @endif
                                        <p class="text-gray-500">{{ $address->phone_number }}</p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        <a href="{{ route('shipping-addresses.create') }}"
                           class="mt-3 inline-block text-sm text-indigo-600 hover:underline">
                            + 別の住所を追加する
                        </a>
                    @endif
                </div>

                {{-- お支払い情報（PAY.JP Checkout でカード入力） --}}
                <div class="bg-white shadow rounded-lg p-5 mb-4">
                    <h3 class="font-medium text-gray-900 mb-1">お支払い方法</h3>
                    <p class="text-sm text-gray-500">次の画面（PAY.JP の安全な決済ページ）でカード情報を入力してください。カード情報はこのサイトのサーバーには保存されません。</p>
                </div>

                {{-- 購入金額 --}}
                <div class="bg-white shadow rounded-lg p-5 mb-6">
                    <h3 class="font-medium text-gray-900 mb-4">購入金額</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>商品価格</span>
                            <span>¥{{ number_format($lure->price) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>送料</span>
                            <span>{{ $lure->shipping_cost_type === 'included' ? '込み' : '着払い' }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-gray-900 text-base pt-2 border-t border-gray-100">
                            <span>合計</span>
                            <span>¥{{ number_format($lure->price) }}</span>
                        </div>
                    </div>
                </div>

                {{-- 購入ボタン --}}
                @if ($shippingAddresses->isNotEmpty())
                    <button type="submit" id="submit-btn"
                            class="w-full py-4 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-xl transition text-base">
                        ¥{{ number_format($lure->price) }} を支払って購入する
                    </button>
                @else
                    <p class="text-center text-sm text-gray-500">購入するには配送先住所の登録が必要です。</p>
                @endif

                <p class="text-xs text-gray-400 text-center mt-3">
                    購入ボタンを押すと<a href="#" class="underline">利用規約</a>に同意したことになります。
                </p>

            </form>
        </div>
    </div>

    <script>
        const submitBtn = document.getElementById('submit-btn');
        const pageError = document.getElementById('page-error');
        const lurePrice = '¥{{ number_format($lure->price) }} を支払って購入する';

        function showPageError(message) {
            pageError.textContent = message;
            pageError.classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
            submitBtn.disabled    = false;
            submitBtn.textContent = lurePrice;
        }

        // bfcache から復元されたときにボタンをリセット（ブラウザバック対策）
        window.addEventListener('pageshow', function(e) {
            if (e.persisted && submitBtn) {
                submitBtn.disabled    = false;
                submitBtn.textContent = lurePrice;
            }
        });

        document.getElementById('purchase-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            submitBtn.disabled    = true;
            submitBtn.textContent = '処理中...';
            pageError.classList.add('hidden');

            try {
                const res = await fetch('{{ route('transactions.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        lure_id:             {{ $lure->id }},
                        shipping_address_id: document.querySelector('input[name="shipping_address_id"]:checked')?.value,
                    }),
                });

                if (res.status === 429) {
                    showPageError('送信回数の上限に達しました。しばらく時間をおいてから再度お試しください。');
                    return;
                }

                const data = await res.json();

                if (!data.success) {
                    showPageError(data.error || data.message || '購入処理に失敗しました。');
                    return;
                }

                // PAY.JP の Checkout ページへリダイレクト（カード入力・3DS はそちらで完結）
                submitBtn.textContent = '決済ページに移動中...';
                window.location.href = data.checkout_url;
            } catch (err) {
                showPageError('通信エラーが発生しました。ページを再読み込みしてください。');
            }
        });
    </script>
</x-app-layout>

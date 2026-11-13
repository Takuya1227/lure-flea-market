<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequest;
use App\Models\Lure;
use App\Models\Payment;
use App\Models\ShippingAddress;
use App\Models\Transaction;
use App\Services\PayjpService;
use Illuminate\Http\JsonResponse;
use PAYJPV2\Model\CheckoutSessionStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TransactionController extends Controller
{
    // 購入確認画面（配送先選択）
    public function create(Request $request): View|RedirectResponse
    {
        $lure = Lure::with(['images', 'brand', 'seller'])->findOrFail($request->lure_id);

        // 自分の商品は購入不可
        if ($lure->seller_id === auth()->id()) {
            return redirect()->route('lures.show', $lure)
                ->with('error', '自分の出品商品は購入できません。');
        }

        // 出品中の商品のみ購入可能
        if (!$lure->isActive()) {
            return redirect()->route('lures.show', $lure)
                ->with('error', 'この商品は現在購入できません。');
        }

        $shippingAddresses = auth()->user()->shippingAddresses()->get();

        return view('transactions.create', compact('lure', 'shippingAddresses'));
    }

    /**
     * 購入処理:
     * ① DBロック付きで商品確認・取引レコード作成
     * ② PAY.JP Checkout Session 作成（カード入力・3DS は PAY.JP 側で処理）
     * ③ フロントエンドへ Checkout URL を返し、ユーザーを PAY.JP ページへ誘導
     */
    public function store(StorePurchaseRequest $request, PayjpService $payjp): JsonResponse
    {
        $user = auth()->user();

        // 配送先が自分のものか検証（他ユーザーの住所を使わせない）
        $shippingAddress = ShippingAddress::where('id', $request->shipping_address_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $transaction   = null;
        $earlyError    = null;
        $lure          = null;

        // ① DBロック付きで商品を確認し、取引レコードを作成
        DB::transaction(function () use ($request, $user, $shippingAddress, &$transaction, &$earlyError, &$lure) {
            $lure = Lure::lockForUpdate()->findOrFail($request->lure_id);

            if ($lure->seller_id === $user->id) {
                $earlyError = '自分の出品商品は購入できません。';
                return;
            }

            if (!$lure->isActive()) {
                $earlyError = 'この商品はすでに購入されました。';
                return;
            }

            // セッション期限切れ等で残った古い pending_payment をキャンセル
            Transaction::where('lure_id', $lure->id)
                ->where('buyer_id', $user->id)
                ->where('status', 'pending_payment')
                ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            $price          = $lure->price;
            $feeRate        = 0.100;
            $feeAmount      = (int) floor($price * $feeRate);
            $sellerEarnings = $price - $feeAmount;

            $transaction = Transaction::create([
                'lure_id'             => $lure->id,
                'buyer_id'            => $user->id,
                'seller_id'           => $lure->seller_id,
                'shipping_address_id' => $shippingAddress->id,
                'price'               => $price,
                'fee_rate'            => $feeRate,
                'fee_amount'          => $feeAmount,
                'seller_earnings'     => $sellerEarnings,
                'status'              => 'pending_payment',
            ]);
        });

        if ($earlyError) {
            return response()->json(['success' => false, 'error' => $earlyError], 422);
        }

        // ② PAY.JP Checkout Session 作成
        $successUrl    = route('transactions.return', $transaction);
        $cancelUrl     = route('transactions.cancel', $transaction);
        $sessionResult = $payjp->createCheckoutSession(
            $transaction->price,
            "ルアマケ: {$lure->model_name}",
            $successUrl,
            $cancelUrl
        );

        if (!$sessionResult['success']) {
            $transaction->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            return response()->json(['success' => false, 'error' => $sessionResult['error']], 422);
        }

        // 決済レコードを作成（flow_id は PAY.JP での決済完了後に returnCallback で設定）
        Payment::create([
            'transaction_id'      => $transaction->id,
            'checkout_session_id' => $sessionResult['session_id'],
            'amount'              => $transaction->price,
            'status'              => 'pending',
        ]);

        // ③ フロントエンドへ Checkout URL を返す
        return response()->json([
            'success'      => true,
            'checkout_url' => $sessionResult['checkout_url'],
        ]);
    }

    /**
     * PAY.JP Checkout 完了後のコールバック（success_url）
     * Checkout Session のステータスを確認し、completed なら取引を確定する
     */
    public function returnCallback(Transaction $transaction, PayjpService $payjp): RedirectResponse
    {
        // 本人確認
        if (auth()->id() !== $transaction->buyer_id) {
            abort(403);
        }

        if ($transaction->status !== 'pending_payment') {
            return redirect()->route('transactions.show', $transaction);
        }

        $payment = $transaction->payment;
        if (!$payment || !$payment->checkout_session_id) {
            return redirect()->route('lures.show', $transaction->lure_id)
                ->with('error', '決済情報が見つかりません。サポートへお問い合わせください。');
        }

        // PAY.JP で Checkout Session ステータスと PaymentFlow ID を取得
        $result = $payjp->getCheckoutSession($payment->checkout_session_id);

        if (!$result['success'] || $result['status'] !== CheckoutSessionStatus::COMPLETE->value) {
            $transaction->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            $lure = $transaction->lure;
            return redirect()->route('lures.show', $lure)
                ->with('error', '決済が完了しませんでした。もう一度お試しください。');
        }

        // PaymentFlow ID を Payment レコードに保存（以降の返金処理で使用）
        $payment->update(['payjp_flow_id' => $result['flow_id']]);

        return DB::transaction(function () use ($transaction, $payment) {
            // 決済中に他ユーザーが購入した場合の確認（悲観的ロック）
            $lure = Lure::lockForUpdate()->findOrFail($transaction->lure_id);

            if (!$lure->isActive()) {
                $transaction->update(['status' => 'cancelled', 'cancelled_at' => now()]);
                return redirect()->route('lures.show', $lure)
                    ->with('error', 'この商品は他の方が購入されました。購入は成立していません。');
            }

            return $this->finalizeTransaction($transaction, $payment, $lure);
        });
    }

    /**
     * PAY.JP Checkout でユーザーがキャンセルした場合のコールバック（cancel_url）
     * 取引を cancelled にしてエラーメッセージとともに商品ページへ戻す
     */
    public function cancelCallback(Transaction $transaction): RedirectResponse
    {
        if (auth()->id() !== $transaction->buyer_id) {
            abort(403);
        }

        if ($transaction->status === 'pending_payment') {
            $transaction->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        }

        return redirect()->route('lures.show', $transaction->lure_id)
            ->with('error', '決済がキャンセルされました。再度購入する場合はもう一度お試しください。');
    }

    // 取引詳細
    public function show(Transaction $transaction): View|RedirectResponse
    {
        $this->authorize('view', $transaction);

        $transaction->load(['lure.images', 'buyer', 'seller', 'shippingAddress', 'payment']);

        return view('transactions.show', compact('transaction'));
    }

    // ステータス更新（発送準備・発送済み・受取確認・キャンセル）
    public function updateStatus(Request $request, Transaction $transaction, PayjpService $payjp): RedirectResponse
    {
        $action = $request->input('action');

        return DB::transaction(function () use ($request, $transaction, $action, $payjp) {
            $transaction = Transaction::lockForUpdate()->findOrFail($transaction->id);

            switch ($action) {
                case 'shipped':
                    $this->authorize('ship', $transaction);
                    abort_unless($transaction->status === 'paid', 403);
                    $transaction->update([
                        'status'     => 'shipped',
                        'shipped_at' => now(),
                    ]);
                    break;

                case 'received':
                    $this->authorize('complete', $transaction);
                    abort_unless($transaction->status === 'shipped', 403);
                    $transaction->update(['status' => 'review_pending']);
                    break;

                case 'complete':
                    $this->authorize('complete', $transaction);
                    abort_unless($transaction->status === 'review_pending', 403);
                    $transaction->update([
                        'status'       => 'completed',
                        'completed_at' => now(),
                    ]);
                    $transaction->seller()->increment('balance', $transaction->seller_earnings);
                    $transaction->seller()->increment('sales_count');
                    break;

                case 'cancel':
                    $this->authorize('cancel', $transaction);
                    $cancelledBy = auth()->id() === $transaction->buyer_id ? 'buyer' : 'seller';

                    // 支払済みの場合は返金処理
                    if ($transaction->status === 'paid' && $transaction->payment?->payjp_flow_id) {
                        $result = $payjp->refundFlow($transaction->payment->payjp_flow_id, $transaction->price);
                        if (!$result['success']) {
                            return redirect()->back()->with('error', $result['error']);
                        }
                        $transaction->payment()->update([
                            'status'        => 'refunded',
                            'refunded_at'   => now(),
                            'refund_amount' => $transaction->price,
                        ]);
                        // 商品を出品中に戻す
                        $transaction->lure()->update(['status' => 'active', 'sold_at' => null]);
                    }

                    $transaction->update([
                        'status'        => 'cancelled',
                        'cancelled_by'  => $cancelledBy,
                        'cancel_reason' => $request->input('cancel_reason'),
                        'cancelled_at'  => now(),
                    ]);
                    break;

                default:
                    abort(400);
            }

            return redirect()->route('transactions.show', $transaction)
                ->with('success', 'ステータスを更新しました。');
        });
    }

    // DB上の取引・商品・出品者を paid 状態に更新する
    private function finalizeTransaction(Transaction $transaction, Payment $payment, Lure $lure): RedirectResponse
    {
        $payment->update(['status' => 'succeeded']);

        $transaction->update([
            'status'           => 'paid',
            'paid_at'          => now(),
            'auto_complete_at' => now()->addDays(14),
        ]);

        $lure->update(['status' => 'sold', 'sold_at' => now()]);

        return redirect()->route('transactions.show', $transaction)
            ->with('success', '購入が完了しました。');
    }
}

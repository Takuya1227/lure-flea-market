<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    // 取引詳細を見られるのは購入者・出品者のみ
    public function view(User $user, Transaction $transaction): bool
    {
        return $user->id === $transaction->buyer_id
            || $user->id === $transaction->seller_id;
    }

    // 発送済みに更新できるのは出品者のみ（支払い完了後）
    public function ship(User $user, Transaction $transaction): bool
    {
        return $user->id === $transaction->seller_id
            && $transaction->status === 'paid';
    }

    // 受取確認・取引完了は購入者のみ
    public function complete(User $user, Transaction $transaction): bool
    {
        return $user->id === $transaction->buyer_id
            && in_array($transaction->status, ['shipped', 'review_pending']);
    }

    // キャンセルは買い手（支払前のみ）または売り手（発送前のみ）
    public function cancel(User $user, Transaction $transaction): bool
    {
        if (!$transaction->isCancellable()) {
            return false;
        }
        if ($user->id === $transaction->buyer_id) {
            return $transaction->status === 'pending_payment';
        }
        if ($user->id === $transaction->seller_id) {
            return in_array($transaction->status, ['pending_payment', 'paid']);
        }
        return false;
    }
}

<?php

namespace App\Policies;

use App\Models\Lure;
use App\Models\User;

class LurePolicy
{
    // 出品者本人のみ編集・削除可能
    public function update(User $user, Lure $lure): bool
    {
        return $user->id === $lure->seller_id && $lure->isEditable();
    }

    public function delete(User $user, Lure $lure): bool
    {
        // 売却済み・取引中は削除不可（今フェーズではactiveも削除可）
        return $user->id === $lure->seller_id
            && !in_array($lure->status, ['sold', 'deleted']);
    }

    public function suspend(User $user, Lure $lure): bool
    {
        return $user->id === $lure->seller_id && $lure->status === 'active';
    }

    public function resume(User $user, Lure $lure): bool
    {
        return $user->id === $lure->seller_id && $lure->status === 'suspended';
    }

    public function publish(User $user, Lure $lure): bool
    {
        // 下書きを出品する
        return $user->id === $lure->seller_id && $lure->status === 'draft';
    }
}

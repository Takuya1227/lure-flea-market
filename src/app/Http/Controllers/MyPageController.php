<?php

namespace App\Http\Controllers;

use App\Models\Lure;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyPageController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $activeLures = Lure::forSeller($user->id)
            ->whereIn('status', ['active', 'draft', 'suspended'])
            ->with(['images' => fn($q) => $q->where('display_order', 0)])
            ->latest()
            ->limit(5)
            ->get();

        $purchaseHistory = Transaction::forBuyer($user->id)
            ->with(['lure.images' => fn($q) => $q->where('display_order', 0)])
            ->whereNotIn('status', ['pending_payment'])
            ->latest()
            ->limit(5)
            ->get();

        $salesHistory = Transaction::forSeller($user->id)
            ->with(['lure.images' => fn($q) => $q->where('display_order', 0)])
            ->whereNotIn('status', ['pending_payment'])
            ->latest()
            ->limit(5)
            ->get();

        $activeTransactionCount = Transaction::where(function ($q) use ($user) {
                $q->where('buyer_id', $user->id)->orWhere('seller_id', $user->id);
            })
            ->whereIn('status', ['paid', 'shipped', 'review_pending'])
            ->count();

        $receivedReviews = collect();

        return view('mypage.index', compact(
            'user',
            'activeLures',
            'purchaseHistory',
            'salesHistory',
            'receivedReviews',
            'activeTransactionCount',
        ));
    }
}

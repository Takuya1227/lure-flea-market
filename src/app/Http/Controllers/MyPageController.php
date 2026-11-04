<?php

namespace App\Http\Controllers;

use App\Models\Lure;
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

        $purchaseHistory  = collect();
        $receivedReviews  = collect();

        return view('mypage.index', compact(
            'user',
            'activeLures',
            'purchaseHistory',
            'receivedReviews',
        ));
    }
}

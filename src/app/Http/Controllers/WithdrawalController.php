<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWithdrawalRequest;
use App\Models\WithdrawalRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WithdrawalController extends Controller
{
    public function index(): View
    {
        $withdrawalRequests = auth()->user()
            ->withdrawalRequests()
            ->with('bankAccount')
            ->latest()
            ->paginate(10);

        return view('withdrawals.index', compact('withdrawalRequests'));
    }

    public function create(): View
    {
        $user         = auth()->user();
        $bankAccounts = $user->bankAccounts()->get();
        $balance      = $user->balance;

        return view('withdrawals.create', compact('bankAccounts', 'balance'));
    }

    public function store(StoreWithdrawalRequest $request): RedirectResponse
    {
        $user = auth()->user();

        if ($request->amount > $user->balance) {
            return redirect()->back()
                ->withErrors(['amount' => '申請金額が売上残高を超えています。'])
                ->withInput();
        }

        // 口座が自分のものか確認
        $bankAccount = $user->bankAccounts()->findOrFail($request->bank_account_id);

        DB::transaction(function () use ($request, $user, $bankAccount) {
            WithdrawalRequest::create([
                'user_id'         => $user->id,
                'bank_account_id' => $bankAccount->id,
                'amount'          => $request->amount,
                'status'          => 'pending',
            ]);

            // 申請と同時に残高から差し引き（処理中は使えない）
            $user->decrement('balance', $request->amount);
        });

        return redirect()->route('withdrawals.index')
            ->with('success', '振込申請を受け付けました。通常3〜5営業日でお振込みします。');
    }
}

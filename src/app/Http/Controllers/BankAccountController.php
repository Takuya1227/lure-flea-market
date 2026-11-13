<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBankAccountRequest;
use App\Models\BankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BankAccountController extends Controller
{
    public function index(): View
    {
        $bankAccounts = auth()->user()->bankAccounts()->latest()->get();
        return view('bank-accounts.index', compact('bankAccounts'));
    }

    public function create(): View
    {
        return view('bank-accounts.create');
    }

    public function store(StoreBankAccountRequest $request): RedirectResponse
    {
        auth()->user()->bankAccounts()->create($request->validated());

        return redirect()->route('bank-accounts.index')
            ->with('success', '振込先口座を登録しました。');
    }

    public function edit(BankAccount $bankAccount): View
    {
        $this->authorize('update', $bankAccount);
        return view('bank-accounts.edit', compact('bankAccount'));
    }

    public function update(StoreBankAccountRequest $request, BankAccount $bankAccount): RedirectResponse
    {
        $this->authorize('update', $bankAccount);
        $bankAccount->update($request->validated());

        return redirect()->route('bank-accounts.index')
            ->with('success', '振込先口座を更新しました。');
    }

    public function destroy(BankAccount $bankAccount): RedirectResponse
    {
        $this->authorize('delete', $bankAccount);

        // 申請中の振込申請がある場合は削除不可
        if ($bankAccount->withdrawalRequests()->whereIn('status', ['pending', 'processing'])->exists()) {
            return redirect()->route('bank-accounts.index')
                ->with('error', '申請中の振込申請があるため削除できません。');
        }

        $bankAccount->delete();

        return redirect()->route('bank-accounts.index')
            ->with('success', '振込先口座を削除しました。');
    }
}

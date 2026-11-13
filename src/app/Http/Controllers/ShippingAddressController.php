<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShippingAddressRequest;
use App\Models\ShippingAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShippingAddressController extends Controller
{
    public function index(Request $request): View
    {
        $addresses = $request->user()
            ->shippingAddresses()
            ->orderByDesc('is_default')
            ->orderBy('created_at')
            ->get();

        return view('shipping_addresses.index', compact('addresses'));
    }

    public function create(): View
    {
        return view('shipping_addresses.create');
    }

    public function store(ShippingAddressRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // デフォルトに設定する場合は既存のデフォルトを外す
        if (!empty($validated['is_default'])) {
            $user->shippingAddresses()->update(['is_default' => false]);
        }

        // 初めて登録する住所は自動的にデフォルトにする
        if ($user->shippingAddresses()->count() === 0) {
            $validated['is_default'] = true;
        }

        $user->shippingAddresses()->create($validated);

        return redirect()->route('shipping-addresses.index')
            ->with('status', 'address-created');
    }

    public function edit(Request $request, ShippingAddress $shippingAddress): View
    {
        // 認可: 自分の住所のみ編集可能
        abort_unless($shippingAddress->user_id === $request->user()->id, 403);

        return view('shipping_addresses.edit', compact('shippingAddress'));
    }

    public function update(ShippingAddressRequest $request, ShippingAddress $shippingAddress): RedirectResponse
    {
        // 認可: 自分の住所のみ更新可能
        abort_unless($shippingAddress->user_id === $request->user()->id, 403);

        $validated = $request->validated();

        // デフォルトに変更する場合は既存のデフォルトを外す
        if (!empty($validated['is_default'])) {
            $request->user()->shippingAddresses()
                ->where('id', '!=', $shippingAddress->id)
                ->update(['is_default' => false]);
        }

        $shippingAddress->update($validated);

        return redirect()->route('shipping-addresses.index')
            ->with('status', 'address-updated');
    }

    public function destroy(Request $request, ShippingAddress $shippingAddress): RedirectResponse
    {
        // 認可: 自分の住所のみ削除可能
        abort_unless($shippingAddress->user_id === $request->user()->id, 403);

        $wasDefault = $shippingAddress->is_default;
        $shippingAddress->delete();

        // 削除したのがデフォルト住所だった場合、次の住所を自動でデフォルトに設定
        if ($wasDefault) {
            $request->user()->shippingAddresses()->oldest()->first()?->update(['is_default' => true]);
        }

        return redirect()->route('shipping-addresses.index')
            ->with('status', 'address-deleted');
    }
}

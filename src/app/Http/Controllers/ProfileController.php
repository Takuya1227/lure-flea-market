<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // プロフィール画像のアップロード処理
        if ($request->hasFile('profile_image')) {
            // 古い画像を削除（ストレージを無駄に使わない）
            if ($user->profile_image_path) {
                Storage::disk('public')->delete($user->profile_image_path);
            }

            // セキュリティ: ランダムなファイル名で保存（元のファイル名はサーバーに渡さない）
            $file = $request->file('profile_image');
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid() . '.' . $extension;
            $validated['profile_image_path'] = $file->storeAs('profile_images', $filename, 'public');
        }

        // profile_image キーはDBカラム名と異なるので除外（profile_image_path に変換済み）
        unset($validated['profile_image']);

        $user->fill($validated);

        // メールアドレスが変更された場合は確認済みフラグをリセット
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        // プロフィール画像をストレージから削除
        if ($user->profile_image_path) {
            Storage::disk('public')->delete($user->profile_image_path);
        }

        // 個人情報の匿名化処理
        // → emailをユニーク制約を維持しつつ無効化し、同じメールで再登録できるようにする
        // → 取引履歴・レビューはuser_idで紐付いているためレコード自体は残す（法的対応・トラブル解決のため）
        $user->update([
            'name'               => '退会済みユーザー',
            'nickname'           => null,
            'email'              => 'deleted_' . $user->id . '_' . time() . '@deleted.invalid',
            'email_verified_at'  => null,
            'bio'                => null,
            'profile_image_path' => null,
        ]);

        // ソフトデリート（deleted_at に退会日時を記録）
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}

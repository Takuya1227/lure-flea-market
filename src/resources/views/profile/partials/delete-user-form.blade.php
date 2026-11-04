<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            退会する
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            退会すると、お名前・メールアドレス・プロフィール情報は削除されます。
            取引履歴・レビュー等は法的対応のため内部に保持されますが、外部には表示されません。
            取引中の商品がある場合は、取引完了後に退会手続きを行ってください。
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >退会する</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">
                本当に退会しますか？
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                退会するとログインできなくなります。
                パスワードを入力して確認してください。
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="パスワード" class="sr-only" />
                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="パスワード"
                />
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    キャンセル
                </x-secondary-button>
                <x-danger-button class="ms-3">
                    退会する
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>

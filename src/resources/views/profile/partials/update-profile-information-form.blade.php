<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            プロフィール情報
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            表示名・自己紹介・メールアドレスを設定できます。
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    {{-- enctype="multipart/form-data" は画像ファイルをサーバーに送るために必要 --}}
    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        {{-- プロフィール画像 --}}
        <div>
            <x-input-label for="profile_image" value="プロフィール画像" />
            <div class="mt-2 flex items-center gap-4">
                @if ($user->profile_image_path)
                    <img src="{{ Storage::disk('public')->url($user->profile_image_path) }}"
                         alt="プロフィール画像"
                         class="h-16 w-16 rounded-full object-cover">
                @else
                    <div class="h-16 w-16 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 text-2xl font-bold">
                        {{ mb_substr($user->display_name, 0, 1) }}
                    </div>
                @endif
                <div>
                    <input id="profile_image" name="profile_image" type="file"
                           accept="image/jpeg,image/png,image/webp"
                           class="block text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <p class="mt-1 text-xs text-gray-500">JPEG・PNG・WebP / 2MB以下</p>
                </div>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('profile_image')" />
        </div>

        {{-- 氏名 --}}
        <div>
            <x-input-label for="name" value="氏名" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                          :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        {{-- ニックネーム --}}
        <div>
            <x-input-label for="nickname" value="ニックネーム（任意）" />
            <x-text-input id="nickname" name="nickname" type="text" class="mt-1 block w-full"
                          :value="old('nickname', $user->nickname)" autocomplete="nickname"
                          placeholder="他のユーザーに表示される名前" />
            <p class="mt-1 text-xs text-gray-500">未設定の場合は氏名が表示されます。最大50文字</p>
            <x-input-error class="mt-2" :messages="$errors->get('nickname')" />
        </div>

        {{-- メールアドレス --}}
        <div>
            <x-input-label for="email" value="メールアドレス" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                          :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        メールアドレスが未確認です。
                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            確認メールを再送する
                        </button>
                    </p>
                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            確認メールを送信しました。
                        </p>
                    @endif
                </div>
            @endif
        </div>

        {{-- 自己紹介 --}}
        <div>
            <x-input-label for="bio" value="自己紹介（任意）" />
            <textarea id="bio" name="bio" rows="4"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                      maxlength="500"
                      placeholder="好きな釣りスタイルや活動エリアなど">{{ old('bio', $user->bio) }}</textarea>
            <p class="mt-1 text-xs text-gray-500">最大500文字</p>
            <x-input-error class="mt-2" :messages="$errors->get('bio')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>保存する</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }"
                   x-show="show"
                   x-transition
                   x-init="setTimeout(() => show = false, 2000)"
                   class="text-sm text-gray-600">
                    保存しました。
                </p>
            @endif
        </div>
    </form>
</section>

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            プロフィール設定
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- プロフィール情報 --}}
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            {{-- 配送先住所への案内リンク --}}
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-medium text-gray-900">配送先住所</h2>
                        <p class="mt-1 text-sm text-gray-600">購入時に使用する配送先住所を管理できます。</p>
                    </div>
                    <a href="{{ route('shipping-addresses.index') }}"
                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 transition">
                        住所を管理する
                    </a>
                </div>
            </div>

            {{-- パスワード変更 --}}
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            {{-- アカウント削除 --}}
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

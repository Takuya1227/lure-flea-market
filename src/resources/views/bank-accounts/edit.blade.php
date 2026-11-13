<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">振込先口座の編集</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-lg mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6">
                <form action="{{ route('bank-accounts.update', $bankAccount) }}" method="POST">
                    @csrf @method('PUT')
                    @include('bank-accounts._form')
                    <div class="mt-6 flex gap-3">
                        <a href="{{ route('bank-accounts.index') }}"
                           class="flex-1 py-2.5 border border-gray-300 text-gray-600 rounded-lg text-sm text-center hover:bg-gray-50 transition">
                            キャンセル
                        </a>
                        <button type="submit"
                                class="flex-1 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                            更新する
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

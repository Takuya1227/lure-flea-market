<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            配送先住所の編集
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6 sm:p-8">

                <form method="post" action="{{ route('shipping-addresses.update', $shippingAddress) }}" class="space-y-6">
                    @csrf
                    @method('patch')

                    @include('shipping_addresses.partials.form', ['address' => $shippingAddress])

                    <div class="flex items-center gap-4 pt-2">
                        <x-primary-button>更新する</x-primary-button>
                        <a href="{{ route('shipping-addresses.index') }}" class="text-sm text-gray-600 hover:underline">
                            キャンセル
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">いいねした商品</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            @if ($lures->isEmpty())
                <div class="text-center py-16 text-gray-400">
                    <svg class="mx-auto h-16 w-16 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                              d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                    <p class="text-lg font-medium">いいねした商品はまだありません</p>
                    <a href="{{ route('search.index') }}"
                       class="mt-4 inline-block text-indigo-600 hover:underline text-sm">
                        商品を探す
                    </a>
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                    @foreach ($lures as $lure)
                        @include('partials.lure-card', ['lure' => $lure, 'userFavoriteIds' => $userFavoriteIds])
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $lures->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

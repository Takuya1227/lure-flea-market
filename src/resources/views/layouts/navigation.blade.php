<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">

            {{-- ロゴ --}}
            <div class="flex items-center gap-4">
                <a href="{{ route('home') }}" class="text-xl font-bold text-indigo-600 tracking-tight">ルアマケ</a>

                {{-- 検索バー（PC表示） --}}
                <form action="{{ route('search.index') }}" method="GET"
                      class="hidden md:flex items-center">
                    <input type="text" name="q" value="{{ request('q') }}"
                           placeholder="商品名・ブランドで検索"
                           class="w-56 lg:w-72 text-sm border border-gray-300 rounded-l-lg px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <button type="submit"
                            class="px-3 py-1.5 bg-indigo-600 text-white rounded-r-lg hover:bg-indigo-700 text-sm transition">
                        検索
                    </button>
                </form>
            </div>

            {{-- PC右側メニュー --}}
            <div class="hidden sm:flex items-center gap-5">
                <a href="{{ route('search.index') }}"
                   class="text-sm text-gray-600 hover:text-gray-900 transition">商品一覧</a>

                @auth
                    <a href="{{ route('favorites.index') }}"
                       class="text-sm text-gray-600 hover:text-gray-900 transition flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                        いいね
                    </a>

                    <a href="{{ route('lures.create') }}"
                       class="text-sm px-4 py-1.5 bg-orange-500 text-white rounded-lg hover:bg-orange-600 font-medium transition">
                        出品する
                    </a>

                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center gap-1.5 px-3 py-2 border border-transparent text-sm font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition">
                                <span>{{ Auth::user()->displayName }}</span>
                                <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link :href="route('dashboard')">マイページ</x-dropdown-link>
                            <x-dropdown-link :href="route('lures.index')">出品管理</x-dropdown-link>
                            <x-dropdown-link :href="route('profile.edit')">プロフィール設定</x-dropdown-link>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link href="#"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                    ログアウト
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <a href="{{ route('login') }}"
                       class="text-sm text-gray-600 hover:text-gray-900 transition">ログイン</a>
                    <a href="{{ route('register') }}"
                       class="text-sm px-4 py-1.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium transition">
                        新規登録
                    </a>
                @endauth
            </div>

            {{-- ハンバーガーメニュー（スマホ） --}}
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = !open"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open}" class="inline-flex"
                              stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path :class="{'hidden': !open, 'inline-flex': open}" class="hidden"
                              stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- スマホメニュー展開時 --}}
    <div :class="{'block': open, 'hidden': !open}" class="hidden sm:hidden">
        {{-- 検索バー --}}
        <div class="p-3 border-b">
            <form action="{{ route('search.index') }}" method="GET" class="flex">
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="商品を検索"
                       class="flex-1 text-sm border border-gray-300 rounded-l-lg px-3 py-2 focus:outline-none">
                <button type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-r-lg text-sm">検索</button>
            </form>
        </div>

        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('search.index')">商品一覧</x-responsive-nav-link>
            @auth
                <x-responsive-nav-link :href="route('favorites.index')">いいね一覧</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('lures.create')">出品する</x-responsive-nav-link>
            @endauth
        </div>

        @auth
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->displayName }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>
            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('dashboard')">マイページ</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('lures.index')">出品管理</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('profile.edit')">プロフィール設定</x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link href="#"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        ログアウト
                    </x-responsive-nav-link>

                </form>
            </div>
        </div>
        @else
        <div class="pt-4 pb-3 border-t border-gray-200 space-y-1">
            <x-responsive-nav-link :href="route('login')">ログイン</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('register')">新規登録</x-responsive-nav-link>
        </div>
        @endauth
    </div>
</nav>

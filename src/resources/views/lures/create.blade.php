<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">ルアーを出品する</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @php $lure = new \App\Models\Lure(); @endphp
            <form method="POST" action="{{ route('lures.store') }}" enctype="multipart/form-data"
                  x-data="lureForm(null)">

                @csrf
                @include('lures._form')

                {{-- ボタン（type="submit" + name/value でどちらのボタンか判別） --}}
                <div class="mt-6 flex gap-3 justify-end">
                    <button type="submit" name="form_action" value="draft"
                            class="px-5 py-2.5 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition">
                        下書き保存
                    </button>
                    <button type="submit" name="form_action" value="publish"
                            class="px-5 py-2.5 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition">
                        出品する
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">商品を編集する</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            {{-- editForm() で lureForm と画像管理を統合 --}}
            <form method="POST" action="{{ route('lures.update', $lure) }}" enctype="multipart/form-data"
                  x-data="editForm('{{ $lure->category ?? '' }}', {{ $lure->images->map(fn($i) => ['id' => $i->id, 'url' => $i->url])->toJson() }}, {{ $lure->is_handmade ? 'true' : 'false' }})"
                  @submit="prepareFileInput">

                @csrf @method('PUT')
                @php $editMode = true; @endphp
                @include('lures._form')

                {{-- ---- 統合画像管理（既存 + 新規を一緒に並び替え・削除） ---- --}}
                <div class="mt-6 bg-white shadow rounded-lg p-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-1">商品画像</h3>
                    <p class="text-xs text-gray-400 mb-3">
                        ドラッグで順番変更・「削除」で削除マーク・右下の＋ボタンで追加（最大10枚）
                    </p>

                    <div class="grid grid-cols-4 gap-3">
                        {{-- 既存・新規画像の統合リスト --}}
                        <template x-for="(item, index) in items" :key="index">
                            <div class="relative group select-none transition-opacity duration-200"
                                 :class="{
                                     'opacity-30': item.deleted,
                                     'ring-2 ring-indigo-400 rounded-lg': draggingIndex === index,
                                     'cursor-grabbing': draggingIndex !== null,
                                     'cursor-grab': draggingIndex === null,
                                 }"
                                 draggable="true"
                                 @dragstart="dragStart(index)"
                                 @dragover.prevent="dragOver(index)"
                                 @dragend="dragEnd()">

                                <img :src="item.url" alt=""
                                     class="w-full aspect-square object-cover rounded-lg bg-gray-100 pointer-events-none">

                                {{-- 順番バッジ --}}
                                <span x-text="index + 1"
                                      class="absolute top-1 left-1 bg-black bg-opacity-50 text-white text-xs px-1.5 py-0.5 rounded pointer-events-none"></span>

                                {{-- 新規バッジ --}}
                                <template x-if="item.kind === 'new'">
                                    <span class="absolute bottom-1 left-1 bg-indigo-500 text-white text-xs px-1.5 py-0.5 rounded pointer-events-none">新規</span>
                                </template>

                                {{-- 削除/取消ボタン --}}
                                <button type="button" @click="toggleDelete(index)"
                                        class="absolute top-1 right-1 text-white rounded text-xs px-1.5 py-0.5 opacity-0 group-hover:opacity-100 transition"
                                        :class="item.deleted ? 'bg-gray-600' : 'bg-red-500'">
                                    <span x-text="item.deleted ? '取消' : '削除'"></span>
                                </button>

                                {{-- 並び順・削除の hidden input（既存画像のみ） --}}
                                <template x-if="item.kind === 'existing' && !item.deleted">
                                    <input type="hidden" name="image_order[]" :value="item.id">
                                </template>
                                <template x-if="item.kind === 'existing' && item.deleted">
                                    <input type="hidden" name="delete_image_ids[]" :value="item.id">
                                </template>
                            </div>
                        </template>

                        {{-- 画像追加ボタン（10枚未満の時のみ表示） --}}
                        <template x-if="activeCount < 10">
                            <label class="aspect-square border-2 border-dashed rounded-lg flex flex-col items-center justify-center cursor-pointer transition"
                                   :class="isDropping ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300 hover:border-indigo-400'"
                                   @dragover.prevent="isDropping = true"
                                   @dragleave.prevent="isDropping = false"
                                   @drop.prevent="isDropping = false; addFiles($event.dataTransfer.files)">
                                <span class="text-gray-300 text-3xl leading-none pointer-events-none">+</span>
                                <span class="text-xs text-gray-400 mt-1 pointer-events-none">追加</span>
                                <input type="file" multiple accept="image/jpeg,image/png,image/webp"
                                       class="sr-only" @change="addFiles($event.target.files)">
                            </label>
                        </template>
                    </div>

                    <p class="mt-2 text-xs text-gray-400" x-text="activeCount + '枚 / 最大10枚'"></p>

                    {{-- 新規画像をサーバーに送信する hidden file input --}}
                    <input type="file" name="images[]" multiple x-ref="newImagesInput" class="sr-only">
                </div>

                {{-- ボタン --}}
                <div class="mt-6 flex gap-3 justify-end">
                    <a href="{{ route('lures.show', $lure) }}"
                       class="px-5 py-2.5 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition">
                        キャンセル
                    </a>
                    @if ($lure->status === 'draft')
                        <button type="submit" name="form_action" value="draft"
                                class="px-5 py-2.5 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition">
                            下書き保存
                        </button>
                        <button type="submit" name="form_action" value="publish"
                                class="px-5 py-2.5 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition">
                            出品する
                        </button>
                    @else
                        <button type="submit" name="form_action" value="update"
                                class="px-5 py-2.5 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition">
                            更新する
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <script>
    function editForm(initialCategory, existingImages, initialIsHandmade) {
        return {
            // lureForm の内容を展開
            category: initialCategory || '',
            brandId: '',
            colorId: '',
            isHandmade: initialIsHandmade ? '1' : '0',

            // 統合画像リスト（既存 + 新規）
            items: existingImages.map(img => ({
                kind: 'existing',
                id: img.id,
                url: img.url,
                deleted: false,
            })),
            draggingIndex: null,
            isDropping: false,

            get activeCount() {
                return this.items.filter(i => !i.deleted).length;
            },

            addFiles(files) {
                const remaining = 10 - this.activeCount;
                Array.from(files).slice(0, remaining).forEach(file => {
                    if (!file.type.startsWith('image/')) return;
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.items.push({
                            kind: 'new',
                            file: file,
                            url: e.target.result,
                            deleted: false,
                        });
                    };
                    reader.readAsDataURL(file);
                });
            },

            toggleDelete(index) {
                const item = this.items[index];
                if (item.kind === 'new') {
                    // 新規画像はリストから即削除
                    this.items.splice(index, 1);
                } else {
                    item.deleted = !item.deleted;
                }
            },

            dragStart(index) {
                this.draggingIndex = index;
            },

            dragOver(index) {
                if (this.draggingIndex === null || this.draggingIndex === index) return;
                const item = this.items.splice(this.draggingIndex, 1)[0];
                this.items.splice(index, 0, item);
                this.draggingIndex = index;
            },

            dragEnd() {
                this.draggingIndex = null;
            },

            // フォーム送信前に新規画像ファイルを hidden input に反映する
            prepareFileInput() {
                const newItems = this.items.filter(i => i.kind === 'new' && !i.deleted);
                const dt = new DataTransfer();
                newItems.forEach(item => dt.items.add(item.file));
                this.$refs.newImagesInput.files = dt.files;
            },
        };
    }
    </script>
</x-app-layout>

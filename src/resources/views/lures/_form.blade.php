{{-- バリデーションエラー表示 --}}
@if ($errors->any())
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
        <ul class="text-sm text-red-600 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- ---- セクション1: カテゴリ ---- --}}
<div class="bg-white shadow rounded-lg p-6 mb-6">
    <h3 class="text-sm font-medium text-gray-700 mb-4">カテゴリ <span class="text-red-500">*</span></h3>
    <div class="grid grid-cols-3 gap-3">
        @foreach (['bass' => 'バス', 'trout' => 'トラウト', 'salt' => 'ソルト'] as $value => $label)
            <label class="relative cursor-pointer">
                <input type="radio" name="category" value="{{ $value }}"
                       x-model="category"
                       {{ old('category', $lure->category ?? '') === $value ? 'checked' : '' }}
                       class="sr-only peer">
                <div class="flex items-center justify-center p-4 border-2 rounded-lg text-sm font-medium transition
                            border-gray-200 text-gray-600
                            peer-checked:border-indigo-500 peer-checked:bg-indigo-50 peer-checked:text-indigo-700">
                    {{ $label }}
                </div>
            </label>
        @endforeach
    </div>
</div>

{{-- ---- セクション2: 基本情報 ---- --}}
<div class="bg-white shadow rounded-lg p-6 mb-6 space-y-5">
    <h3 class="text-sm font-medium text-gray-700">基本情報</h3>

    {{-- ハンドメイド / 既製品 トグル --}}
    <div>
        <label class="block text-sm text-gray-600 mb-2">種別 <span class="text-red-500">*</span></label>
        <div class="grid grid-cols-2 gap-3">
            <label class="relative cursor-pointer">
                {{-- Alpine.jsのラジオボタンはDOM value が文字列になるため '1'/'0' で統一 --}}
                <input type="radio" x-model="isHandmade" value="1" class="sr-only peer">
                <div class="flex items-center justify-center p-3 border-2 rounded-lg text-sm font-medium transition
                            border-gray-200 text-gray-600
                            peer-checked:border-indigo-500 peer-checked:bg-indigo-50 peer-checked:text-indigo-700">
                    ハンドメイド
                </div>
            </label>
            <label class="relative cursor-pointer">
                <input type="radio" x-model="isHandmade" value="0" class="sr-only peer">
                <div class="flex items-center justify-center p-3 border-2 rounded-lg text-sm font-medium transition
                            border-gray-200 text-gray-600
                            peer-checked:border-indigo-500 peer-checked:bg-indigo-50 peer-checked:text-indigo-700">
                    既製品
                </div>
            </label>
        </div>
        <input type="hidden" name="is_handmade" :value="isHandmade">
    </div>

    {{-- ブランド（既製品）/ 作り手名（ハンドメイド） --}}
    <div>
        <label class="block text-sm text-gray-600 mb-1"
               x-text="isHandmade === '1' ? '作り手名 / 屋号（任意）' : 'ブランド'"></label>

        {{-- 既製品のみ: マスターからのブランド選択ドロップダウン --}}
        <select name="brand_id"
                class="w-full border-gray-300 rounded-md text-sm mb-2"
                x-show="isHandmade === '0'"
                :disabled="isHandmade === '1'"
                x-model="brandId">
            <option value="">-- 選択してください --</option>
            @foreach ($brands as $brand)
                <option value="{{ $brand->id }}"
                    {{ old('brand_id', $lure->brand_id ?? '') == $brand->id ? 'selected' : '' }}>
                    {{ $brand->name }}
                </option>
            @endforeach
            <option value="other">その他（自由入力）</option>
        </select>

        {{-- ハンドメイド: 常に表示 / 既製品: 「その他」選択時のみ表示 --}}
        <input type="text" name="brand_name_custom"
               :placeholder="isHandmade === '1' ? '例: 山田ルアー工房、タクルアー' : 'ブランド名を入力'"
               value="{{ old('brand_name_custom', $lure->brand_name_custom ?? '') }}"
               x-show="isHandmade === '1' || brandId === 'other' || brandId === ''"
               class="w-full border-gray-300 rounded-md text-sm">
    </div>

    {{-- 商品名 --}}
    <div>
        <label class="block text-sm text-gray-600 mb-1"
               x-text="isHandmade === '1' ? '商品名 *' : 'モデル名 *'"></label>
        <input type="text" name="model_name"
               value="{{ old('model_name', $lure->model_name ?? '') }}"
               :placeholder="isHandmade === '1' ? '例: ハンドメイドポッパー 試作3号' : '例: I-Waver 105F'"
               class="w-full border-gray-300 rounded-md text-sm @error('model_name') border-red-400 @enderror">
    </div>

    {{-- ルアータイプ（カテゴリ別） --}}
    <div>
        <label class="block text-sm text-gray-600 mb-1">ルアータイプ</label>
        <select name="lure_type" class="w-full border-gray-300 rounded-md text-sm">
            <option value="">-- 選択してください --</option>

            {{-- バス用 --}}
            <template x-if="category === 'bass'">
                <optgroup label="バスルアー">
                    @foreach ([
                        'crankbait' => 'クランクベイト', 'vibration' => 'バイブレーション',
                        'spinnerbait' => 'スピナーベイト', 'buzzbait' => 'バズベイト',
                        'topwater' => 'トップウォーター/ポッパー', 'pencilbait' => 'ペンシルベイト',
                        'minnow' => 'ミノー', 'jerkbait' => 'ジャークベイト',
                        'swimbait' => 'スイムベイト', 'jig' => 'ラバージグ',
                        'worm' => 'ワーム/ソフトルアー', 'other' => 'その他',
                    ] as $val => $label)
                        <option value="{{ $val }}" {{ old('lure_type', $lure->lure_type ?? '') === $val ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </optgroup>
            </template>

            {{-- トラウト用 --}}
            <template x-if="category === 'trout'">
                <optgroup label="トラウトルアー">
                    @foreach ([
                        'spoon' => 'スプーン', 'spinner' => 'スピナー',
                        'minnow' => 'ミノー', 'crankbait' => 'クランクベイト', 'other' => 'その他',
                    ] as $val => $label)
                        <option value="{{ $val }}" {{ old('lure_type', $lure->lure_type ?? '') === $val ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </optgroup>
            </template>

            {{-- ソルト用 --}}
            <template x-if="category === 'salt'">
                <optgroup label="ソルトルアー">
                    @foreach ([
                        'minnow' => 'ミノー', 'vibration' => 'バイブレーション',
                        'metal_jig' => 'メタルジグ', 'popper' => 'ポッパー',
                        'pencilbait' => 'シンキングペンシル', 'soft_lure' => 'ワーム/ソフトルアー',
                        'tai_rubber' => 'タイラバ', 'other' => 'その他',
                    ] as $val => $label)
                        <option value="{{ $val }}" {{ old('lure_type', $lure->lure_type ?? '') === $val ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </optgroup>
            </template>
        </select>
    </div>

    {{-- コンディション --}}
    <div>
        <label class="block text-sm text-gray-600 mb-1">コンディション <span class="text-red-500">*</span></label>
        <select name="condition" class="w-full border-gray-300 rounded-md text-sm @error('condition') border-red-400 @enderror">
            <option value="">-- 選択してください --</option>
            @foreach ([
                'new_unopened' => '新品未開封',
                'new_opened'   => '新品開封済み',
                'like_new'     => '未使用に近い',
                'good'         => '目立った傷なし',
                'fair'         => 'やや傷あり',
                'poor'         => '傷・汚れあり',
            ] as $val => $label)
                <option value="{{ $val }}" {{ old('condition', $lure->condition ?? '') === $val ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- フラグ（廃盤・限定は既製品のみ表示） --}}
    <div class="flex gap-6" x-show="isHandmade === '0'">
        @foreach (['is_discontinued' => '廃盤品', 'is_limited' => '限定品'] as $field => $label)
            <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                <input type="checkbox" name="{{ $field }}" value="1"
                       :disabled="isHandmade === '1'"
                       {{ old($field, $lure->{$field} ?? false) ? 'checked' : '' }}
                       class="rounded border-gray-300 text-indigo-600">
                {{ $label }}
            </label>
        @endforeach
    </div>
</div>

{{-- ---- セクション3: スペック ---- --}}
<div class="bg-white shadow rounded-lg p-6 mb-6 space-y-5">
    <h3 class="text-sm font-medium text-gray-700">スペック（任意）</h3>

    {{-- カラー --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm text-gray-600 mb-1">カラー</label>
            <select name="color_id" class="w-full border-gray-300 rounded-md text-sm"
                    x-model="colorId">
                <option value="">-- 選択 --</option>
                @foreach ($colors as $color)
                    <option value="{{ $color->id }}"
                        {{ old('color_id', $lure->color_id ?? '') == $color->id ? 'selected' : '' }}>
                        {{ $color->name }}
                    </option>
                @endforeach
                <option value="other">その他</option>
            </select>
            <input type="text" name="color_name_custom"
                   placeholder="カラー名を入力"
                   value="{{ old('color_name_custom', $lure->color_name_custom ?? '') }}"
                   x-show="colorId === 'other'"
                   class="mt-2 w-full border-gray-300 rounded-md text-sm">
        </div>
        <div>
            <label class="block text-sm text-gray-600 mb-1">素材</label>
            <select name="material" class="w-full border-gray-300 rounded-md text-sm">
                <option value="">--</option>
                @foreach (['plastic' => 'プラスチック', 'wood' => 'ウッド', 'metal' => 'メタル', 'rubber' => 'ラバー', 'other' => 'その他'] as $val => $label)
                    <option value="{{ $val }}" {{ old('material', $lure->material ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- サイズ・重量 --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm text-gray-600 mb-1">サイズ（mm）</label>
            <input type="number" name="size_mm" min="1" max="9999"
                   value="{{ old('size_mm', $lure->size_mm ?? '') }}"
                   class="w-full border-gray-300 rounded-md text-sm">
        </div>
        <div>
            <label class="block text-sm text-gray-600 mb-1">重量（g）</label>
            <input type="number" name="weight_g" step="0.1" min="0.1" max="9999.9"
                   value="{{ old('weight_g', $lure->weight_g ?? '') }}"
                   class="w-full border-gray-300 rounded-md text-sm">
        </div>
    </div>

    {{-- アクション・浮力 --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm text-gray-600 mb-1">アクション</label>
            <select name="action" class="w-full border-gray-300 rounded-md text-sm">
                <option value="">--</option>
                @foreach ([
                    'wobbling' => 'ウォブリング', 'rolling' => 'ローリング',
                    'wobble_and_roll' => 'ウォブ&ロール', 'slide' => 'スライド',
                    'i_shaped' => 'Iシェイプ', 'no_action' => 'ノーアクション', 'other' => 'その他',
                ] as $val => $label)
                    <option value="{{ $val }}" {{ old('action', $lure->action ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm text-gray-600 mb-1">浮力</label>
            <select name="buoyancy" class="w-full border-gray-300 rounded-md text-sm">
                <option value="">--</option>
                @foreach (['floating' => 'フローティング', 'suspending' => 'サスペンド', 'sinking' => 'シンキング'] as $val => $label)
                    <option value="{{ $val }}" {{ old('buoyancy', $lure->buoyancy ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- レンジ（複数選択） --}}
    <div>
        <label class="block text-sm text-gray-600 mb-2">レンジ（複数選択可）</label>
        <div class="flex flex-wrap gap-3">
            @php $selectedRanges = old('ranges', $lure->ranges ?? []); @endphp
            @foreach (['surface' => '表層', 'shallow' => 'シャロー', 'middle' => '中層', 'deep' => 'ディープ', 'bottom' => 'ボトム'] as $val => $label)
                <label class="flex items-center gap-1.5 text-sm text-gray-600 cursor-pointer">
                    <input type="checkbox" name="ranges[]" value="{{ $val }}"
                           {{ in_array($val, (array)$selectedRanges) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-indigo-600">
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </div>

    {{-- フック --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm text-gray-600 mb-1">フックサイズ</label>
            <input type="text" name="hook_size" placeholder="例: #6, 1/0"
                   value="{{ old('hook_size', $lure->hook_size ?? '') }}"
                   class="w-full border-gray-300 rounded-md text-sm">
        </div>
        <div>
            <label class="block text-sm text-gray-600 mb-1">フック状態</label>
            <select name="hook_condition" class="w-full border-gray-300 rounded-md text-sm">
                <option value="">--</option>
                @foreach (['new' => '新品', 'replaced' => '交換済み', 'used' => '使用済み', 'rusty' => 'サビあり'] as $val => $label)
                    <option value="{{ $val }}" {{ old('hook_condition', $lure->hook_condition ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- 付属品 --}}
    <div>
        <label class="block text-sm text-gray-600 mb-1">付属品</label>
        <input type="text" name="accessories"
               placeholder="例: 元箱あり、ケースあり"
               value="{{ old('accessories', $lure->accessories ?? '') }}"
               class="w-full border-gray-300 rounded-md text-sm">
    </div>
</div>

{{-- ---- セクション4: カテゴリ別詳細属性 ---- --}}
<template x-if="category === 'bass'">
    <div class="bg-white shadow rounded-lg p-6 mb-6 space-y-4">
        <h3 class="text-sm font-medium text-gray-700">バス釣り詳細</h3>
        <div>
            <label class="block text-sm text-gray-600 mb-1">ターゲット</label>
            <select name="category_attributes[target_species]" class="w-full border-gray-300 rounded-md text-sm">
                <option value="">--</option>
                <option value="largemouth" {{ old('category_attributes.target_species', ($lure->category_attributes ?? [])['target_species'] ?? '') === 'largemouth' ? 'selected' : '' }}>ラージマウスバス</option>
                <option value="smallmouth" {{ old('category_attributes.target_species', ($lure->category_attributes ?? [])['target_species'] ?? '') === 'smallmouth' ? 'selected' : '' }}>スモールマウスバス</option>
                <option value="both" {{ old('category_attributes.target_species', ($lure->category_attributes ?? [])['target_species'] ?? '') === 'both' ? 'selected' : '' }}>両方</option>
            </select>
        </div>
    </div>
</template>

<template x-if="category === 'trout'">
    <div class="bg-white shadow rounded-lg p-6 mb-6 space-y-4">
        <h3 class="text-sm font-medium text-gray-700">トラウト詳細</h3>
        <div>
            <label class="block text-sm text-gray-600 mb-1">対応フィールド</label>
            <div class="flex gap-4">
                @foreach (['stream' => '渓流', 'managed_pond' => '管理釣り場', 'lake' => '湖'] as $val => $label)
                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                        <input type="checkbox" name="category_attributes[field_type][]" value="{{ $val }}"
                               class="rounded border-gray-300 text-indigo-600">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>
    </div>
</template>

<template x-if="category === 'salt'">
    <div class="bg-white shadow rounded-lg p-6 mb-6 space-y-4">
        <h3 class="text-sm font-medium text-gray-700">ソルト詳細</h3>
        <div>
            <label class="block text-sm text-gray-600 mb-1">ターゲット魚種</label>
            <div class="grid grid-cols-3 gap-2">
                @foreach ([
                    'seabass' => 'シーバス', 'flounder' => 'ヒラメ',
                    'mebaru' => 'メバル', 'aji' => 'アジ',
                    'jigging' => 'ジギング全般', 'other' => 'その他',
                ] as $val => $label)
                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                        <input type="checkbox" name="category_attributes[target_species][]" value="{{ $val }}"
                               class="rounded border-gray-300 text-indigo-600">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>
        <div>
            <label class="block text-sm text-gray-600 mb-1">フィールド</label>
            <select name="category_attributes[field_type]" class="w-full border-gray-300 rounded-md text-sm">
                <option value="">--</option>
                <option value="shore">ショア（岸）</option>
                <option value="offshore">オフショア（船）</option>
                <option value="both">両方</option>
            </select>
        </div>
    </div>
</template>

{{-- ---- セクション5: 商品説明 ---- --}}
<div class="bg-white shadow rounded-lg p-6 mb-6">
    <label class="block text-sm font-medium text-gray-700 mb-2">
        商品説明 <span class="text-red-500">*</span>
    </label>
    <textarea name="description" rows="6"
              placeholder="ルアーの状態、使用感、入手経緯など詳しく記載してください"
              class="w-full border-gray-300 rounded-md text-sm @error('description') border-red-400 @enderror">{{ old('description', $lure->description ?? '') }}</textarea>
    <p class="mt-1 text-xs text-gray-400">最大5000文字</p>
</div>

{{-- ---- セクション6: 価格 ---- --}}
<div class="bg-white shadow rounded-lg p-6 mb-6">
    <label class="block text-sm font-medium text-gray-700 mb-2">
        販売価格 <span class="text-red-500">*</span>
    </label>
    <div class="flex items-center gap-2">
        <span class="text-gray-500">¥</span>
        <input type="number" name="price" min="1" max="9999999"
               value="{{ old('price', $lure->price ?? '') }}"
               class="w-40 border-gray-300 rounded-md text-sm @error('price') border-red-400 @enderror">
    </div>
    <p class="mt-2 text-xs text-gray-400">販売手数料10%を差し引いた金額が売上になります</p>
</div>

{{-- ---- セクション7: 配送情報 ---- --}}
<div class="bg-white shadow rounded-lg p-6 mb-6 space-y-5">
    <h3 class="text-sm font-medium text-gray-700">配送情報</h3>

    {{-- 送料 --}}
    <div>
        <label class="block text-sm text-gray-600 mb-2">送料負担 <span class="text-red-500">*</span></label>
        <div class="flex gap-4">
            @foreach (['included' => '送料込み（出品者負担）', 'cash_on_delivery' => '着払い（購入者負担）'] as $val => $label)
                <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                    <input type="radio" name="shipping_cost_type" value="{{ $val }}"
                           {{ old('shipping_cost_type', $lure->shipping_cost_type ?? '') === $val ? 'checked' : '' }}
                           class="border-gray-300 text-indigo-600">
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </div>

    {{-- 配送方法・発送元・日数 --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm text-gray-600 mb-1">配送方法</label>
            <input type="text" name="shipping_method"
                   placeholder="例: ゆうパック、ネコポス"
                   value="{{ old('shipping_method', $lure->shipping_method ?? '') }}"
                   class="w-full border-gray-300 rounded-md text-sm">
        </div>
        <div>
            <label class="block text-sm text-gray-600 mb-1">発送元 <span class="text-red-500">*</span></label>
            <select name="shipping_prefecture" class="w-full border-gray-300 rounded-md text-sm @error('shipping_prefecture') border-red-400 @enderror">
                <option value="">-- 都道府県 --</option>
                @foreach ($prefectures as $pref)
                    <option value="{{ $pref }}"
                        {{ old('shipping_prefecture', $lure->shipping_prefecture ?? '') === $pref ? 'selected' : '' }}>
                        {{ $pref }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- 発送日数 --}}
    <div>
        <label class="block text-sm text-gray-600 mb-1">発送までの日数 <span class="text-red-500">*</span></label>
        <select name="shipping_days" class="w-full border-gray-300 rounded-md text-sm @error('shipping_days') border-red-400 @enderror">
            <option value="">--</option>
            @foreach (['1_2_days' => '1〜2日', '3_7_days' => '3〜7日', 'over_7_days' => '7日以上'] as $val => $label)
                <option value="{{ $val }}" {{ old('shipping_days', $lure->shipping_days ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>

{{-- ---- セクション8: 画像アップロード（編集画面では別セクションを使うため非表示） ---- --}}
@if (!($editMode ?? false))
<div class="bg-white shadow rounded-lg p-6 mb-6"
     x-data="imageUpload({{ json_encode(old('temp_image_paths', [])) }})">
    <h3 class="text-sm font-medium text-gray-700 mb-3">
        商品画像 <span class="text-xs text-gray-400">（最大10枚・各5MB以下・JPEG/PNG/WebP）</span>
    </h3>

    {{-- サイズエラー --}}
    <div x-show="sizeError" x-cloak
         class="mb-3 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600"
         x-text="sizeError"></div>

    {{-- アップロード中メッセージ --}}
    <div x-show="hasUploading" x-cloak
         class="mb-3 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
        画像をアップロード中です。完了までお待ちください...
    </div>

    {{-- ドロップゾーン --}}
    <div class="border-2 border-dashed rounded-lg p-6 text-center transition"
         :class="isDragging ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300 hover:border-indigo-400'"
         @dragover.prevent="isDragging = true"
         @dragleave.prevent="isDragging = false"
         @drop.prevent="handleDrop($event)">
        <label class="cursor-pointer block" :class="images.length >= 10 ? 'opacity-40 pointer-events-none' : ''">
            {{-- ファイル選択用（フォーム送信には使わない） --}}
            <input type="file" multiple accept="image/jpeg,image/png,image/webp"
                   x-ref="fileInput" class="sr-only" @change="onFileChange($event)">
            <p class="text-sm text-gray-500">クリックして画像を選択</p>
            <p class="text-xs text-gray-400 mt-1">または画像をここにドラッグ＆ドロップ</p>
        </label>
    </div>

    {{-- プレビューグリッド --}}
    <div class="mt-4 grid grid-cols-4 gap-3" x-show="images.length > 0">
        <template x-for="(img, i) in images" :key="i">
            <div class="relative aspect-square bg-gray-100 rounded-lg overflow-hidden">
                <img :src="img.url" alt="" class="w-full h-full object-cover">

                {{-- アップロード中スピナー --}}
                <div x-show="img.uploading"
                     class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                    <svg class="animate-spin h-6 w-6 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                </div>

                {{-- アップロードエラー --}}
                <div x-show="img.error"
                     class="absolute inset-0 bg-red-600 bg-opacity-80 flex items-center justify-center p-1">
                    <span class="text-white text-xs text-center leading-tight" x-text="img.error"></span>
                </div>

                {{-- 枚数番号 --}}
                <span x-show="!img.uploading && !img.error" x-text="i + 1"
                      class="absolute top-1 left-1 bg-black bg-opacity-50 text-white text-xs px-1.5 py-0.5 rounded"></span>

                {{-- 削除ボタン --}}
                <button type="button" @click="removeImage(i)"
                        class="absolute top-1 right-1 w-5 h-5 bg-black bg-opacity-60 hover:bg-opacity-80 text-white rounded-full flex items-center justify-center text-xs leading-none transition">
                    ✕
                </button>

                {{-- 一時パスをフォームへ送信（アップロード成功済みのみ） --}}
                <template x-if="img.path">
                    <input type="hidden" name="temp_image_paths[]" :value="img.path">
                </template>
            </div>
        </template>
    </div>
    <p class="mt-2 text-xs text-gray-400" x-show="images.length > 0"
       x-text="images.length + '枚' + (hasUploading ? '（アップロード中...）' : '選択中')"></p>
</div>
@endif

{{--
    ---- セクション9: 動画アップロード（現在無効） ----
    サーバーのストレージ容量を圧迫するリスクがあるため、一時的に非表示にしています。
    将来的にS3等の外部ストレージに移行した際に有効化する予定です。
    バックエンド（ルート・コントローラー・モデル・DB列）はすべて実装済みです。
    有効化する際はこのコメントブロックを外すだけで動作します。
    参照: /tasks/decisions.md「動画アップロード機能」

<div class="bg-white shadow rounded-lg p-6 mb-6"
     x-data="videoUpload('{{ old('temp_video_path', '') }}')">
    <h3 class="text-sm font-medium text-gray-700 mb-1">
        ルアーの動き（動画）<span class="text-xs text-gray-400 ml-1">任意・MP4/MOV/WebP・100MB以下</span>
    </h3>
    <p class="text-xs text-gray-400 mb-3">ルアーの動きがわかる動画を1本アップロードできます</p>

    {{-- エラー --}}
    <div x-show="uploadError" x-cloak
         class="mb-3 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600"
         x-text="uploadError"></div>

    {{-- アップロード中 --}}
    <div x-show="uploading" x-cloak
         class="mb-3 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
        動画をアップロード中です...
    </div>

    {{-- 選択ボタン（動画未選択時）--}}
    <div x-show="!videoPath && !uploading">
        <label class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition">
            <input type="file" accept="video/mp4,video/quicktime,video/webm"
                   x-ref="videoInput" class="sr-only" @change="onVideoChange($event)">
            動画を選択する
        </label>
    </div>

    {{-- プレビュー（アップロード完了後）--}}
    <div x-show="videoPath && !uploading" x-cloak>
        <video :src="videoUrl" controls class="w-full max-h-64 rounded-lg bg-black"></video>
        <div class="mt-2 flex items-center gap-3">
            <p class="text-xs text-gray-500">動画が選択されています</p>
            <button type="button" @click="removeVideo()"
                    class="text-xs text-red-500 hover:underline">削除</button>
        </div>
        <input type="hidden" name="temp_video_path" :value="videoPath">
    </div>
</div>
--}}

{{-- Alpine.js: フォームロジック --}}
<script>
function lureForm(initialCategory) {
    return {
        category: initialCategory || '{{ old('category', $lure->category ?? '') }}',
        brandId: '{{ old('brand_id', $lure->brand_id ?? '') }}',
        colorId: '{{ old('color_id', $lure->color_id ?? '') }}',
        isHandmade: '{{ old('is_handmade', $lure->is_handmade ?? true) ? '1' : '0' }}',
    };
}

function imageUpload(existingPaths) {
    return {
        images: [],      // [{url, path, uploading, error}]
        isDragging: false,
        sizeError: '',

        get hasUploading() {
            return this.images.some(img => img.uploading);
        },

        init() {
            // バリデーションエラー後のページリロード時に一時画像を復元
            (existingPaths || []).forEach(path => {
                this.images.push({ url: '/storage/' + path, path, uploading: false, error: null });
            });

            // アップロード中は送信をブロック
            const form = this.$el.closest('form');
            if (form) {
                form.addEventListener('submit', (e) => {
                    if (this.hasUploading) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                    }
                }, true);
            }
        },

        async addFiles(files) {
            const maxSize = 5 * 1024 * 1024;
            const oversized = [], valid = [];

            Array.from(files).forEach(f => {
                if (f.size > maxSize) oversized.push(f.name);
                else valid.push(f);
            });

            this.sizeError = oversized.length
                ? '5MB未満の画像を選択してください（超過: ' + oversized.join(', ') + '）'
                : '';

            const slots = 10 - this.images.length;
            if (slots <= 0 || valid.length === 0) return;

            const token = document.head.querySelector('meta[name="csrf-token"]').content;

            for (const file of valid.slice(0, slots)) {
                const idx = this.images.length;
                this.images.push({ url: URL.createObjectURL(file), path: null, uploading: true, error: null });

                const form = new FormData();
                form.append('image', file);
                form.append('_token', token);

                try {
                    const res = await fetch('{{ route("lures.images.temp") }}', { method: 'POST', body: form });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || 'エラー');
                    this.images[idx] = { ...this.images[idx], path: data.path, uploading: false };
                } catch {
                    this.images[idx] = { ...this.images[idx], error: 'アップロードに\n失敗しました', uploading: false };
                }
            }
        },

        removeImage(i) {
            this.images.splice(i, 1);
        },

        onFileChange(event) {
            this.addFiles(event.target.files);
            event.target.value = '';  // 同じファイルを再選択できるようにリセット
        },

        handleDrop(event) {
            this.isDragging = false;
            this.addFiles(event.dataTransfer.files);
        },
    };
}

function videoUpload(existingPath) {
    return {
        videoPath: existingPath || '',
        videoUrl: existingPath ? '/storage/' + existingPath : '',
        uploading: false,
        uploadError: '',

        init() {
            const form = this.$el.closest('form');
            if (form) {
                form.addEventListener('submit', (e) => {
                    if (this.uploading) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                    }
                }, true);
            }
        },

        async onVideoChange(event) {
            const file = event.target.files[0];
            if (!file) return;
            event.target.value = '';

            const maxSize = 100 * 1024 * 1024;
            if (file.size > maxSize) {
                this.uploadError = '動画は100MB以下にしてください（選択: ' + (file.size / 1024 / 1024).toFixed(1) + 'MB）';
                return;
            }

            this.uploadError = '';
            this.uploading = true;
            this.videoPath = '';
            this.videoUrl = '';

            const form = new FormData();
            form.append('video', file);
            form.append('_token', document.head.querySelector('meta[name="csrf-token"]').content);

            try {
                const res = await fetch('{{ route("lures.videos.temp") }}', { method: 'POST', body: form });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'エラー');
                this.videoPath = data.path;
                this.videoUrl = data.url;
            } catch {
                this.uploadError = '動画のアップロードに失敗しました。もう一度お試しください。';
            } finally {
                this.uploading = false;
            }
        },

        removeVideo() {
            this.videoPath = '';
            this.videoUrl = '';
            this.uploadError = '';
        },
    };
}
</script>

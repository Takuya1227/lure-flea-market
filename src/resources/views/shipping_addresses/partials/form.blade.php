{{-- 住所フォームの共通パーツ（create.blade.php と edit.blade.php で @include して使う） --}}

{{-- 受取人氏名 --}}
<div>
    <x-input-label for="name" value="受取人氏名" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                  :value="old('name', $address->name ?? '')" required />
    <x-input-error class="mt-2" :messages="$errors->get('name')" />
</div>

{{-- 郵便番号 --}}
<div>
    <x-input-label for="postal_code" value="郵便番号" />
    <x-text-input id="postal_code" name="postal_code" type="text" class="mt-1 block w-48"
                  :value="old('postal_code', $address->postal_code ?? '')"
                  required placeholder="123-4567" maxlength="8" />
    <x-input-error class="mt-2" :messages="$errors->get('postal_code')" />
</div>

{{-- 都道府県 --}}
<div>
    <x-input-label for="prefecture" value="都道府県" />
    <select id="prefecture" name="prefecture"
            class="mt-1 block w-48 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @php
            $prefectures = [
                '北海道','青森県','岩手県','宮城県','秋田県','山形県','福島県',
                '茨城県','栃木県','群馬県','埼玉県','千葉県','東京都','神奈川県',
                '新潟県','富山県','石川県','福井県','山梨県','長野県','岐阜県',
                '静岡県','愛知県','三重県','滋賀県','京都府','大阪府','兵庫県',
                '奈良県','和歌山県','鳥取県','島根県','岡山県','広島県','山口県',
                '徳島県','香川県','愛媛県','高知県','福岡県','佐賀県','長崎県',
                '熊本県','大分県','宮崎県','鹿児島県','沖縄県',
            ];
            $selected = old('prefecture', $address->prefecture ?? '');
        @endphp
        <option value="">選択してください</option>
        @foreach ($prefectures as $pref)
            <option value="{{ $pref }}" @selected($selected === $pref)>{{ $pref }}</option>
        @endforeach
    </select>
    <x-input-error class="mt-2" :messages="$errors->get('prefecture')" />
</div>

{{-- 市区町村 --}}
<div>
    <x-input-label for="city" value="市区町村" />
    <x-text-input id="city" name="city" type="text" class="mt-1 block w-full"
                  :value="old('city', $address->city ?? '')" required placeholder="渋谷区" />
    <x-input-error class="mt-2" :messages="$errors->get('city')" />
</div>

{{-- 番地 --}}
<div>
    <x-input-label for="address_line1" value="番地" />
    <x-text-input id="address_line1" name="address_line1" type="text" class="mt-1 block w-full"
                  :value="old('address_line1', $address->address_line1 ?? '')" required placeholder="1-2-3" />
    <x-input-error class="mt-2" :messages="$errors->get('address_line1')" />
</div>

{{-- 建物名・部屋番号（任意） --}}
<div>
    <x-input-label for="address_line2" value="建物名・部屋番号（任意）" />
    <x-text-input id="address_line2" name="address_line2" type="text" class="mt-1 block w-full"
                  :value="old('address_line2', $address->address_line2 ?? '')" placeholder="○○マンション 101号室" />
    <x-input-error class="mt-2" :messages="$errors->get('address_line2')" />
</div>

{{-- 電話番号 --}}
<div>
    <x-input-label for="phone_number" value="電話番号" />
    <x-text-input id="phone_number" name="phone_number" type="tel" class="mt-1 block w-48"
                  :value="old('phone_number', $address->phone_number ?? '')" required placeholder="090-1234-5678" />
    <x-input-error class="mt-2" :messages="$errors->get('phone_number')" />
</div>

{{-- デフォルト住所 --}}
<div class="flex items-center gap-2">
    <input id="is_default" name="is_default" type="checkbox" value="1"
           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
           @checked(old('is_default', ($address->is_default ?? false) ? '1' : '0') === '1')>
    <x-input-label for="is_default" value="この住所をデフォルトに設定する" />
</div>

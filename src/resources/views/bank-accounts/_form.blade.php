@if ($errors->any())
    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">銀行名 <span class="text-red-500">*</span></label>
        <input type="text" name="bank_name" value="{{ old('bank_name', $bankAccount->bank_name ?? '') }}"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('bank_name') border-red-400 @enderror"
               placeholder="例: 三菱UFJ銀行" required>
        @error('bank_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">支店名 <span class="text-red-500">*</span></label>
        <input type="text" name="branch_name" value="{{ old('branch_name', $bankAccount->branch_name ?? '') }}"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('branch_name') border-red-400 @enderror"
               placeholder="例: 渋谷支店" required>
        @error('branch_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">口座種別 <span class="text-red-500">*</span></label>
        <select name="account_type"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="ordinary" {{ old('account_type', $bankAccount->account_type ?? 'ordinary') === 'ordinary' ? 'selected' : '' }}>普通</option>
            <option value="checking" {{ old('account_type', $bankAccount->account_type ?? '') === 'checking' ? 'selected' : '' }}>当座</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">口座番号 <span class="text-red-500">*</span></label>
        <input type="text" name="account_number" value="{{ old('account_number', isset($bankAccount) ? '' : '') }}"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('account_number') border-red-400 @enderror"
               placeholder="1234567（7桁）" maxlength="7" required>
        <p class="mt-1 text-xs text-gray-500">半角数字7桁で入力してください。</p>
        @error('account_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">口座名義（カタカナ） <span class="text-red-500">*</span></label>
        <input type="text" name="account_holder_name" value="{{ old('account_holder_name', $bankAccount->account_holder_name ?? '') }}"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('account_holder_name') border-red-400 @enderror"
               placeholder="ヤマダ タロウ" required>
        <p class="mt-1 text-xs text-gray-500">カタカナ・スペースで入力してください。</p>
        @error('account_holder_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

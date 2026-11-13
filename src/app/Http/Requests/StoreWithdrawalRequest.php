<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_account_id' => ['required', 'integer', 'exists:bank_accounts,id'],
            'amount'          => ['required', 'integer', 'min:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'bank_account_id.required' => '振込先口座を選択してください。',
            'amount.min'               => '最低振込申請額は1,000円です。',
        ];
    }
}

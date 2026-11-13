<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_name'           => ['required', 'string', 'max:50'],
            'branch_name'         => ['required', 'string', 'max:50'],
            'account_type'        => ['required', Rule::in(['ordinary', 'checking'])],
            'account_number'      => ['required', 'string', 'regex:/^\d{7}$/'],
            'account_holder_name' => ['required', 'string', 'max:50', 'regex:/^[ァ-ヶー\s]+$/u'],
        ];
    }

    public function messages(): array
    {
        return [
            'account_number.regex'      => '口座番号は7桁の数字で入力してください。',
            'account_holder_name.regex' => '口座名義はカタカナで入力してください。',
        ];
    }
}

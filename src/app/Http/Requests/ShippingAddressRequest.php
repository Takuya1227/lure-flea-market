<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShippingAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 認可はコントローラー側のPolicy/Gate で実施
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:50'],
            // 郵便番号: 数字7桁またはハイフン付き（例: 1234567 または 123-4567）
            'postal_code'   => ['required', 'string', 'regex:/^\d{3}-?\d{4}$/'],
            'prefecture'    => ['required', 'string', 'max:10'],
            'city'          => ['required', 'string', 'max:50'],
            'address_line1' => ['required', 'string', 'max:100'],
            'address_line2' => ['nullable', 'string', 'max:100'],
            // 電話番号: 数字・ハイフンのみ
            'phone_number'  => ['required', 'string', 'regex:/^[\d\-]{10,15}$/'],
            'is_default'    => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'postal_code.regex'  => '郵便番号の形式が正しくありません（例: 123-4567）',
            'phone_number.regex' => '電話番号の形式が正しくありません（例: 090-1234-5678）',
        ];
    }
}

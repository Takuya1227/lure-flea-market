<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lure_id'             => ['required', 'integer', 'exists:lures,id'],
            'shipping_address_id' => ['required', 'integer', 'exists:shipping_addresses,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'shipping_address_id.required' => '配送先住所を選択してください。',
        ];
    }
}

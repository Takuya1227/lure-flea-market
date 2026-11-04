<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isDraft = $this->input('form_action') === 'draft';

        // 下書き保存時は必須チェックを緩和する
        if ($isDraft) {
            return [
                'category'           => ['required', 'in:bass,trout,salt'],
                'model_name'         => ['nullable', 'string', 'max:100'],
                'brand_id'           => ['nullable', 'integer', 'exists:brands,id'],
                'brand_name_custom'  => ['nullable', 'string', 'max:100'],
                'description'        => ['nullable', 'string', 'max:5000'],
                'price'              => ['nullable', 'integer', 'min:1', 'max:9999999'],
                'images'             => ['nullable', 'array', 'max:10'],
                'images.*'           => ['image', 'mimes:jpeg,png,webp', 'max:5120'],
                'condition'          => ['nullable', 'in:new_unopened,new_opened,like_new,good,fair,poor'],
                'shipping_cost_type' => ['nullable', 'in:included,cash_on_delivery'],
                'shipping_days'      => ['nullable', 'in:1_2_days,3_7_days,over_7_days'],
                'shipping_prefecture'=> ['nullable', 'string', 'max:10'],
            ];
        }

        // 出品時の必須バリデーション
        return [
            'category'            => ['required', 'in:bass,trout,salt'],
            'model_name'          => ['required', 'string', 'max:100'],
            'brand_id'            => ['nullable', 'integer', 'exists:brands,id'],
            'brand_name_custom'   => ['nullable', 'string', 'max:100'],
            'lure_type'           => ['nullable', 'string', 'max:50'],
            'condition'           => ['required', 'in:new_unopened,new_opened,like_new,good,fair,poor'],
            'is_handmade'         => ['boolean'],
            'is_discontinued'     => ['boolean'],
            'is_limited'          => ['boolean'],
            'color_id'            => ['nullable', 'integer', 'exists:colors,id'],
            'color_name_custom'   => ['nullable', 'string', 'max:100'],
            'size_mm'             => ['nullable', 'integer', 'min:1', 'max:9999'],
            'weight_g'            => ['nullable', 'numeric', 'min:0.1', 'max:9999.9'],
            'material'            => ['nullable', 'in:plastic,wood,metal,rubber,other'],
            'action'              => ['nullable', 'in:wobbling,rolling,wobble_and_roll,slide,i_shaped,no_action,other'],
            'buoyancy'            => ['nullable', 'in:floating,suspending,sinking'],
            'range'               => ['nullable', 'in:surface,shallow,middle,deep,bottom'],
            'hook_size'           => ['nullable', 'string', 'max:10'],
            'hook_condition'      => ['nullable', 'in:new,replaced,used,rusty'],
            'accessories'         => ['nullable', 'string', 'max:255'],
            'category_attributes' => ['nullable', 'array'],
            'description'         => ['required', 'string', 'max:5000'],
            'price'               => ['required', 'integer', 'min:1', 'max:9999999'],
            'shipping_cost_type'  => ['required', 'in:included,cash_on_delivery'],
            'shipping_method'     => ['nullable', 'string', 'max:100'],
            'shipping_days'       => ['required', 'in:1_2_days,3_7_days,over_7_days'],
            'shipping_prefecture' => ['required', 'string', 'max:10'],
            'images'              => ['nullable', 'array', 'max:10'],
            // セキュリティ: JPEG/PNG/WebPのみ許可、各5MB以下
            'images.*'            => ['image', 'mimes:jpeg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'images.*.mimes' => '画像はJPEG、PNG、WebP形式のみアップロードできます。',
            'images.*.max'   => '画像は1枚5MB以下にしてください。',
            'images.max'     => '画像は最大10枚までアップロードできます。',
            'price.min'      => '販売価格は1円以上で入力してください。',
        ];
    }
}

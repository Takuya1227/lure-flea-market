<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;

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
                'temp_image_paths'   => ['nullable', 'array', 'max:10'],
                'temp_image_paths.*' => ['string'],
                'condition'          => ['nullable', 'in:new_unopened,new_opened,like_new,good,fair,poor'],
                'shipping_cost_type' => ['nullable', 'in:included,cash_on_delivery'],
                'shipping_days'      => ['nullable', 'in:1_2_days,3_7_days,over_7_days'],
                'shipping_prefecture'=> ['nullable', 'string', 'max:10'],
                'ranges'             => ['nullable', 'array'],
                'ranges.*'           => ['in:surface,shallow,middle,deep,bottom'],
                'temp_video_path'    => ['nullable', 'string'],
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
            'ranges'              => ['nullable', 'array'],
            'ranges.*'            => ['in:surface,shallow,middle,deep,bottom'],
            'temp_video_path'     => ['nullable', 'string'],
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
            'temp_image_paths'   => ['nullable', 'array', 'max:10'],
            // パストラバーサル防止: temp/lures/ 配下かつストレージに存在するファイルのみ
            'temp_image_paths.*' => ['string', function ($attribute, $value, $fail) {
                if (!preg_match('/^temp\/lures\/[a-zA-Z0-9_\-]+\.(jpg|jpeg|png|webp)$/i', $value)) {
                    $fail('不正な画像パスです。');
                    return;
                }
                if (!Storage::disk('public')->exists($value)) {
                    $fail('画像ファイルが見つかりません。再度選択してください。');
                }
            }],
        ];
    }

    public function messages(): array
    {
        return [
            'temp_image_paths.max' => '画像は最大10枚までアップロードできます。',
            'price.min'            => '販売価格は1円以上で入力してください。',
        ];
    }
}

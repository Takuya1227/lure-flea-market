<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'nickname' => [
                'nullable',
                'string',
                'max:50',
                // 英数字・日本語・ハイフン・アンダースコアのみ許可
                'regex:/^[\p{L}\p{N}_\-]+$/u',
                Rule::unique(User::class, 'nickname')->ignore($this->user()->id),
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'bio' => ['nullable', 'string', 'max:500'],
            // セキュリティ: JPEG/PNG/WebPのみ・2MB以下・MIMEタイプも検証
            'profile_image' => [
                'nullable',
                'image',
                'mimes:jpeg,png,webp',
                'max:2048',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nickname.regex'      => 'ニックネームは文字・数字・ハイフン・アンダースコアのみ使用できます。',
            'nickname.unique'     => 'このニックネームはすでに使用されています。',
            'profile_image.image' => '画像ファイルを選択してください。',
            'profile_image.mimes' => '対応形式: JPEG・PNG・WebP',
            'profile_image.max'   => '画像は2MB以下にしてください。',
        ];
    }
}

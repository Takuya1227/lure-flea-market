<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'             => User::factory(),
            'bank_name'           => $this->faker->randomElement(['三菱UFJ銀行', 'ゆうちょ銀行', '楽天銀行', '住信SBIネット銀行']),
            'branch_name'         => $this->faker->city . '支店',
            'account_type'        => 'ordinary',
            'account_number'      => (string) $this->faker->numerify('#######'),
            'account_holder_name' => 'ヤマダ タロウ',
        ];
    }
}

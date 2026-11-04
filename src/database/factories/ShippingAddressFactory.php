<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShippingAddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'       => User::factory(),
            'name'          => $this->faker->name(),
            'postal_code'   => $this->faker->numerify('###-####'),
            'prefecture'    => '東京都',
            'city'          => '渋谷区',
            'address_line1' => $this->faker->numerify('#-#-#'),
            'address_line2' => null,
            'phone_number'  => $this->faker->numerify('090-####-####'),
            'is_default'    => false,
        ];
    }
}

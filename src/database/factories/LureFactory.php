<?php

namespace Database\Factories;

use App\Models\Lure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LureFactory extends Factory
{
    protected $model = Lure::class;

    public function definition(): array
    {
        return [
            'seller_id'           => User::factory(),
            'brand_id'            => null,
            'brand_name_custom'   => $this->faker->company(),
            'model_name'          => $this->faker->words(3, true),
            'category'            => $this->faker->randomElement(['bass', 'trout', 'salt']),
            'lure_type'           => 'minnow',
            'condition'           => $this->faker->randomElement(['new_unopened', 'like_new', 'good']),
            'is_handmade'         => false,
            'is_discontinued'     => false,
            'is_limited'          => false,
            'color_id'            => null,
            'color_name_custom'   => 'チャート',
            'size_mm'             => 100,
            'weight_g'            => 14.5,
            'material'            => 'plastic',
            'action'              => 'wobbling',
            'buoyancy'            => 'floating',
            'description'         => $this->faker->paragraph(),
            'price'               => $this->faker->numberBetween(500, 50000),
            'shipping_cost_type'  => 'included',
            'shipping_days'       => '1_2_days',
            'shipping_prefecture' => '東京都',
            'status'              => 'active',
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }

    public function suspended(): static
    {
        return $this->state(['status' => 'suspended']);
    }
}

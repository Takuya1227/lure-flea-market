<?php

namespace Database\Factories;

use App\Models\Lure;
use App\Models\ShippingAddress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        $price          = $this->faker->numberBetween(500, 50000);
        $feeRate        = 0.100;
        $feeAmount      = (int) floor($price * $feeRate);
        $sellerEarnings = $price - $feeAmount;

        return [
            'lure_id'             => Lure::factory(),
            'buyer_id'            => User::factory(),
            'seller_id'           => User::factory(),
            'shipping_address_id' => ShippingAddress::factory(),
            'price'               => $price,
            'fee_rate'            => $feeRate,
            'fee_amount'          => $feeAmount,
            'seller_earnings'     => $sellerEarnings,
            'status'              => 'paid',
            'paid_at'             => now(),
            'auto_complete_at'    => now()->addDays(14),
        ];
    }
}

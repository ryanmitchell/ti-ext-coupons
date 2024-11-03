<?php

namespace Igniter\Coupons\Database\Factories;

use Igniter\Flame\Database\Factories\Factory;

class CouponHistoryFactory extends Factory
{
    protected $model = \Igniter\Coupons\Models\CouponHistory::class;

    public function definition(): array
    {
        return [
            'coupon_id' => $this->faker->randomDigitNotNull,
            'order_id' => $this->faker->randomDigitNotNull,
            'customer_id' => $this->faker->randomDigitNotNull,
            'min_total' => $this->faker->randomFloat(2, 1, 100),
            'amount' => $this->faker->randomFloat(2, 1, 100),
            'status' => $this->faker->boolean,
        ];
    }
}

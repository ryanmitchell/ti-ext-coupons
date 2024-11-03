<?php

namespace Igniter\Cart\Tests\Models;

use Igniter\Coupons\Models\CouponHistory;
use Igniter\User\Models\Customer;

it('gets customer name attribute correctly', function() {
    $customer = Customer::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);
    $couponHistory = CouponHistory::factory()->create([
        'customer_id' => $customer->getKey(),
    ]);

    expect($couponHistory->customer_name)->toBe('Jane Doe');
});


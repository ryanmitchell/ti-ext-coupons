<?php

namespace Igniter\Cart\Tests\Models;

use Igniter\Coupons\Models\Coupon;
use Igniter\Coupons\Models\CouponHistory;
use Igniter\Coupons\Models\Scopes\CouponScope;
use Igniter\Local\Models\Concerns\Locationable;
use Igniter\Local\Models\Location;
use Igniter\System\Models\Concerns\Switchable;
use Igniter\User\Models\Customer;
use Igniter\User\Models\CustomerGroup;

it('gets recurring every attribute correctly', function() {
    $coupon = Coupon::factory()->create([
        'recurring_every' => [0, 1, 2, 3, 4, 5, 6],
    ]);

    expect($coupon->recurring_every)->toBe(['0', '1', '2', '3', '4', '5', '6']);
});

it('sets recurring every attribute correctly', function() {
    $coupon = Coupon::factory()->create([
        'recurring_every' => [0, 1, 2, 3, 4, 5, 6],
    ]);

    expect($coupon->getAttributes()['recurring_every'])->toBe('0, 1, 2, 3, 4, 5, 6');
});

it('gets type name attribute correctly', function() {
    $coupon = Coupon::factory()->create(['type' => 'P']);

    expect($coupon->type_name)->toBe(lang('igniter.coupons::default.text_percentage'));

    $coupon = Coupon::factory()->create(['type' => 'F']);

    expect($coupon->type_name)->toBe(lang('igniter.coupons::default.text_fixed_amount'));
});

it('gets formatted discount attribute correctly', function() {
    $coupon = Coupon::factory()->create(['type' => 'P', 'discount' => 10]);
    expect($coupon->formatted_discount)->toBe('10%');

    $coupon = Coupon::factory()->create(['type' => 'F', 'discount' => 10]);
    expect($coupon->formatted_discount)->toBe('10.00');
});

it('checks if coupon is fixed', function() {
    $coupon = Coupon::factory()->create(['type' => 'F']);

    expect($coupon->isFixed())->toBeTrue();
});

it('gets discount with operand', function() {
    $coupon = Coupon::factory()->create(['type' => 'F', 'discount' => 10]);

    expect($coupon->discountWithOperand())->toBe('-10');
});

it('checks if coupon is valid', function($attributes) {
    $coupon = Coupon::factory()->create($attributes);

    expect($coupon->isExpired())->toBeFalse();
})->with([
    fn() => ['validity' => 'forever'],
    fn() => [
        'validity' => 'fixed',
        'fixed_date' => now(),
        'fixed_from_time' => '00:00:00',
        'fixed_to_time' => '23:59:59',
    ],
    fn() => [
        'validity' => 'period',
        'period_start_date' => now()->subDays(2),
        'period_end_date' => now()->addDay(),
    ],
    fn() => [
        'validity' => 'recurring',
        'recurring_every' => [0, 1, 2, 3, 4, 5, 6],
        'recurring_from_time' => '00:00:00',
        'recurring_to_time' => '23:59:59',
    ],
]);

it('checks if coupon is expired', function($attributes) {
    $coupon = Coupon::factory()->create($attributes);

    expect($coupon->isExpired())->toBeTrue();
})->with([
    fn() => [
        'validity' => 'fixed',
        'fixed_date' => now()->subDay(),
        'fixed_from_time' => now()->subHours(3)->toTimeString(),
        'fixed_to_time' => now()->subHour()->toTimeString(),
    ],
    fn() => [
        'validity' => 'period',
        'period_start_date' => now()->subDays(2),
        'period_end_date' => now()->subDay(),
    ],
    fn() => [
        'validity' => 'recurring',
        'recurring_every' => [0, 1, 2, 3, 4, 5, 6],
        'recurring_from_time' => now()->subHours(3)->toTimeString(),
        'recurring_to_time' => now()->subHour()->toTimeString(),
    ],
]);

it('checks if coupon has restriction', function() {
    $coupon = Coupon::factory()->create(['order_restriction' => ['delivery']]);
    expect($coupon->hasRestriction('delivery'))->toBeFalse();
});

it('checks if coupon has location restriction', function() {
    $location = Location::factory()->create();
    $coupon = Coupon::factory()->create();
    $coupon->locations()->attach($location);

    expect($coupon->hasLocationRestriction($location->getKey()))->toBeFalse();
});

it('checks if coupon has reached max redemption', function() {
    $coupon = Coupon::factory()->create(['redemptions' => 1]);
    $coupon->history()->create(['status' => 1]);

    expect($coupon->hasReachedMaxRedemption())->toBeTrue();
});

it('checks if customer has max redemption', function() {
    $customer = Customer::factory()->create();
    $coupon = Coupon::factory()->create(['customer_redemptions' => 1]);
    $coupon->history()->create(['status' => 1, 'customer_id' => $customer->getKey()]);

    expect($coupon->customerHasMaxRedemption($customer))->toBeTrue();
});

it('checks if customer can redeem', function() {
    $customer = Customer::factory()->create();
    $coupon = Coupon::factory()->create();
    $coupon->customers()->attach($customer);

    expect($coupon->customerCanRedeem($customer))->toBeTrue();
});

it('checks if customer group can redeem', function() {
    $group = CustomerGroup::factory()->create();
    $coupon = Coupon::factory()->create();
    $coupon->customer_groups()->attach($group);

    expect($coupon->customerGroupCanRedeem($group))->toBeTrue();
});

it('applies filters on the query builder', function() {
    $query = Coupon::query()->applyFilters([
        'status' => 1,
        'sort' => 'code desc',
    ]);

    expect($query->toSql())
        ->toContain('`igniter_coupons`.`status` = ?')
        ->toContain('order by `code` desc');
});

it('configures coupon model correctly', function() {
    $coupon = new Coupon;

    expect(class_uses($coupon))
        ->toHaveKey(Locationable::class)
        ->toHaveKey(Switchable::class)
        ->and($coupon->getTable())->toBe('igniter_coupons')
        ->and($coupon->getKeyName())->toBe('coupon_id')
        ->and($coupon->timestamps)->toBeTrue()
        ->and($coupon->getGlobalScopes())->toHaveKey(CouponScope::class)
        ->and($coupon->getMorphClass())->toBe('coupons')
        ->and(new CouponHistory)->getMorphClass()->toBe('coupon_history');
});

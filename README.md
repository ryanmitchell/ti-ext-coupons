<p align="center">
    <a href="https://github.com/tastyigniter/ti-ext-coupons/actions"><img src="https://github.com/tastyigniter/ti-ext-coupons/actions/workflows/pipeline.yml/badge.svg" alt="Build Status"></a>
    <a href="https://packagist.org/packages/tastyigniter/ti-ext-coupons"><img src="https://img.shields.io/packagist/dt/tastyigniter/ti-ext-coupons" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/tastyigniter/ti-ext-coupons"><img src="https://img.shields.io/packagist/v/tastyigniter/ti-ext-coupons" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/tastyigniter/ti-ext-coupons"><img src="https://img.shields.io/packagist/l/tastyigniter/ti-ext-coupons" alt="License"></a>
</p>

## Introduction

The TastyIgniter Coupons extension allows you to offer discounts and rewards to your customers, enhancing their dining experience and boosting your sales. With a variety of coupon types and advanced usage restrictions, you can tailor your promotions to fit your business needs.

## Features

- **Fixed Amount & Percentage Coupons:** Offer flat rate discounts or percentage-based savings.
- **Advanced Usage Restrictions and Limitations:** Control how and when coupons can be used, such as limiting usage to specific days or times, or requiring a minimum order amount.
- **Location-Based Coupons:** Target specific locations with unique promotions.
- **Time-Sensitive Discounts, Seasonal Promotions:** Run limited-time offers or seasonal promotions to encourage sales during slow periods.

## Installation

You can install the extension via composer using the following command:

```bash
composer require tastyigniter/ti-ext-coupons:"^4.0" -W
```

Run the database migrations to create the required tables:
  
```bash
php artisan igniter:up
```

## Getting started

This extension provides a cart condition that allows you apply coupons to the cart. To enable the coupon condition:

- Navigate to the _Manage > Settings > Cart Settings_ admin settings page
- Switch to enable the `coupon` cart condition
- Navigate to the _Marketing > Coupons_ admin page to manage coupons

## Usage

To create a new coupon, navigate to _Marketing > Coupons_ and click the `New Coupon` button. You can configure the coupon details, such as the discount type, amount, and usage restrictions.

Once you have created a coupon, customers can apply it during checkout by entering the coupon code in the cart.

### Applying coupons to the cart

You can apply a coupon to a cart instance by using the `coupon` cart condition. Here is an example of how to apply a coupon to the cart from your frontend component:

```php
use Igniter\Cart\Facades\Cart;

$couponCondition = Cart::getCondition('coupon');

$couponCondition->setMetaData(['code' => $code]);

Cart::loadCondition($couponCondition);
```

And check if the coupon condition is valid and has been applied to the cart totals using the `isValid` method:

```php
if (Cart::getCondition('coupon')->isValid()) {
    // Do something...
}
```

You can also clear the coupon from the cart using the `removeCondition` method:

```php
Cart::removeCondition('coupon');
```

### Redeeming coupons

You may want to programmatically redeem a coupon when a customer completes an order. You can do this by using the `redeemCoupon` method on the order model:

```php
use Igniter\Cart\Models\Order;

// A new pending order is created
$order = Order::create($attributes);

// Log the coupon history using the coupon cart condition
$couponCondition = Cart::conditions()->get('coupon');
$order->logCouponHistory($couponCondition);

// Add a coupon total to the order_totals table, required for the coupon to be redeemed
$order->addOrUpdateOrderTotal([
    'code' => 'coupon',
    'title' => 'Coupon',
    'value' => -10.0,
    'priority' => 100,
    'is_summable' => true,
]);

// Redeem the coupon by setting the status on the coupon history to true
$order->redeemCoupon();
```

### Permissions

The Coupons extension registers the following permission:

- `Admin.Coupons`: Control who can manage coupons in the admin area.

For more on restricting access to the admin area, see the [TastyIgniter Permissions](https://tastyigniter.com/docs/extend/permissions) documentation.

### Events

The Coupons extension triggers the following events:

- `igniter.cart.beforeApplyCoupon`: Triggered before applying a coupon to the cart. Passes the coupon code as argument.
- `couponHistory.beforeAddHistory`: Triggered before adding a coupon history record. Passes the coupon history model, coupon cart condition object, customer model, coupon model as arguments.
- `admin.order.couponRedeemed`: Triggered after a coupon has been redeemed on an order. Passes the coupon history model as argument.

Here is an example of hooking an event in the `boot` method of an extension class:

```php
use Illuminate\Support\Facades\Event;

public function boot()
{
    Event::listen('igniter.cart.beforeApplyCoupon', function ($code) {
        // ...
    });
}
```

## Changelog

Please see [CHANGELOG](https://github.com/tastyigniter/ti-ext-coupons/blob/master/CHANGELOG.md) for more information on what has changed recently.

## Reporting issues

If you encounter a bug in this extension, please report it using the [Issue Tracker](https://github.com/tastyigniter/ti-ext-coupons/issues) on GitHub.

## Contributing

Contributions are welcome! Please read [TastyIgniter's contributing guide](https://tastyigniter.com/docs/contribution-guide).

## Security vulnerabilities

For reporting security vulnerabilities, please see our [our security policy](https://github.com/tastyigniter/ti-ext-coupons/security/policy).

## License

TastyIgniter Coupons extension is open-source software licensed under the [MIT license](https://github.com/tastyigniter/ti-ext-coupons/blob/master/LICENSE.md).

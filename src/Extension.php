<?php

namespace Igniter\Coupons;

use Igniter\Cart\Classes\CartManager;
use Igniter\Cart\Models\Order;
use Igniter\Coupons\Models\Actions\RedeemsCoupon;
use Igniter\Coupons\Models\Coupon;
use Igniter\Coupons\Models\CouponHistory;
use Igniter\Coupons\Models\Observers\CouponObserver;
use Igniter\Coupons\Models\Observers\OrderObserver;
use Igniter\Coupons\Models\Scopes\CouponScope;
use Igniter\Local\Facades\Location;
use Igniter\System\Classes\BaseExtension;
use Igniter\User\Models\Customer;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;

class Extension extends BaseExtension
{
    protected $observers = [
        Coupon::class => CouponObserver::class,
        Order::class => OrderObserver::class,
    ];

    protected array $scopes = [
        Coupon::class => CouponScope::class,
    ];

    public function boot()
    {
        Order::extend(function($model) {
            $model->relation['hasMany']['coupon_history'] = [\Igniter\Coupons\Models\CouponHistory::class];
            $model->implement[] = RedeemsCoupon::class;
        });

        Event::listen('cart.added', function() {
            Coupon::query()->isEnabled()
                ->isAutoApplicable()
                ->whereHasOrDoesntHaveLocation(Location::getId())
                ->each(function($coupon) {
                    $orderDateTime = Location::orderDateTime();
                    if (!$coupon->isExpired($orderDateTime)) {
                        resolve(CartManager::class)->applyCouponCondition($coupon->code);
                    }
                });
        });

        Event::listen('payregister.paypalexpress.extendFields', function($payment, &$fields, $order, $data) {
            if ($coupon = $order->getOrderTotals()->firstWhere('code', 'coupon')) {
                $fields['purchase_units'][0]['amount']['breakdown']['discount'] = [
                    'currency_code' => $fields['purchase_units'][0]['amount']['currency_code'],
                    'value' => number_format($coupon->value, 2, '.', ''),
                ];
            }
        });

        Event::listen('igniter.checkout.afterSaveOrder', function($order) {
            if ($couponTotal = $order->getOrderTotals()->firstWhere('code', 'coupon')) {
                $order->logCouponHistory($couponTotal);
            }
        });

        Event::listen('admin.order.paymentProcessed', function($order) {
            $order->redeemCoupon();
        });

        Customer::created(function($customer) {
            Order::where('email', $customer->email)
                ->chunk(100, function($orders) use ($customer) {
                    foreach ($orders as $order) {
                        CouponHistory::where('order_id', $order->order_id)
                            ->update(['customer_id' => $customer->customer_id]);
                    }
                });
        });

        Relation::morphMap([
            'coupon_history' => \Igniter\Coupons\Models\CouponHistory::class,
            'coupons' => \Igniter\Coupons\Models\Coupon::class,
        ]);
    }

    public function registerApiResources()
    {
        return [
            'coupons' => [
                'controller' => \Igniter\Coupons\ApiResources\Coupons::class,
                'name' => 'Coupons',
                'description' => 'An API resource for coupons',
                'actions' => [
                    'index:all', 'show:all', 'store:admin', 'update:admin', 'destroy:admin',
                ],
            ],
        ];
    }

    public function registerCartConditions()
    {
        return [
            \Igniter\Coupons\CartConditions\Coupon::class => [
                'name' => 'coupon',
                'label' => 'lang:igniter.coupons::default.text_coupon',
                'description' => 'lang:igniter.coupons::default.help_coupon_condition',
            ],
        ];
    }

    public function registerPermissions(): array
    {
        return [
            'Admin.Coupons' => [
                'label' => 'igniter.coupons::default.permissions',
                'group' => 'igniter.cart::default.text_permission_order_group',
            ],
        ];
    }

    public function registerNavigation(): array
    {
        return [
            'marketing' => [
                'child' => [
                    'coupons' => [
                        'priority' => 10,
                        'class' => 'coupons',
                        'href' => admin_url('igniter/coupons/coupons'),
                        'title' => lang('igniter.coupons::default.side_menu'),
                        'permission' => 'Admin.Coupons',
                    ],
                ],
            ],
        ];
    }
}

<?php

namespace Igniter\Coupons;

use Admin\Models\Customers_model;
use Admin\Models\Orders_model;
use Igniter\Cart\Models\Cart;
use Igniter\Coupons\Models\Coupons_history_model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use System\Classes\BaseExtension;

class Extension extends BaseExtension
{
    public function boot()
    {
        Orders_model::extend(function ($model) {
            $model->relation['hasMany']['coupon_history'] = ['Igniter\Coupons\Models\Coupons_history_model'];
            $model->implement[] = 'Igniter.Coupons.Classes.CouponOrders';
        });

        Event::listen('admin.order.beforePaymentProcessed', function ($order) {
            $order->redeemCoupon();
        });

        Event::listen('igniter.checkout.afterSaveOrder', function ($order) {
           if ($couponCondition = Cart::conditions()->get('coupon'))
               $order->logCouponHistory($couponCondition);
        });

        Customers_model::created(function ($customer) {
            Orders_model::where('email', $customer->email)
            ->get()
            ->each(function ($order) use ($customer) {
                Coupons_history_model::where('order_id', $order->order_id)
                ->update(['customer_id' => $customer->customer_id]);
            });
        });

        Relation::morphMap([
            'coupon_history' => 'Igniter\Coupons\Models\Coupons_history_model',
            'coupons' => 'Igniter\Coupons\Models\Coupons_model',
        ]);
    }

    public function registerCartConditions()
    {
        return [
            \Igniter\Coupons\CartConditions\Coupon::class => [
                'name' => 'coupon',
                'label' => 'lang:igniter.cart::default.text_coupon',
                'description' => 'lang:igniter.cart::default.help_coupon_condition',
            ],
        ];
    }

    public function registerPermissions()
    {
        return [
            'Admin.Coupons' => [
                'label' => 'igniter.coupons::default.permissions',
                'group' => 'admin::lang.permissions.name',
            ],
        ];
    }

    public function registerNavigation()
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

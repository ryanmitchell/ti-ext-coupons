<?php

namespace Igniter\Coupons\Models\Observers;

use Igniter\Cart\Models\Order;

class OrderObserver
{
    public function deleting(Order $order)
    {
        $order->coupon_history()->delete();
    }
}

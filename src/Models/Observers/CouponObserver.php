<?php

namespace Igniter\Coupons\Models\Observers;

use Igniter\Coupons\Models\Coupon;

class CouponObserver
{
    public function deleting(Coupon $coupon)
    {
        $coupon->categories()->detach();
        $coupon->menus()->detach();
    }
}

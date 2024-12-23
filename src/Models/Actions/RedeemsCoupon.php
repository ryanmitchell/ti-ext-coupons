<?php

namespace Igniter\Coupons\Models\Actions;

use Igniter\Cart\Models\Order;
use Igniter\Coupons\Models\CouponHistory;
use Igniter\System\Actions\ModelAction;

class RedeemsCoupon extends ModelAction
{
    /**
     * Redeem coupon by order
     */
    public function redeemCoupon()
    {
        if ($this->model->getOrderTotals()->keyBy('code')->get('coupon')) {
            CouponHistory::redeem($this->model->order_id);
        }
    }

    /**
     * Add cart coupon to order by order_id
     *
     * @param object $couponTotal
     *
     * @return bool
     */
    public function logCouponHistory($couponTotal)
    {
        // Make sure order model exists
        if (!$this->model->exists) {
            return false;
        }

        /** @var Order $this */
        return CouponHistory::createHistory($couponTotal, $this->model);
    }
}

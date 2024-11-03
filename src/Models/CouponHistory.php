<?php

namespace Igniter\Coupons\Models;

use Igniter\Flame\Database\Factories\HasFactory;
use Igniter\Flame\Database\Model;
use Igniter\System\Models\Concerns\Switchable;
use Igniter\User\Models\Concerns\HasCustomer;

/**
 * Coupons History Model Class
 */
class CouponHistory extends Model
{
    use HasCustomer;
    use HasFactory;
    use Switchable;

    /**
     * @var string The database table name
     */
    protected $table = 'igniter_coupons_history';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'coupon_history_id';

    public $timestamps = true;

    protected $guarded = [];

    protected $appends = ['customer_name'];

    protected $casts = [
        'coupon_history_id' => 'integer',
        'coupon_id' => 'integer',
        'order_id' => 'integer',
        'customer_id' => 'integer',
        'min_total' => 'float',
        'amount' => 'float',
        'status' => 'boolean',
    ];

    public $relation = [
        'belongsTo' => [
            'customer' => \Igniter\User\Models\Customer::class,
            'order' => \Igniter\Cart\Models\Order::class,
            'coupon' => \Igniter\Coupons\Models\Coupon::class,
        ],
    ];

    protected array $queryModifierFilters = [
        'redeemed' => ['applyRedeemed', 'default' => true],
        'customer' => 'applyCustomer',
        'order_id' => 'whereOrderId',
    ];

    protected array $queryModifierSorts = [
        'created_at desc' => true, 'created_at asc',
    ];

    public static function redeem($orderId)
    {
        if (!$couponHistory = static::query()->orderBy('created_at', 'desc')->firstWhere('order_id', $orderId)) {
            return false;
        }

        $couponHistory->update([
            'status' => 1,
            'created_at' => now(),
        ]);

        static::query()->where('order_id', $orderId)
            ->where('coupon_history_id', '<>', $couponHistory->coupon_history_id)
            ->delete();

        $couponHistory->fireSystemEvent('admin.order.couponRedeemed');
    }

    public function getCustomerNameAttribute($value)
    {
        return ($this->customer && $this->customer->exists) ? $this->customer->full_name : $value;
    }

    public function scopeApplyRedeemed($query)
    {
        return $query->where('status', '>=', 1);
    }

    public function touchStatus()
    {
        $this->status = ($this->status < 1) ? 1 : 0;

        return $this->save();
    }

    /**
     * @param object $couponTotal
     * @param \Igniter\Cart\Models\Order $order
     * @return \Igniter\Coupons\Models\CouponHistory|bool
     */
    public static function createHistory($couponTotal, $order)
    {
        if ($couponTotal->code === 'coupon' && str_contains($couponTotal->title, '[')) {
            $couponTotal->code = str_after(str_before($couponTotal->title, ']'), '[');
        }

        if (!$coupon = Coupon::firstWhere('code', $couponTotal->code)) {
            return false;
        }

        $model = new static;
        $model->order_id = $order->getKey();
        $model->customer_id = $order->customer ? $order->customer->getKey() : null;
        $model->coupon_id = $coupon->coupon_id;
        $model->code = $coupon->code;
        $model->amount = $couponTotal->value;
        $model->min_total = $coupon->min_total;

        if ($model->fireSystemEvent('couponHistory.beforeAddHistory', [$couponTotal, $order->customer, $coupon]) === false) {
            return false;
        }

        $model->save();

        return $model;
    }
}

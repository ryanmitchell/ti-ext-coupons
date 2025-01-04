<?php

namespace Igniter\Coupons\Models;

use Carbon\Carbon;
use Igniter\Flame\Database\Factories\HasFactory;
use Igniter\Flame\Database\Model;
use Igniter\Local\Models\Concerns\Locationable;
use Igniter\System\Models\Concerns\Switchable;
use Igniter\User\Models\Customer;
use Igniter\User\Models\CustomerGroup;

/**
 * Coupons Model Class
 *
 * @property int $coupon_id
 * @property string $name
 * @property string $code
 * @property string $type
 * @property float|null $discount
 * @property float|null $min_total
 * @property int $redemptions
 * @property int $customer_redemptions
 * @property string|null $description
 * @property bool|null $status
 * @property \Illuminate\Support\Carbon $created_at
 * @property string|null $validity
 * @property \Illuminate\Support\Carbon|null $fixed_date
 * @property mixed|null $fixed_from_time
 * @property mixed|null $fixed_to_time
 * @property \Illuminate\Support\Carbon|null $period_start_date
 * @property \Illuminate\Support\Carbon|null $period_end_date
 * @property string|null $recurring_every
 * @property mixed|null $recurring_from_time
 * @property mixed|null $recurring_to_time
 * @property array|null $order_restriction
 * @property string $apply_coupon_on
 * @property bool $auto_apply
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read mixed $formatted_discount
 * @property-read mixed $type_name
 * @mixin \Igniter\Flame\Database\Model
 */
class Coupon extends Model
{
    use HasFactory;
    use Locationable;
    use Switchable;

    const LOCATIONABLE_RELATION = 'locations';

    /**
     * @var string The database table name
     */
    protected $table = 'igniter_coupons';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'coupon_id';

    protected $timeFormat = 'H:i';

    public $timestamps = true;

    protected $casts = [
        'discount' => 'float',
        'min_total' => 'float',
        'redemptions' => 'integer',
        'customer_redemptions' => 'integer',
        'status' => 'boolean',
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'fixed_date' => 'date',
        'fixed_from_time' => 'time',
        'fixed_to_time' => 'time',
        'recurring_from_time' => 'time',
        'recurring_to_time' => 'time',
        'order_restriction' => 'array',
        'auto_apply' => 'boolean',
    ];

    public $relation = [
        'belongsToMany' => [
            'categories' => [\Igniter\Cart\Models\Category::class, 'table' => 'igniter_coupon_categories'],
            'menus' => [\Igniter\Cart\Models\Menu::class, 'table' => 'igniter_coupon_menus'],
            'customers' => [\Igniter\User\Models\Customer::class, 'table' => 'igniter_coupon_customers'],
            'customer_groups' => [\Igniter\User\Models\CustomerGroup::class, 'table' => 'igniter_coupon_customer_groups'],
        ],
        'hasMany' => [
            'history' => \Igniter\Coupons\Models\CouponHistory::class,
        ],
        'morphToMany' => [
            'locations' => [\Igniter\Local\Models\Location::class, 'name' => 'locationable'],
        ],
    ];

    protected array $queryModifierFilters = [
        'enabled' => ['applySwitchable', 'default' => true],
    ];

    protected array $queryModifierSorts = [
        'name desc', 'name asc',
        'coupon_id desc', 'coupon_id asc',
        'code desc', 'code asc',
    ];

    public function getRecurringEveryOptions()
    {
        return ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    }

    public static function getDropdownOptions()
    {
        return static::whereIsEnabled()->dropdown('name');
    }

    //
    // Accessors & Mutators
    //

    public function getRecurringEveryAttribute($value)
    {
        return empty($value) ? [0, 1, 2, 3, 4, 5, 6] : explode(', ', $value);
    }

    public function setRecurringEveryAttribute($value)
    {
        $this->attributes['recurring_every'] = empty($value)
            ? null : implode(', ', $value);
    }

    public function getTypeNameAttribute($value)
    {
        return ($this->type == 'P') ? lang('igniter.coupons::default.text_percentage') : lang('igniter.coupons::default.text_fixed_amount');
    }

    public function getFormattedDiscountAttribute($value)
    {
        return ($this->type == 'P') ? round($this->discount).'%' : number_format($this->discount, 2);
    }

    //
    // Events
    //

    /**
     * Create new or update existing menu categories
     *
     * @param array $categoryIds if empty all existing records will be deleted
     *
     * @return bool
     */
    public function addMenuCategories(array $categoryIds = [])
    {
        $this->categories()->sync($categoryIds);
    }

    /**
     * Create new or update existing menus
     *
     * @param array $menuIds if empty all existing records will be deleted
     *
     * @return bool
     */
    public function addMenus(array $menuIds = [])
    {
        $this->menus()->sync($menuIds);
    }

    //
    // Helpers
    //

    public function isFixed()
    {
        return $this->type == 'F';
    }

    public function discountWithOperand()
    {
        return ($this->isFixed() ? '-' : '-%').$this->discount;
    }

    public function minimumOrderTotal()
    {
        return $this->min_total ?: 0;
    }

    /**
     * Check if a coupone is expired
     *
     * @param \Carbon\Carbon $orderDateTime orderDateTime
     *
     * @return bool
     */
    public function isExpired($orderDateTime = null)
    {
        if (is_null($orderDateTime)) {
            $orderDateTime = Carbon::now();
        }

        switch ($this->validity) {
            case 'forever':
                return false;
            case 'fixed':
                $start = $this->fixed_date->copy()->setTimeFromTimeString($this->fixed_from_time);
                $end = $this->fixed_date->copy()->setTimeFromTimeString($this->fixed_to_time);

                if ($start->gt($end)) {
                    $end->addDay();
                }

                return !$orderDateTime->between($start, $end);
            case 'period':
                return !$orderDateTime->between($this->period_start_date, $this->period_end_date);
            case 'recurring':
                if (!in_array($orderDateTime->format('w'), $this->recurring_every)) {
                    return true;
                }

                $start = $orderDateTime->copy()->setTimeFromTimeString($this->recurring_from_time);
                $end = $orderDateTime->copy()->setTimeFromTimeString($this->recurring_to_time);

                if ($start->gt($end)) {
                    $end->addDay();
                }

                return !$orderDateTime->between($start, $end);
        }

        if ($result = $this->fireSystemEvent('igniter.coupon.isExpired', [$orderDateTime])) {
            return $result;
        }

        return false;
    }

    public function hasRestriction($orderType)
    {
        if (empty($this->order_restriction)) {
            return false;
        }

        return !in_array($orderType, $this->order_restriction);
    }

    public function hasLocationRestriction($locationId)
    {
        if (!$this->locations || $this->locations->isEmpty()) {
            return false;
        }

        $locationKeyColumn = $this->locations()->getModel()->qualifyColumn('location_id');

        return !$this->locations()->where($locationKeyColumn, $locationId)->exists();
    }

    public function hasReachedMaxRedemption()
    {
        return $this->redemptions && $this->redemptions <= $this->countRedemptions();
    }

    public function customerHasMaxRedemption(Customer $customer)
    {
        return $this->customer_redemptions && $this->customer_redemptions <= $this->countCustomerRedemptions($customer->getKey());
    }

    public function customerCanRedeem(?Customer $customer = null)
    {
        if (!$this->customers || $this->customers->isEmpty()) {
            return true;
        }

        return $customer && $this->customers->contains('customer_id', $customer->getKey());
    }

    public function customerGroupCanRedeem(?CustomerGroup $group = null)
    {
        if (!$this->customer_groups || $this->customer_groups->isEmpty()) {
            return true;
        }

        return $group && $this->customer_groups->contains('customer_group_id', $group->getKey());
    }

    public function countRedemptions()
    {
        return $this->history()->whereIsEnabled()->count();
    }

    public function countCustomerRedemptions($id)
    {
        return $this->history()->isEnabled()
            ->where('customer_id', $id)->count();
    }

    public function appliesOnWholeCart()
    {
        return $this->apply_coupon_on == 'whole_cart';
    }

    public function appliesOnMenuItems()
    {
        return $this->apply_coupon_on == 'menu_items';
    }

    public function appliesOnDelivery()
    {
        return $this->apply_coupon_on == 'delivery_fee';
    }

    public static function getByCode($code)
    {
        return self::whereIsEnabled()->whereCode($code)->first();
    }

    public static function getByCodeAndLocation($code, $locationId)
    {
        return self::whereIsEnabled()->whereCodeAndLocation($code, $locationId)->first();
    }
}

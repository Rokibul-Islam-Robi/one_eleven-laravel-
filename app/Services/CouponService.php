<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Throwable;

/** Coupon validation. Ported from validate_coupon() in includes/checkout.php. */
class CouponService
{
    /** Returns [ok, discount, message, couponRow]. Discount is in currency units. */
    public static function validate(string $code, float $subtotal): array
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return [true, 0.0, '', null];
        }

        try {
            $row = DB::table('coupons')->where('code', $code)->first();
            if (! $row) {
                return [false, 0.0, 'Coupon code is not valid.', null];
            }
            $c = (array) $row;
            if (! (int) $c['active']) {
                return [false, 0.0, 'This coupon is no longer active.', null];
            }

            $now = date('Y-m-d H:i:s');
            if (! empty($c['starts_at']) && $now < $c['starts_at']) {
                return [false, 0.0, 'This coupon is not active yet.', null];
            }
            if (! empty($c['ends_at']) && $now > $c['ends_at']) {
                return [false, 0.0, 'This coupon has expired.', null];
            }
            if ((int) $c['max_uses'] > 0 && (int) $c['used_count'] >= (int) $c['max_uses']) {
                return [false, 0.0, 'This coupon has reached its usage limit.', null];
            }
            $min = (float) $c['min_order'];
            if ($min > 0 && $subtotal < $min) {
                return [false, 0.0, 'Minimum order for this coupon is ৳' . number_format($min) . '.', null];
            }

            if ($c['discount_type'] === 'fixed') {
                $discount = min((float) $c['discount_percent'], $subtotal);
            } else {
                $discount = round(($subtotal * (float) $c['discount_percent']) / 100, 2);
                $discount = min($discount, $subtotal);
            }
            return [true, max(0.0, $discount), '', $c];
        } catch (Throwable $e) {
            return [false, 0.0, 'Error validating coupon.', null];
        }
    }
}

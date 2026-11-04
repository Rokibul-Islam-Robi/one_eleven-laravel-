<?php

namespace App\Services;

use App\Services\Payments\PaymentConfig;
use App\Support\Catalog;
use App\Support\Districts;
use App\Support\CustomerAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Checkout engine — server-side validation + single-transaction order persistence.
 * Ported from includes/checkout.php: every price, discount, shipping fee and stock
 * level is re-checked here; nothing from the client is trusted.
 */
class CheckoutService
{
    /** @return array<string,string> field => message */
    public static function errors(array $post): array
    {
        $errs = [];
        $name = trim((string) ($post['name'] ?? ''));
        $phone = trim((string) ($post['phone'] ?? ''));
        $address = trim((string) ($post['address'] ?? ''));
        $email = trim((string) ($post['email'] ?? ''));
        if ($name === '' || mb_strlen($name) > 120) {
            $errs['name'] = 'Full name is required (max 120 chars).';
        }
        if (! preg_match('/^(?:\+?880|0)1[3-9][0-9]{8}$/', preg_replace('/[\s\-]/', '', $phone))) {
            $errs['phone'] = 'Enter a valid Bangladeshi mobile number (e.g. 01712345678).';
        }
        if ($address === '' || mb_strlen($address) > 500) {
            $errs['address'] = 'Delivery address is required (max 500 chars).';
        }
        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errs['email'] = 'Enter a valid email address.';
        }
        /* District must be one of the 64 official districts (the street address stays a separate field). */
        if (Districts::canonical((string) ($post['area'] ?? '')) === '') {
            $errs['area'] = 'Select your district from the list.';
        }
        if (! PaymentConfig::known((string) ($post['payment_method'] ?? 'cod'))) {
            $errs['payment_method'] = 'Choose Cash on Delivery, SSLCommerz, bKash or Nagad.';
        }
        return $errs;
    }

    /** Stock available for a JSON catalogue id (via the catalog_stock mapping). */
    public static function stockAvailable(int $jsonId): int
    {
        try {
            $row = DB::table('catalog_stock')->where('json_id', $jsonId)->first();
            return $row ? (int) $row->stock : 0;
        } catch (Throwable $e) {
            return 999;
        }
    }

    /** Whether a JSON catalogue item is prescription-gated (admin flag via catalog_stock). */
    public static function requiresPrescription(int $jsonId): bool
    {
        try {
            $row = DB::table('catalog_stock')->where('json_id', $jsonId)->first();
            return $row && (int) $row->requires_prescription === 1;
        } catch (Throwable $e) {
            return false;
        }
    }

    /** Resolve the session cart for checkout. */
    public static function resolveCart(): array
    {
        $lines = [];
        $errors = [];
        $subtotal = 0.0;
        $anyRx = false;
        $wantQty = [];

        $cart = session('cart');
        if (! is_array($cart)) {
            return ['lines' => [], 'errors' => [], 'subtotal' => 0.0, 'any_rx' => false, 'count' => 0];
        }

        foreach ($cart as $cartKey => $qty) {
            $qty = (int) $qty;
            if ($qty <= 0) {
                continue;
            }

            /* Cart keys may be "12" or "12#1000mg#2" (id#potency#pack). */
            $parts = Catalog::keyParts((string) $cartKey);
            $pid = (int) $parts['id'];

            $p = Catalog::byId((string) $pid); /* JSON catalogue — price + name */
            if (! $p) {
                $errors[] = 'A product in your cart is no longer available.';
                continue;
            }

            /* Variants of the same product share one stock row — sum first. */
            $wantQty[$pid] = ($wantQty[$pid] ?? 0) + $qty;

            $price = Catalog::variantUnitPrice($p, $parts['potency'], $parts['pack']);

            $line = round($price * $qty, 2);
            $subtotal += $line;
            $rx = self::requiresPrescription($pid);
            if ($rx) {
                $anyRx = true;
            }
            $lines[] = [
                'product_id'    => $pid, /* JSON catalogue id — what the storefront sells */
                'name'          => (string) ($p['n'] ?? 'Product'),
                'variant_label' => Catalog::variantLabel($parts['potency'], $parts['pack']),
                'image_url'     => (string) ($p['i'] ?? ''),
                'price'         => $price,
                'qty'           => $qty,
                'line'          => $line,
                'rx'            => $rx,
            ];
        }

        /* Enforce stock once per product (covers multi-variant carts). */
        foreach ($wantQty as $pid => $want) {
            $stock = self::stockAvailable($pid);
            if ($stock < 0) {
                $stock = 0;
            }
            if ($stock < $want) {
                $name = Catalog::byId((string) $pid)['n'] ?? ('#' . $pid);
                $errors[] = $name . ' has only ' . $stock . ' left in stock.';
            }
        }

        return [
            'lines'    => $lines,
            'errors'   => $errors,
            'subtotal' => round($subtotal, 2),
            'any_rx'   => $anyRx,
            'count'    => array_sum(array_column($lines, 'qty')),
        ];
    }

    /**
     * Create an order — single transaction, duplicate-safe.
     * @return array ['ok'=>bool, 'error'=>string] or the order summary.
     */
    public static function createOrder(array $post): array
    {
        $errs = self::errors($post);
        if ($errs) {
            return ['ok' => false, 'error' => implode(' ', $errs)];
        }

        $cart = self::resolveCart();
        if ($cart['errors']) {
            return ['ok' => false, 'error' => implode(' ', $cart['errors'])];
        }
        if (! $cart['lines']) {
            return ['ok' => false, 'error' => 'Your cart is empty.'];
        }

        $paymentMethod = PaymentConfig::normalize((string) ($post['payment_method'] ?? 'cod'));
        $courierId = (int) ($post['courier_id'] ?? 0);
        $courier = $courierId > 0 ? CourierService::byId($courierId) : CourierService::default();
        if (! $courier) {
            return ['ok' => false, 'error' => 'No delivery option is available right now.'];
        }

        /* coupon */
        $couponCode = strtoupper(trim((string) ($post['coupon_code'] ?? '')));
        [$cOk, $discount, $cMsg, $couponRow] = CouponService::validate($couponCode, $cart['subtotal']);
        if (! $cOk) {
            return ['ok' => false, 'error' => $cMsg];
        }

        $quote = CourierService::quote($courier, $cart['subtotal'] - $discount, $paymentMethod);
        $shipping = $quote['shipping'];
        $codFee = $quote['cod_fee'];
        $total = round($cart['subtotal'] - $discount + $shipping + $codFee, 2);
        if ($total < 0) {
            $total = 0.0;
        }

        $area = Districts::canonical((string) ($post['area'] ?? ''));
        $customer = [
            'name'     => trim((string) $post['name']),
            'phone'    => trim((string) $post['phone']),
            'email'    => trim((string) ($post['email'] ?? '')),
            'address'  => trim((string) $post['address']),
            'area'     => $area,
            'division' => Districts::divisionOf($area),
            'note'     => trim((string) ($post['note'] ?? '')),
        ];
        $rxId = (int) ($post['prescription_id'] ?? 0) ?: null;
        $ip = substr((string) request()->server('REMOTE_ADDR', ''), 0, 40);

        /* idempotency — same cart fingerprint + same minute = same key (blocks double-submit) */
        $fingerprint = sha1(
            json_encode(session('cart', [])) . '|'
            . $customer['phone'] . '|'
            . $ip . '|'
            . date('YmdHi')
        );

        try {
            $existing = DB::table('orders')->where('idempotency_key', $fingerprint)
                ->first(['id', 'order_number', 'payment_status', 'total', 'shipping']);
            if ($existing) {
                if ((string) $existing->payment_status !== 'failed') {
                    return [
                        'ok' => true, 'order_id' => (int) $existing->id,
                        'order_number' => (string) $existing->order_number,
                        'customer' => $customer, 'payment_method' => $paymentMethod,
                        'total' => (float) $existing->total, 'shipping' => (float) $existing->shipping,
                    ];
                }
                $fingerprint = sha1($fingerprint . '|retry|' . bin2hex(random_bytes(4)));
            }

            $status = $cart['any_rx'] ? 'awaiting_prescription' : 'pending';

            DB::beginTransaction();

            /* lock catalog stock rows */
            $pids = array_values(array_unique(array_column($cart['lines'], 'product_id')));
            if ($pids) {
                $stockMap = DB::table('catalog_stock')->whereIn('json_id', $pids)->lockForUpdate()
                    ->pluck('stock', 'json_id')->map(fn ($v) => (int) $v)->all();
                $want = [];
                foreach ($cart['lines'] as $ln) {
                    $want[$ln['product_id']] = ($want[$ln['product_id']] ?? 0) + $ln['qty'];
                }
                foreach ($want as $pid => $need) {
                    if (($stockMap[$pid] ?? 0) < $need) {
                        DB::rollBack();
                        $n = Catalog::byId((string) $pid)['n'] ?? ('#' . $pid);
                        return ['ok' => false, 'error' => 'Not enough stock for "' . $n . '" — someone else may have bought it first.'];
                    }
                }
            }

            /* insert order */
            $orderNumber = 'BH' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
            $order = [
                'order_number'          => $orderNumber,
                'user_id'               => null,
                'subtotal'              => $cart['subtotal'],
                'shipping'              => $shipping,
                'cod_fee'               => $codFee,
                'discount_amount'       => $discount,
                'total'                 => $total,
                'status'                => $status,
                'payment_method'        => $paymentMethod === 'cod' ? 'cash_on_delivery' : $paymentMethod,
                'payment_status'        => 'unpaid',
                'courier_id'            => (int) ($courier['id'] ?? 0),
                'courier_code'          => (string) ($courier['code'] ?? 'default'),
                'tracking_code'         => null,
                'shipping_name'         => $customer['name'],
                'shipping_phone'        => $customer['phone'],
                'shipping_address'      => $customer['address'],
                'shipping_area'         => $customer['area'],
                'customer_email'        => $customer['email'],
                'customer_ip'           => substr((string) request()->server('REMOTE_ADDR', ''), 0, 45),
                'coupon_id'             => $couponRow ? (int) $couponRow['id'] : null,
                'coupon_code'           => $couponRow ? (string) $couponRow['code'] : null,
                'requires_prescription' => $cart['any_rx'] ? 1 : 0,
                'prescription_id'       => $rxId,
                'idempotency_key'       => $fingerprint,
                'created_at'            => date('Y-m-d H:i:s'),
            ];
            if (Schema::hasColumn('orders', 'store_customer_id')) {
                $order['store_customer_id'] = CustomerAuth::current()['id'] ?? null;
            }
            $orderId = (int) DB::table('orders')->insertGetId($order);

            /* items + stock deduction */
            foreach ($cart['lines'] as $ln) {
                $orderItemId = (int) DB::table('order_items')->insertGetId([
                    'order_id'      => $orderId,
                    'product_id'    => $ln['product_id'],
                    'product_name'  => $ln['name'],
                    'variant_label' => $ln['variant_label'],
                    'image_url'     => $ln['image_url'],
                    'price'         => $ln['price'],
                    'qty'           => $ln['qty'],
                ]);
                $dec = DB::table('catalog_stock')->where('json_id', $ln['product_id'])
                    ->where('stock', '>=', $ln['qty'])
                    ->update(['stock' => DB::raw('stock - ' . (int) $ln['qty'])]);
                if ($dec === 0) {
                    DB::rollBack();
                    return ['ok' => false, 'error' => 'Stock changed for "' . $ln['name'] . '" — please retry.'];
                }
                $sellerProduct = DB::table('products')->where('json_id', $ln['product_id'])
                    ->whereNotNull('seller_id')->lockForUpdate()->first(['id', 'seller_id', 'stock']);
                if ($sellerProduct) {
                    if ((int) $sellerProduct->stock < (int) $ln['qty']) {
                        DB::rollBack();
                        return ['ok' => false, 'error' => 'Stock changed for "' . $ln['name'] . '" — please retry.'];
                    }
                    DB::table('products')->where('id', $sellerProduct->id)
                        ->update(['stock' => DB::raw('stock - ' . (int) $ln['qty'])]);

                    DB::table('seller_order_items')->insert([
                        'order_id' => $orderId,
                        'order_item_id' => $orderItemId,
                        'seller_id' => (int) $sellerProduct->seller_id,
                        'product_id' => (int) $sellerProduct->id,
                        'product_name' => $ln['name'],
                        'qty' => (int) $ln['qty'],
                        'unit_price' => $ln['price'],
                        'line_total' => round((float) $ln['price'] * (int) $ln['qty'], 2),
                        'commission_rate' => null,
                        'commission_amount' => null,
                        'seller_earning' => null,
                        'status' => 'pending',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
                DB::table('stock_movements')->insert([
                    'product_id' => $ln['product_id'],
                    'order_id'   => $orderId,
                    'delta'      => -$ln['qty'],
                    'type'       => 'order_hold',
                    'note'       => 'Checkout order ' . $orderNumber,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            /* consume coupon usage */
            if ($couponRow) {
                $upd = DB::table('coupons')->where('id', (int) $couponRow['id'])
                    ->where(function ($q) {
                        $q->where('max_uses', 0)->orWhereColumn('used_count', '<', 'max_uses');
                    })
                    ->update(['used_count' => DB::raw('used_count + 1')]);
                if ($upd === 0) {
                    DB::rollBack();
                    return ['ok' => false, 'error' => 'This coupon just reached its usage limit — please remove it.'];
                }
            }

            if ($rxId) {
                DB::table('prescriptions')->where('id', $rxId)->update(['order_id' => $orderId]);
            }

            DB::commit();
            return [
                'ok' => true, 'order_id' => $orderId, 'order_number' => $orderNumber,
                'customer' => $customer, 'payment_method' => $paymentMethod,
                'total' => $total, 'shipping' => $shipping, 'cod_fee' => $codFee,
                'discount' => $discount, 'subtotal' => $cart['subtotal'],
                'courier' => $courier, 'coupon_code' => $couponRow ? (string) $couponRow['code'] : '',
                'requires_prescription' => $cart['any_rx'],
            ];
        } catch (Throwable $ex) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            report($ex);
            return ['ok' => false, 'error' => 'We could not save your order. Please try again.'];
        }
    }
}

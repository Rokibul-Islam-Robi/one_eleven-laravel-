<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/** Order lookups, payment-state writes, stock release and return requests. Ported from includes/checkout.php. */
class OrderService
{
    public static function byNumber(string $orderNumber): ?array
    {
        try {
            $o = DB::table('orders')->where('order_number', $orderNumber)->first();
            return $o ? (array) $o : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public static function byGatewayRef(string $reference): ?array
    {
        $reference = trim($reference);
        if ($reference === '') {
            return null;
        }
        try {
            $o = DB::table('orders')->where('val_id', $reference)->orderByDesc('id')->first();
            return $o ? (array) $o : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /** Gateway accepted the session: unpaid -> pending (+ reference / method / demo flag). */
    public static function markPending(string $orderNumber, array $gateway = []): bool
    {
        $o = self::byNumber($orderNumber);
        if (! $o) {
            return false;
        }
        try {
            return DB::transaction(function () use ($o, $gateway): bool {
                $order = DB::table('orders')->where('id', (int) $o['id'])
                    ->lockForUpdate()->first(['id', 'status', 'payment_status']);
                if (! $order) {
                    return false;
                }
                if ((string) $order->payment_status === 'paid') {
                    return true;
                }
                if (! in_array((string) $order->payment_status, ['unpaid', 'pending'], true)
                    || ! in_array((string) $order->status, ['pending', 'awaiting_prescription', 'processing'], true)) {
                    return false;
                }
                if (DB::table('stock_movements')->where('order_id', (int) $order->id)
                    ->whereIn('type', ['order_release', 'order_return', 'order_cancelled'])->exists()) {
                    return false;
                }

                $upd = ['payment_status' => 'pending'];
                self::keepOrSet($upd, 'val_id', mb_substr((string) ($gateway['val_id'] ?? ''), 0, 100));
                self::keepOrSet($upd, 'bank_tran_id', mb_substr((string) ($gateway['bank_tran_id'] ?? ''), 0, 100));
                self::keepOrSet($upd, 'card_type', mb_substr((string) ($gateway['card_type'] ?? ''), 0, 40));
                self::keepOrSet($upd, 'payment_method', ! empty($gateway['payment_method']) ? (string) $gateway['payment_method'] : '');
                if (! empty($gateway['demo'])) {
                    $upd['demo_payment'] = 1;
                }
                DB::table('orders')->where('id', (int) $order->id)->update($upd);

                return true;
            });
        } catch (Throwable $ex) {
            report($ex);
            return false;
        }
    }

    /** COALESCE(NULLIF(value,''), column): only overwrite when a non-empty value arrives. */
    private static function keepOrSet(array &$upd, string $col, string $value): void
    {
        if ($value !== '') {
            $upd[$col] = $value;
        }
    }

    public static function markPaid(string $orderNumber, array $gateway = []): bool
    {
        $o = self::byNumber($orderNumber);
        if (! $o) {
            return false;
        }
        try {
            return DB::transaction(function () use ($o, $gateway): bool {
                $order = DB::table('orders')->where('id', (int) $o['id'])
                    ->lockForUpdate()->first(['id', 'status', 'payment_status']);
                if (! $order) {
                    return false;
                }
                if ((string) $order->payment_status === 'paid') {
                    return true;
                }

                if ((string) $order->payment_status === 'refunded') {
                    return false;
                }

                $stockWasReleased = DB::table('stock_movements')
                    ->where('order_id', (int) $order->id)
                    ->whereIn('type', ['order_release', 'order_return', 'order_cancelled'])
                    ->exists();
                $orderIsClosed = in_array((string) $order->status, ['cancelled', 'returned'], true);
                $upd = [
                    'payment_status' => 'paid',
                    'val_id'         => mb_substr((string) ($gateway['val_id'] ?? ''), 0, 100),
                    'bank_tran_id'   => mb_substr((string) ($gateway['bank_tran_id'] ?? ''), 0, 100),
                    'card_type'      => mb_substr((string) ($gateway['card_type'] ?? ''), 0, 40),
                    'demo_payment'   => ! empty($gateway['demo']) ? 1 : 0,
                ];
                self::keepOrSet($upd, 'payment_method', ! empty($gateway['payment_method']) ? (string) $gateway['payment_method'] : '');

                if ($orderIsClosed || $stockWasReleased) {
                    $upd['failed_reason'] = 'Payment was confirmed after order closure; administrator refund review is required.';
                    DB::table('orders')->where('id', (int) $order->id)->update($upd);

                    return true;
                }

                if (! in_array((string) $order->status, ['pending', 'awaiting_prescription', 'processing'], true)) {
                    return false;
                }

                $upd['status'] = 'processing';
                DB::table('orders')->where('id', (int) $o['id'])->update($upd);
                self::confirmSellerOrderItems((int) $o['id']);

                return true;
            });
        } catch (Throwable $ex) {
            report($ex);
            return false;
        }
    }

    public static function confirmSellerOrderItems(int $orderId): void
    {
        DB::transaction(function () use ($orderId): void {
            $order = DB::table('orders')->where('id', $orderId)
                ->lockForUpdate()->first(['status']);
            if (! $order || (string) $order->status !== 'processing') {
                return;
            }

            DB::table('seller_order_items')->where('order_id', $orderId)
                ->where('status', 'pending')
                ->update(['status' => 'confirmed', 'updated_at' => now()]);
        });
    }

    public static function markFailed(string $orderNumber, string $reason = ''): bool
    {
        $o = self::byNumber($orderNumber);
        if (! $o) {
            return false;
        }
        try {
            return DB::transaction(function () use ($o, $reason): bool {
                $order = DB::table('orders')->where('id', (int) $o['id'])
                    ->lockForUpdate()->first(['id', 'status', 'payment_status']);
                if (! $order || in_array((string) $order->payment_status, ['paid', 'refunded'], true)) {
                    return false;
                }

                if (! in_array((string) $order->status, ['pending', 'awaiting_prescription', 'processing', 'cancelled'], true)) {
                    return false;
                }

                DB::table('orders')->where('id', (int) $order->id)->update([
                    'status' => 'cancelled',
                    'payment_status' => 'failed',
                    'failed_reason' => mb_substr($reason, 0, 2000),
                ]);

                if (! self::releaseStock((int) $order->id, 'payment_failed')) {
                    throw new RuntimeException('Payment-failed order stock could not be released.');
                }

                return true;
            });
        } catch (Throwable $ex) {
            report($ex);
            return false;
        }
    }

    public static function expirePendingPayments(bool $dryRun = false): int
    {
        $query = DB::table('orders')
            ->where('payment_status', 'pending')
            ->whereIn('payment_method', ['sslcommerz', 'bkash', 'nagad'])
            ->whereIn('status', ['pending', 'processing'])
            ->where('created_at', '<=', now()->subMinutes(30));

        if ($dryRun) {
            return $query->count();
        }

        $expired = 0;
        $query->orderBy('id')->chunkById(100, function ($orders) use (&$expired): void {
            foreach ($orders as $order) {
                if (self::expirePendingOnlineOrder((int) $order->id)) {
                    $expired++;
                }
            }
        });

        return $expired;
    }

    private static function expirePendingOnlineOrder(int $orderId): bool
    {
        try {
            return DB::transaction(function () use ($orderId): bool {
                $order = DB::table('orders')
                    ->where('id', $orderId)
                    ->where('payment_status', 'pending')
                    ->whereIn('payment_method', ['sslcommerz', 'bkash', 'nagad'])
                    ->whereIn('status', ['pending', 'processing'])
                    ->where('created_at', '<=', now()->subMinutes(30))
                    ->lockForUpdate()
                    ->first(['id']);

                if (! $order) {
                    return false;
                }

                DB::table('orders')->where('id', $orderId)->update([
                    'status' => 'cancelled',
                    'payment_status' => 'failed',
                    'failed_reason' => 'Online payment session expired after 30 minutes.',
                ]);

                if (! self::releaseStock($orderId, 'payment_failed')) {
                    throw new RuntimeException('Expired online payment stock could not be released.');
                }

                return true;
            });
        } catch (Throwable $ex) {
            report($ex);

            return false;
        }
    }

    /** Hand reserved stock + the coupon use back when an order is cancelled / payment fails. */
    public static function releaseStock(int $orderId, string $reason = 'order_cancelled'): bool
    {
        try {
            DB::beginTransaction();
            $order = DB::table('orders')->where('id', $orderId)->lockForUpdate()->first(['id']);
            if (! $order) {
                DB::rollBack();
                return false;
            }

            $done = DB::table('stock_movements')->where('order_id', $orderId)
                ->whereIn('type', ['order_release', 'order_return', 'order_cancelled'])->count();
            if ($done > 0) {
                self::closeSellerOrderItems($orderId, $reason);
                DB::commit();
                return true;
            }
            $rows = DB::table('order_items')->where('order_id', $orderId)->lockForUpdate()->get(['product_id', 'qty']);
            foreach ($rows as $r) {
                DB::table('products')->where('json_id', (int) $r->product_id)
                    ->whereNotNull('seller_id')
                    ->increment('stock', (int) $r->qty);
                DB::table('catalog_stock')->where('json_id', (int) $r->product_id)
                    ->update(['stock' => DB::raw('stock + ' . (int) $r->qty)]);
                DB::table('stock_movements')->insert([
                    'product_id' => (int) $r->product_id,
                    'order_id'   => $orderId,
                    'delta'      => (int) $r->qty,
                    'type'       => 'order_release',
                    'note'       => $reason,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
            $couponId = DB::table('orders')->where('id', $orderId)->value('coupon_id');
            if ($couponId) {
                DB::table('coupons')->where('id', $couponId)->where('used_count', '>', 0)
                    ->update(['used_count' => DB::raw('used_count - 1')]);
            }
            self::closeSellerOrderItems($orderId, $reason);
            DB::commit();
            return true;
        } catch (Throwable $ex) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            report($ex);
            return false;
        }
    }

    public static function cancel(int $orderId, string $reason = ''): bool
    {
        try {
            return DB::transaction(function () use ($orderId, $reason): bool {
                $order = DB::table('orders')->where('id', $orderId)
                    ->lockForUpdate()->first(['status', 'payment_status']);
                if (! $order || in_array($order->status, ['cancelled', 'returned', 'delivered'], true)) {
                    return false;
                }

                $failureReason = trim($reason) !== '' ? trim($reason) : 'Cancelled';
                if ($order->payment_status === 'paid'
                    && ! str_contains(strtolower($failureReason), 'refund review is required')) {
                    $failureReason .= ' Payment was already confirmed; administrator refund review is required.';
                }

                DB::table('orders')->where('id', $orderId)->update([
                    'status' => 'cancelled',
                    'failed_reason' => $failureReason,
                ]);
                if (! self::releaseStock($orderId, 'order_cancelled')) {
                    throw new RuntimeException('Order stock could not be released.');
                }

                return true;
            });
        } catch (Throwable $e) {
            report($e);
            return false;
        }
    }

    private static function closeSellerOrderItems(int $orderId, string $reason): void
    {
        $status = str_starts_with($reason, 'order_return') ? 'returned' : 'cancelled';
        DB::table('seller_order_items')->where('order_id', $orderId)
            ->whereIn('status', ['pending', 'confirmed', 'packed', 'shipped', 'delivered'])
            ->update(['status' => $status, 'updated_at' => now()]);
    }

    /** Customer return request (delivered / failed_delivery orders only). */
    public static function requestReturn(int $orderId, string $reason): array
    {
        try {
            $o = DB::table('orders')->where('id', $orderId)->first(['id', 'status']);
            if (! $o) {
                return ['ok' => false, 'error' => 'Order not found.'];
            }
            if (! in_array($o->status, ['delivered', 'failed_delivery'], true)) {
                return ['ok' => false, 'error' => 'Returns can only be requested for delivered orders.'];
            }
            $exists = DB::table('order_returns')->where('order_id', $orderId)
                ->whereIn('status', ['requested', 'approved', 'received'])->exists();
            if ($exists) {
                return ['ok' => false, 'error' => 'A return request already exists for this order.'];
            }
            DB::table('order_returns')->insert([
                'order_id'   => $orderId,
                'reason'     => mb_substr($reason, 0, 255),
                'status'     => 'requested',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            DB::table('orders')->where('id', $orderId)->update(['status' => 'return_requested']);
            return ['ok' => true];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'Return request failed.'];
        }
    }
}

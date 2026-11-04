<?php

namespace App\Services\Payments;

/**
 * Payment configuration + method helpers. Ported from includes/config.php.
 * Credentials come from config/payments.php (BHERB_* environment variables).
 */
class PaymentConfig
{
    /** Active environment: "demo", "sandbox" or "live". */
    public static function env(): string
    {
        $asked = strtolower((string) config('payments.mode'));
        if (in_array($asked, ['demo', 'sandbox', 'live'], true)) {
            return $asked;
        }
        // Auto-detect: as soon as any credential exists the real API is used.
        foreach ((array) config('payments.gateways') as $gw) {
            foreach ($gw['env'] as $block) {
                foreach ($block as $key => $value) {
                    if (in_array($key, ['base', 'currency_code'], true)) {
                        continue;
                    }
                    if ($value !== '') {
                        return 'sandbox';
                    }
                }
            }
        }
        return 'demo';
    }

    /** Merged runtime config for one gateway in the active environment. */
    public static function gateway(string $gateway): array
    {
        $all = (array) config('payments.gateways');
        if (! isset($all[$gateway])) {
            return [];
        }
        $env = self::env();
        $block = $env === 'demo' ? ($all[$gateway]['env']['sandbox'] ?? []) : ($all[$gateway]['env'][$env] ?? []);
        return ['gateway' => $gateway, 'label' => $all[$gateway]['label'], 'env' => $env] + $block;
    }

    /** True when the active environment carries complete credentials for a gateway. */
    public static function configured(string $gateway): bool
    {
        if (self::env() === 'demo') {
            return false;
        }
        $required = match ($gateway) {
            'sslcommerz' => ['store_id', 'store_pass'],
            'bkash'      => ['username', 'password', 'app_key', 'app_secret'],
            'nagad'      => ['merchant_id', 'pg_public_key', 'merchant_private_key'],
            default      => [],
        };
        if (! $required) {
            return false;
        }
        $cfg = self::gateway($gateway);
        foreach ($required as $key) {
            if (($cfg[$key] ?? '') === '') {
                return false;
            }
        }
        return true;
    }

    /** Base URL of a gateway in the active environment. */
    public static function base(string $gateway): string
    {
        $base = trim((string) (self::gateway($gateway)['base'] ?? ''));
        if ($base === '' && $gateway === 'sslcommerz') {
            $base = self::env() === 'live' ? 'https://securepay.sslcommerz.com' : 'https://sandbox.sslcommerz.com';
        }
        // Nagad keeps its trailing slash (endpoints are appended directly).
        return $gateway === 'nagad' ? $base : rtrim($base, '/');
    }

    public static function methods(): array
    {
        return ['cod', 'sslcommerz', 'bkash', 'nagad'];
    }

    /** True when a posted value names a payment method we can route to (aliases allowed). */
    public static function known(string $method): bool
    {
        return in_array(strtolower(trim($method)), [
            'cod', 'cash', 'cash_on_delivery', 'delivery',
            'sslcommerz', 'ssl', 'sslcommerce', 'card', 'online',
            'bkash', 'b-kash', 'b_kash',
            'nagad', 'nogod',
        ], true);
    }

    /** Normalise a posted payment method to a known key (unknown -> cod). */
    public static function normalize(string $method): string
    {
        $m = match (strtolower(trim($method))) {
            'ssl', 'sslcommerz', 'sslcommerce', 'card', 'online' => 'sslcommerz',
            'bkash', 'b-kash', 'b_kash'                          => 'bkash',
            'nagad', 'nogod'                                     => 'nagad',
            'cod', 'cash', 'cash_on_delivery', 'delivery'        => 'cod',
            default                                              => '',
        };
        return $m !== '' ? $m : 'cod';
    }

    /** Human label for a stored payment method ("cash_on_delivery" -> "Cash on Delivery"). */
    public static function label(string $method): string
    {
        return match (self::normalize($method)) {
            'sslcommerz' => 'SSLCommerz',
            'bkash'      => 'bKash',
            'nagad'      => 'Nagad',
            default      => 'Cash on Delivery',
        };
    }

    /** Relative URL of an official payment logo (assets/images/payment/{key}.*) or null. */
    public static function brandLogo(string $key): ?string
    {
        foreach (['svg', 'png', 'webp', 'jpg'] as $ext) {
            $rel = 'assets/images/payment/' . $key . '.' . $ext;
            if (is_file(public_path($rel))) {
                return $rel;
            }
        }
        return null;
    }
}

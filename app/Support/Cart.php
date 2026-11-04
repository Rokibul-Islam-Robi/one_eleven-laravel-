<?php

namespace App\Support;

/**
 * Session cart + wishlist. Same session keys as the original PHP app:
 *   cart => [cartKey => qty], wish => [id,...], prefs => [currency, lang]
 * Ported from includes/bootstrap.php (cart_apply / cart_snapshot).
 */
class Cart
{
    public static function lines(): array
    {
        return session('cart', []);
    }

    public static function wish(): array
    {
        return session('wish', []);
    }

    public static function isWished(string $id): bool
    {
        return in_array($id, self::wish(), true);
    }

    /** Apply a cart/wishlist mutation (add|wish|qty|remove|clear). */
    public static function apply(string $action, string $id = '', int $qty = 1): void
    {
        if (in_array($action, ['add', 'wish', 'qty', 'remove'], true) && ! Catalog::byId($id)) {
            return;
        }
        $cart = self::lines();
        switch ($action) {
            case 'add':
                $cart[$id] = min(99, (int) ($cart[$id] ?? 0) + max(1, $qty));
                break;
            case 'wish':
                $wish = self::wish();
                if (in_array($id, $wish, true)) {
                    $wish = array_values(array_diff($wish, [$id]));
                } else {
                    $wish[] = $id;
                }
                session(['wish' => $wish]);
                break;
            case 'qty':
                $cart[$id] = min(99, max(1, $qty));
                break;
            case 'remove':
                unset($cart[$id]);
                break;
            case 'clear':
                $cart = [];
                break;
        }
        session(['cart' => $cart]);
    }

    public static function clear(): void
    {
        session(['cart' => []]);
    }

    /** Cart lines with product data + totals: ['items','total','count','wish']. */
    public static function snapshot(): array
    {
        $items = [];
        $total = 0.0;
        $count = 0;
        foreach (self::lines() as $key => $qty) {
            $p = Catalog::byId((string) $key);
            if (! $p) {
                continue;
            }
            $parts = Catalog::keyParts((string) $key);
            $unit = Catalog::variantUnitPrice($p, $parts['potency'], $parts['pack']);
            $line = $unit * (int) $qty;
            $p['qty'] = (int) $qty;
            $p['line'] = $line;
            $p['unit'] = $unit;
            $p['cart_key'] = (string) $key;
            $p['variant_label'] = Catalog::variantLabel($parts['potency'], $parts['pack']);
            $items[] = $p;
            $total += $line;
            $count += (int) $qty;
        }
        return ['items' => $items, 'total' => $total, 'count' => $count, 'wish' => count(self::wish())];
    }
}

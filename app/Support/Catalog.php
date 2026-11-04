<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Product catalogue backed by database/data/products.json (the original
 * storefront source). Ported from includes/bootstrap.php + includes/functions.php.
 * Product arrays keep the original short keys:
 *   id n s b c sub p o r rc i d t tag row
 */
class Catalog
{
    private static ?array $products = null;

    public static function all(): array
    {
        if (self::$products === null) {
            $file = config('bherb.catalog_file');
            self::$products = json_decode((string) file_get_contents($file), true) ?: [];
        }

        $sellerProducts = [];
        if (Schema::hasTable('products') && Schema::hasTable('sellers')
            && Schema::hasTable('categories') && Schema::hasTable('brands')) {
            $sellerProducts = DB::table('products as p')
                ->join('sellers as s', 's.id', '=', 'p.seller_id')
                ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
                ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
                ->whereNotNull('p.seller_id')
                ->whereNotNull('p.json_id')
                ->where('p.is_active', true)
                ->where('p.approval_status', 'approved')
                ->where('s.status', 'active')
                ->where('s.application_status', 'approved')
                ->where('s.is_active', true)
                ->whereNotNull('s.email_verified_at')
                ->orderBy('p.id')
                ->get([
                    'p.json_id as id',
                    'p.name as name',
                    'p.short_description',
                    'p.subtitle',
                    'p.price',
                    'p.old_price',
                    'p.image',
                    'p.badge',
                    'p.tone',
                    'p.rating',
                    'p.review_count',
                    'p.reviews_count',
                    'c.slug as category_slug',
                    'b.name as brand_name',
                    's.shop_name',
                ])
                ->map(static function (object $product): array {
                    $price = (float) $product->price;
                    $oldPrice = (float) ($product->old_price ?? 0);
                    $formatPrice = static fn (float $value): string => '৳'.number_format($value, fmod($value, 1.0) === 0.0 ? 0 : 2);

                    return [
                        'id' => (string) $product->id,
                        'n' => (string) $product->name,
                        's' => (string) ($product->subtitle ?: $product->short_description ?: ''),
                        'b' => (string) ($product->brand_name ?: $product->shop_name),
                        'c' => (string) ($product->category_slug ?: 'supplements'),
                        'sub' => (string) ($product->short_description ?: ''),
                        'p' => $formatPrice($price),
                        'o' => $oldPrice > 0 ? $formatPrice($oldPrice) : '',
                        'r' => (float) ($product->rating ?? 0),
                        'rc' => (int) ($product->review_count ?: $product->reviews_count ?: 0),
                        'i' => (string) ($product->image ?? ''),
                        'd' => '',
                        't' => (string) ($product->tone ?: 'g'),
                        'tag' => $oldPrice > $price ? (string) round(($oldPrice - $price) / $oldPrice * 100).'% OFF' : '',
                        'row' => 'seller',
                    ];
                })
                ->all();
        }

        return array_merge(self::$products, $sellerProducts);
    }

    /** Cart keys may carry variant info: "12" or "12#1000mg#2" (id#potency#pack). */
    public static function keyParts(string $key): array
    {
        $bits = explode('#', $key);
        return [
            'id'      => $bits[0] ?? '',
            'potency' => $bits[1] ?? '',
            'pack'    => isset($bits[2]) ? max(1, (int) $bits[2]) : 1,
        ];
    }

    /** Accepts a plain id or a variant cart key. */
    public static function byId($id): ?array
    {
        $id = self::keyParts((string) $id)['id'];
        foreach (self::all() as $p) {
            if ((string) $p['id'] === (string) $id) {
                return $p;
            }
        }
        return null;
    }

    public static function byCategory(string $cat): array
    {
        return array_values(array_filter(self::all(), fn ($p) => ($p['c'] ?? '') === $cat));
    }

    /* ------------------------------ brands ------------------------------ */
    public static function brandSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        return trim($slug, '-') ?: 'brand';
    }

    /** slug => ['name' => ..., 'count' => int] */
    public static function brandIndex(): array
    {
        static $idx = null;
        if ($idx !== null) {
            return $idx;
        }
        $idx = [];
        foreach (self::all() as $p) {
            $b = trim((string) ($p['b'] ?? ''));
            if ($b === '') {
                continue;
            }
            $slug = self::brandSlug($b);
            if (! isset($idx[$slug])) {
                $idx[$slug] = ['name' => $b, 'count' => 0];
            }
            $idx[$slug]['count']++;
        }
        return $idx;
    }

    public static function byBrand(string $slug): array
    {
        return array_values(array_filter(self::all(), fn ($p) => self::brandSlug((string) ($p['b'] ?? '')) === $slug));
    }

    /* ----------------------------- variants ----------------------------- */
    public static function potencies(array $p): array
    {
        $name = (string) ($p['n'] ?? '');
        if (! preg_match('/(\d+(?:\.\d+)?)\s*(?:mg|mcg)\b/i', $name, $m)) {
            return [];
        }
        $base = (float) $m[1];
        $unit = stripos($name, 'mcg') !== false && stripos($name, 'mg') === false ? 'mcg' : 'mg';
        $opts = [];
        foreach ([0.5, 1.0, 2.0] as $mult) {
            $v = $base * $mult;
            $txt = $v === floor($v) ? (string) (int) $v : (string) $v;
            $opts[] = ['value' => $txt . $unit, 'label' => $txt . ' ' . $unit, 'mult' => $mult];
        }
        return $opts;
    }

    public static function packs(array $p): array
    {
        $sub = (string) ($p['s'] ?? '');
        $name = (string) ($p['n'] ?? '');
        $pat = '/(\d+)\s*-?\s*(capsules|tablets|softgels|veggie caps|gummies|chewables|chews|pouches|servings|loads|lozenges|count|caps|tabs|pieces|ct|ml|l|g|kg|fl oz|oz)\b/i';
        if (! preg_match($pat, $sub . ' ' . $name, $m)) {
            return [];
        }
        $count = (int) $m[1];
        $unit = $m[2];
        $suffix = in_array(strtolower($unit), ['g', 'kg', 'ml', 'l', 'oz', 'fl oz'], true) ? $unit : 'ct';
        return [
            ['value' => '1', 'label' => '1 Bottle (' . $count . ' ' . $suffix . ')', 'mult' => 1.0, 'save' => 0],
            ['value' => '2', 'label' => '2 Bottles', 'mult' => 1.9, 'save' => 5],
            ['value' => '3', 'label' => '3 Bottles', 'mult' => 2.7, 'save' => 10],
        ];
    }

    public static function variantLabel(string $potency, int $pack): string
    {
        $bits = [];
        if ($potency !== '') {
            $bits[] = str_replace('mcg', ' mcg', str_replace('mg', ' mg', $potency));
        }
        if ($pack > 1) {
            $bits[] = $pack . '-Pack';
        }
        return implode(' · ', $bits);
    }

    public static function variantUnitPrice(array $p, string $potency, int $pack = 1): float
    {
        $base = priceval($p['p'] ?? 0);
        foreach (self::potencies($p) as $opt) {
            if ($opt['value'] === $potency) {
                $base *= $opt['mult'];
                break;
            }
        }
        foreach (self::packs($p) as $opt) {
            if ((int) $opt['value'] === max(1, $pack)) {
                $base *= $opt['mult'];
                break;
            }
        }
        return round($base);
    }

    public static function gallery(array $p): array
    {
        $imgs = [(string) ($p['i'] ?? '')];
        $brand = (string) ($p['b'] ?? '');
        $cat = (string) ($p['c'] ?? '');
        foreach (self::all() as $q) {
            if (count($imgs) >= 4) break;
            if ((string) ($q['id'] ?? '') === (string) ($p['id'] ?? '')) continue;
            if ($brand !== '' && (string) ($q['b'] ?? '') === $brand) $imgs[] = (string) ($q['i'] ?? '');
        }
        foreach (self::all() as $q) {
            if (count($imgs) >= 4) break;
            if ((string) ($q['id'] ?? '') === (string) ($p['id'] ?? '')) continue;
            if ($cat !== '' && (string) ($q['c'] ?? '') === $cat) $imgs[] = (string) ($q['i'] ?? '');
        }
        return array_values(array_unique(array_filter($imgs)));
    }

    /* ------------------------------ pricing ----------------------------- */
    public static function discPct(array $p): int
    {
        $cur = priceval($p['p']);
        $old = priceval($p['o']);
        return ($old > 0 && $old > $cur) ? (int) round(($old - $cur) / $old * 100) : 0;
    }

    public static function dealText(array $p): string
    {
        $code = config('bherb.promo_code');
        return trim(str_replace('{CODE}', $code, str_replace('${CODE}', $code, (string) ($p['d'] ?? ''))));
    }

    /* ------------------------------- sets ------------------------------- */
    public static function homeSets(): array
    {
        $all = self::all();
        $deals = array_values(array_filter($all, fn ($p) => ($p['row'] ?? '') === 'deals'));
        $new = array_values(array_filter($all, fn ($p) => ($p['row'] ?? '') === 'new'));
        $tabs = [
            ['supplements', 'Supplements'], ['bath', 'Bath & Personal Care'], ['beauty', 'Beauty'],
            ['sports', 'Sports'], ['grocery', 'Grocery'], ['kids', 'Kids & Babies'],
            ['pets', 'Pets'], ['home', 'Home'],
        ];
        $byCat = [];
        foreach ($tabs as [$c]) {
            $list = array_values(array_filter($all, fn ($p) => ($p['c'] ?? '') === $c));
            usort($list, function ($a, $b) {
                $rank = fn ($p) => ($p['row'] ?? '') === 'deals' ? 0 : (($p['row'] ?? '') === 'trending' ? 1 : 2);
                $ra = $rank($a);
                $rb = $rank($b);
                return $ra !== $rb ? $ra - $rb : (int) ($b['rc'] ?? 0) <=> (int) ($a['rc'] ?? 0);
            });
            $byCat[$c] = array_slice($list, 0, 6);
        }
        return ['deals' => $deals, 'new' => $new, 'tabs' => $tabs, 'byCat' => $byCat];
    }

    public static function trending(): array
    {
        return array_values(array_filter(self::all(), fn ($p) => ($p['row'] ?? '') === 'trending'));
    }

    public static function search(string $q, int $limit = 8): array
    {
        $q = trim($q);
        if ($q === '') {
            $list = self::all();
            usort($list, fn ($a, $b) => (int) ($b['rc'] ?? 0) <=> (int) ($a['rc'] ?? 0));
            return array_slice($list, 0, $limit);
        }
        $list = array_values(array_filter(self::all(), function ($p) use ($q) {
            $hay = $p['n'] . ' ' . $p['b'] . ' ' . $p['s'] . ' ' . $p['c'] . ' ' . ($p['sub'] ?? '');
            return stripos($hay, $q) !== false;
        }));
        usort($list, function ($a, $b) use ($q) {
            $score = fn ($p) => (stripos($p['n'], $q) === 0 ? 0 : (stripos($p['n'], $q) !== false ? 1 : 2));
            $sa = $score($a);
            $sb = $score($b);
            return $sa !== $sb ? $sa <=> $sb : (int) ($b['rc'] ?? 0) <=> (int) ($a['rc'] ?? 0);
        });
        return array_slice($list, 0, $limit);
    }
}

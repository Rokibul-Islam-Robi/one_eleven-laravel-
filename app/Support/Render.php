<?php

namespace App\Support;

/** Markup helpers shared by pages, sections and the JSON API (ported from includes/functions.php). */
class Render
{
    /** Inline price markup with a machine readable base value for client-side currency switching. */
    public static function priceHtml(string $tag, string $value, string $cls = ''): string
    {
        $base = priceval($value);
        $attr = ($base === (float) (int) $base) ? (string) (int) $base : (string) $base;
        return '<' . $tag . ($cls !== '' ? ' class="' . e($cls) . '"' : '')
            . ' data-price="' . $attr . '">' . e($value) . '</' . $tag . '>';
    }

    public static function pcard(array $p): string
    {
        $id = e($p['id']);
        $href = 'product/' . $id;
        $d = Catalog::discPct($p);
        $tag = trim((string) ($p['tag'] ?? ''));
        $isNew = (stripos($tag, 'new') !== false);
        $badge = $d > 0 ? $d . '% off' : $tag;
        $badgeCls = ($d > 0) ? '' : 'ptag' . ($isNew ? ' new' : '');
        $wish = in_array((string) $p['id'], Cart::wish(), true) ? ' on' : '';

        $o = '<article class="pcard" data-id="' . $id . '" data-cat="' . e($p['c'] ?? '') . '" data-rc="' . (int) ($p['rc'] ?? 0) . '">';
        $o .= '<div class="pmedia"><a class="pimg" href="' . $href . '"><img loading="lazy" src="' . e($p['i']) . '" alt="' . e($p['n']) . '"></a>';
        if ($badge !== '') {
            $o .= '<span class="ptag ' . $badgeCls . '">' . e($badge) . '</span>';
        }
        $o .= '<form class="pwish' . $wish . '" method="post" data-wish>' . csrf_field() . '<input type="hidden" name="action" value="wish"><input type="hidden" name="id" value="' . $id . '"><button type="submit" aria-pressed="' . ($wish ? 'true' : 'false') . '" aria-label="' . ($wish ? 'Remove from wishlist' : 'Add to wishlist') . '">' . Icons::ic('heart', 'ic-heart') . '</button></form>';
        $o .= '</div>';
        $o .= '<div class="pcopy"><small class="pbrand"><a href="brand.php?id=' . e(Catalog::brandSlug((string) $p['b'])) . '">' . e($p['b']) . '</a></small><a class="pname" href="' . $href . '">' . e($p['n']) . '</a>';
        $o .= '<div class="rating"><span>★★★★★</span><b>' . e($p['r']) . '</b><small>(' . number_format((int) $p['rc']) . ')</small></div>';
        $o .= '<div class="pfoot">';
        $o .= '<div class="pprice">' . self::priceHtml('b', (string) $p['p']) . ' ' . self::priceHtml('del', (string) $p['o']) . '</div>';
        $o .= '<div class="pdeal">' . e(Catalog::dealText($p)) . '</div>';
        $o .= '<div class="pship">Delivery by Tomorrow</div>';
        $o .= '<form class="padd" method="post" data-add>' . csrf_field() . '<input type="hidden" name="action" value="add"><input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="qty" value="1"><button class="addbtn">' . Icons::ic('plus', 'ic-plus') . 'Add to Cart</button></form>';
        $o .= '</div>';
        $o .= '</div></article>';
        return $o;
    }

    public static function secHead(string $title, string $tag, string $href): string
    {
        $o = '<div class="sec-head"><div class="sec-t"><h2>' . e($title) . '</h2>';
        if ($tag !== '') {
            $o .= '<span class="sec-tag">' . e($tag) . '</span>';
        }
        $o .= '</div>';
        if ($href !== '') {
            $o .= '<a class="sec-a" href="' . e($href) . '">View All ' . Icons::ic('arrow', 'ic-arrow-sm') . '</a>';
        }
        return $o . '</div>';
    }

    public static function cartBody(): string
    {
        return view('partials.cart-drawer-body', ['snap' => Cart::snapshot()])->render();
    }

    public static function cartFoot(): string
    {
        return view('partials.cart-drawer-foot', ['snap' => Cart::snapshot()])->render();
    }

    public static function cartPage(): string
    {
        $snap = Cart::snapshot();
        return view('partials.cart-page-content', [
            'cartProducts' => $snap['items'],
            'cartTotal' => $snap['total'],
        ])->render();
    }

    /** Snapshot returned by api/cart.php so JS can refresh every chrome widget. */
    public static function cartPayload(): array
    {
        $snap = Cart::snapshot();
        return [
            'ok'       => true,
            'count'    => $snap['count'],
            'subtotal' => round($snap['total'], 2),
            'wish'     => $snap['wish'],
            'body'     => self::cartBody(),
            'foot'     => self::cartFoot(),
            'page'     => self::cartPage(),
        ];
    }
}

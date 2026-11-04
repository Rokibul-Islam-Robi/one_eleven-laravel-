@php
/* Product detail page: /product/{id} — iHerb-style three-zone PDP.
         Left: image gallery + floating promo · Middle: details + attribute cards · Right: sticky buy box. */
$id = basename($path);
$p = productById($id);
if (!$p) {
    \App\Support\PageMeta::$status = 404;
    \App\Support\PageMeta::$title = 'Product not found — bHerb';
    echo '<main class="wrap page"><div class="empty"><h1>Product not found</h1><p>The product you were looking for is no longer available.</p><a class="btn-green" href="./">Back to home</a></div></main>';
    return;
}
\App\Support\PageMeta::$title = $p['n'] . ' — bHerb';
\App\Support\PageMeta::$desc = (string)$p['s'];

$gallery    = product_gallery($p);
$pots       = product_potencies($p);
$packRaw    = product_packs($p);
$basePrice  = priceval($p['p']);
$oldPrice   = priceval($p['o']);
$brandSlug  = brand_slug((string)$p['b']);
$rating     = (float)($p['r'] ?? 0);
$reviews    = (int)($p['rc'] ?? 0);
$qaCount    = max(2, (int)round($reviews / 70));
$catLabel   = $CAT_LABELS[(string)($p['c'] ?? '')] ?? ucfirst((string)($p['c'] ?? ''));
$bestBefore = date('M Y', strtotime('+3 years +1 month'));
$ratePct    = max(0, min(100, (int)round($rating / 5 * 100)));

$d  = discPct($p);
$dt = dealText($p);

/* Package cards: catalog packs when the product declares a count, single default pack otherwise. */
$packCards = [];
foreach ($packRaw as $opt) {
    $packCards[] = [
        'value' => (string)$opt['value'],
        'title' => 'Pack of ' . $opt['value'],
        'sub'   => (string)$opt['label'],
        'mult'  => (float)$opt['mult'],
        'save'  => (int)($opt['save'] ?? 0),
        'price' => (int)round($basePrice * (float)$opt['mult']),
    ];
}
if (!$packCards) {
    $packCards[] = ['value' => '1', 'title' => 'Pack of 1', 'sub' => 'Single unit', 'mult' => 1.0, 'save' => 0, 'price' => (int)$basePrice];
}

/* Free shipping progress — server-side first paint; JS repaints on variant / quantity changes. */
$shipFrom = (int)BHERB_FREE_SHIPPING_FROM;
$shipLeft = max(0, $shipFrom - (int)$basePrice);
$shipPct  = $shipFrom > 0 ? max(4, min(100, (int)round($basePrice / $shipFrom * 100))) : 100;

/* Supplement Facts rows — derived from the catalog name/subtitle, no invented percentages. */
$factHay = trim((string)$p['s'] . ' ' . (string)$p['n']);
$countPat = '/(\d+)\s*-?\s*(capsules|tablets|softgels|veggie caps|gummies|chewables|chews|pouches|servings|loads|lozenges|count|caps|tabs|pieces|ct)\b/i';
$massPat = '/(\d+(?:\.\d+)?)\s*-?\s*(fl oz|oz|kg|g|ml|l|mL)\b/i';
$sfRows = [];
if (preg_match($countPat, $factHay, $fm)) {
    $serveUnit = ['capsules' => 'Capsule', 'tablets' => 'Tablet', 'softgels' => 'Softgel', 'veggie caps' => 'Veggie Capsule', 'gummies' => 'Gummy', 'chewables' => 'Chewable', 'chews' => 'Chew', 'pouches' => 'Pouch', 'servings' => 'Serving', 'loads' => 'Load', 'lozenges' => 'Lozenge', 'count' => 'Serving', 'caps' => 'Capsule', 'tabs' => 'Tablet', 'pieces' => 'Piece', 'ct' => 'Serving'][strtolower($fm[2])] ?? 'Serving';
    $sfRows[] = ['Serving Size', '1 ' . $serveUnit];
    $sfRows[] = ['Servings Per Container', (string)(int)$fm[1]];
} elseif (preg_match($massPat, $factHay, $mm)) {
    $sfRows[] = ['Net Quantity', $mm[1] . ' ' . $mm[2]];
}
if (preg_match('/^(.*\D)(\d+(?:\.\d+)?)\s*(mg|mcg|g|iu|%)$/i', trim((string)$p['n']), $am)) {
    $unit = strtolower($am[3]);
    $sfRows[] = [trim($am[1]), $am[2] . ' ' . ($unit === 'iu' ? 'IU' : $unit)];
}

/* Same-brand cross-sell + same-category fallback. */
$related = [];
foreach ($products as $q) {
    if (count($related) >= 5) break;
    if ((string)($q['id'] ?? '') === (string)$p['id']) continue;
    if (brand_slug((string)($q['b'] ?? '')) === $brandSlug) $related[] = $q;
}
foreach ($products as $q) {
    if (count($related) >= 5) break;
    if ((string)($q['id'] ?? '') === (string)$p['id']) continue;
    if (in_array($q, $related, true)) continue;
    if (($q['c'] ?? '') === ($p['c'] ?? '')) $related[] = $q;
}
$alsoFrom = [];
foreach ($products as $q) {
    if (count($alsoFrom) >= 5) break;
    if ((string)($q['id'] ?? '') === (string)$p['id']) continue;
    if (brand_slug((string)($q['b'] ?? '')) === $brandSlug) $alsoFrom[] = $q;
}

$chevL = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg>';
$chevR = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg>';
@endphp @php
$wished = in_array((string)$p['id'], session('wish', []), true);
@endphp <main class="wrap wrap-wide page product-page">
    <div class="product-detail">

        <!-- ============ 1 · GALLERY: main image card · thumbnails ============ -->
        <div class="detail-gallery">
            <div class="detail-image dg-main">
                <img id="dg-main-img" src="{{ $gallery[0] ?? $p['i'] }}" alt="{{ $p['n'] }}"
                     width="600" height="600" fetchpriority="high" decoding="async">
                @if ($d > 0)<span class="ptag dg-off">{!! $d !!}% OFF</span>@endif                @if (count($gallery) > 1)                    <button type="button" class="dg-arrow prev" id="dg-prev" aria-label="Previous image">{!! $chevL !!}</button>
                    <button type="button" class="dg-arrow next" id="dg-next" aria-label="Next image">{!! $chevR !!}</button>
                @endif                <span class="dg-count" id="dg-count">1 / {!! count($gallery) !!}</span>
            </div>

            <div class="dg-strip" id="dg-strip">
                <div class="dg-thumbs" id="dg-thumbs" role="tablist" aria-label="Product images">
                    @foreach ($gallery as $i => $img)                        <button type="button" class="dg-thumb{!! $i === 0 ? ' on' : '' !!}" role="tab"
                                aria-selected="{!! $i === 0 ? 'true' : 'false' !!}" tabindex="{!! $i === 0 ? '0' : '-1' !!}"
                                data-full="{{ $img }}" aria-label="View image {!! $i + 1 !!} of {!! count($gallery) !!}">
                            <img src="{{ $img }}" alt="{{ $p['n'] }} — image {!! $i + 1 !!}" width="76" height="76" loading="lazy" decoding="async">
                        </button>
                    @endforeach                </div>
            </div>
        </div>

        <!-- ============ 2 · DETAILS: breadcrumb · brand · rating · attribute cards · accordions ============ -->
        <div class="pd-info">
            <nav class="breadcrumbs pd-crumb" aria-label="Breadcrumb">
                <a href="./">Home</a>
                <span class="crumb-sep" aria-hidden="true">/</span>
                <a href="category.php?cat={{ $p['c'] }}">{{ $catLabel }}</a>
                <span class="crumb-sep" aria-hidden="true">/</span>
                <a href="brand.php?id={{ $brandSlug }}">{{ $p['b'] }}</a>
                <span class="crumb-sep" aria-hidden="true">/</span>
                <span aria-current="page">{{ $p['n'] }}</span>
            </nav>

            <a class="pd-brand" href="brand.php?id={{ $brandSlug }}">{{ $p['b'] }}</a>

            <h1 class="pd-title">{{ $p['n'] }}</h1>

            <div class="pd-rate">
                <span class="pdp-stars" style="--pct:{!! $ratePct !!}%" role="img" aria-label="Rated {{ number_format($rating, 1) }} out of 5">
                    <span aria-hidden="true">★★★★★</span>
                    <span class="on" aria-hidden="true">★★★★★</span>
                </span>
                <b class="pd-rate-v">{{ number_format($rating, 1) }}</b>
                <span class="pd-rate-n">{!! number_format($reviews) !!} reviews</span>
                <span class="pd-sep" aria-hidden="true"></span>
                <button type="button" class="pd-link" data-soon="Reviews are coming soon — thank you for your interest!">Write a review</button>
                <span class="pd-sep" aria-hidden="true"></span>
                <button type="button" class="pd-link" data-soon="Q&amp;A is coming soon — ask our team in Support meanwhile.">Q&amp;A ({!! $qaCount !!})</button>
            </div>

            <div class="pd-badges">
                <span class="pd-badge pd-stock">{!! ic('check', 'ic-badge-ic') !!}In Stock</span>
                <span class="pd-badge pd-exp">{!! ic('clock', 'ic-badge-ic') !!}Best before: {{ $bestBefore }}</span>
            </div>

            <p class="pd-summary">{{ $p['s'] }}</p>
            @if ($dt !== '')                <span class="pd-code">{!! ic('tag', 'ic-code-ic') !!}<span>{{ $dt }}</span></span>
            @endif
            @if ($pots)                <div class="pd-card" role="group" aria-labelledby="pot-h">
                    <div class="pd-card-h"><b id="pot-h">Potency</b><span class="var-sel" id="pot-label">{{ $pots[1]['label'] ?? $pots[0]['label'] }}</span></div>
                    <div class="var-opts" id="pot-opts">
                        @foreach ($pots as $i => $opt)                            <label class="var-opt">
                                <input type="radio" name="potency" value="{{ $opt['value'] }}" form="detail-form"
                                       data-mult="{!! $opt['mult'] !!}" data-label="{{ $opt['label'] }}"
                                       {!! ($i === 1 || (count($pots) < 2 && $i === 0)) ? 'checked' : '' !!}>
                                <span>{{ $opt['label'] }}</span>
                            </label>
                        @endforeach                    </div>
                </div>
            @endif
            <div class="pd-card" role="group" aria-labelledby="pack-h">
                <div class="pd-card-h"><b id="pack-h">Package Quantity</b><span class="var-sel" id="pack-label">{{ $packCards[0]['title'] }}</span></div>
                <div class="pack-opts" id="pack-opts">
                    @foreach ($packCards as $i => $opt)                        <label class="pack-card">
                            <input type="radio" name="pack" value="{{ $opt['value'] }}" form="detail-form"
                                   data-mult="{!! $opt['mult'] !!}" data-label="{{ $opt['title'] }}"
                                   data-save="{!! $opt['save'] !!}" {!! $i === 0 ? 'checked' : '' !!}>
                            <span class="pack-chk">{!! ic('check', 'ic-pack-chk') !!}</span>
                            <span class="pack-tx">
                                <b>{{ $opt['title'] }}</b>
                                <small>{{ $opt['sub'] }}</small>
                                <span class="pack-row">
                                    <em class="pack-price" data-price="{!! $opt['price'] !!}">{!! money($opt['price']) !!}</em>
                                    @if ($opt['save'])<span class="pack-save">Save {!! $opt['save'] !!}%</span>@endif                                </span>
                            </span>
                        </label>
                    @endforeach                </div>
            </div>

            <details class="pd-acc" open>
                <summary class="pd-acc-h"><span>Overview</span>{!! ic('chevron', 'ic-acc') !!}</summary>
                <div class="pd-acc-body">
                    <p class="pd-acc-p">Sourced directly from {{ $p['b'] }} and authenticity-checked by bHerb before it ships.</p>
                    <ul class="pd-facts">
                        <li>{!! ic('shield', 'ic-fact') !!}<span>Authentic product · Ships within 24 hours</span></li>
                        <li>{!! ic('truck', 'ic-fact') !!}<span>Reliable delivery across Bangladesh · Free over {!! money(BHERB_FREE_SHIPPING_FROM) !!}</span></li>
                        <li>{!! ic('refresh', 'ic-fact') !!}<span>14-day returns on unopened items · Support 7 days a week</span></li>
                    </ul>
                </div>
            </details>

            <details class="pd-acc">
                <summary class="pd-acc-h"><span>Supplement Facts</span>{!! ic('chevron', 'ic-acc') !!}</summary>
                <div class="pd-acc-body">
                    @if ($sfRows)                    <table class="sf-table">
                        <tbody>
                        @foreach ($sfRows as [$k, $v])                            <tr><th scope="row">{{ $k }}</th><td>{{ $v }}</td></tr>
                        @endforeach                        </tbody>
                    </table>
                    @endif                    <p class="sf-note">See the product packaging for the complete Supplement Facts panel, ingredient list and allergen information.</p>
                </div>
            </details>
        </div>

        <!-- ============ 3 · BUY BOX: price · delivery · quantity · cart · wishlist · shipping ============ -->
        <aside class="pd-buy" aria-label="Purchase options">
            <div class="pb-price" id="dp-price">
                <b data-price="{!! (int)$basePrice !!}">{!! money($basePrice) !!}</b>
                @if ($oldPrice > $basePrice)                    <del id="dp-old" data-price="{!! (int)$oldPrice !!}">{!! money($oldPrice) !!}</del>
                    <span class="pb-save" id="dp-save">Save {!! $d > 0 ? $d : (int)round(($oldPrice - $basePrice) / $oldPrice * 100) !!}%</span>
                @endif            </div>
            <p class="pb-tax">Inclusive of all taxes</p>

            <div class="pb-expr">
                <span class="pb-expr-ico">{!! ic('truck', 'ic-expr') !!}</span>
                <span class="pb-expr-tx">
                    <b>Delivery by Tomorrow in Dhaka</b>
                    <small>Same-day dispatch before 4 PM · Nationwide delivery available</small>
                </span>
            </div>

            <form method="post" class="detail-form" id="detail-form" data-add>@csrf
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="id" value="{{ $p['id'] }}">
                <input type="hidden" name="variant" id="variant-input" value="">

                <div class="qty-row">
                    <label for="pd-qty">Quantity</label>
                    <div class="qty-stepper" data-stepper>
                        <button type="button" data-step="-1" aria-label="Decrease quantity">{!! ic('minus', 'ic-step') !!}</button>
                        <input id="pd-qty" type="number" name="qty" value="1" min="1" max="99" aria-label="Quantity">
                        <button type="button" data-step="1" aria-label="Increase quantity">{!! ic('plus', 'ic-step') !!}</button>
                    </div>
                </div>

                <button class="addbtn pb-atc">{!! ic('cart', 'ic-atc') !!}Add to Cart</button>
            </form>

            <form method="post" class="pd-wish-form{!! $wished ? ' on' : '' !!}" data-wish data-wish-text>@csrf
                <input type="hidden" name="action" value="wish">
                <input type="hidden" name="id" value="{{ $p['id'] }}">
                <button type="submit" class="wish-btn" aria-pressed="{!! $wished ? 'true' : 'false' !!}"
                        aria-label="{!! $wished ? 'Remove from wishlist' : 'Add to wishlist' !!}">
                    {!! ic('heart', 'ic-heart') !!}<span class="wish-text" data-wish-label>{!! $wished ? 'Saved to wishlist' : 'Add to wishlist' !!}</span>
                </button>
            </form>

            <div class="pb-ship" id="pb-ship" data-from="{!! $shipFrom !!}">
                <p class="pb-ship-tx">
                    <span id="pb-ship-left">Add <b id="pb-ship-amt" data-price="{!! $shipLeft !!}">{!! money($shipLeft) !!}</b> more for <strong>FREE shipping</strong></span>
                    <span id="pb-ship-ok"{!! $shipLeft > 0 ? ' hidden' : '' !!}>Your order qualifies for <strong>FREE shipping</strong></span>
                </p>
                <div class="pb-ship-bar" role="progressbar" aria-label="Free shipping progress"
                     aria-valuemin="0" aria-valuemax="100" aria-valuenow="{!! $shipPct !!}">
                    <i id="pb-ship-fill" style="width:{!! $shipPct !!}%"></i>
                </div>
            </div>

            <ul class="pb-trust">
                <li>{!! ic('truck', 'ic-trust') !!}<span>Ships from &amp; sold by <b>bHerb</b></span></li>
                <li>{!! ic('shield', 'ic-trust') !!}<span>Secure transaction</span></li>
            </ul>
        </aside>
    </div>

    @if ($alsoFrom)    <section class="sec">
        <div class="sec-head"><div class="sec-t"><h2>More from {{ $p['b'] }}</h2>
            <a class="sec-a" href="brand.php?id={{ $brandSlug }}">View brand page →</a></div></div>
        <div class="product-grid">
            @php
foreach ($alsoFrom as $rp) echo pcard($rp);
@endphp        </div>
    </section>
    @endif
    @if ($related)    <section class="sec">
        <div class="sec-head"><div class="sec-t"><h2>You may also like</h2></div></div>
        <div class="product-grid">
            @php
foreach ($related as $rp) echo pcard($rp);
@endphp        </div>
    </section>
    @endif </main>

<script>
/* Gallery slider · variant pricing · free-shipping progress (progressive enhancement;
   forms still post without JS). */
(function () {
    var form = document.getElementById('detail-form');
    if (!form) return;
    var root = document.querySelector('.product-detail') || form;
    var base = {!! (int)$basePrice !!};
    var oldBase = {!! (int)$oldPrice !!};

    var mainImg = document.getElementById('dg-main-img');
    var thumbs = Array.prototype.slice.call(document.querySelectorAll('.dg-thumb'));
    var prevBtn = document.getElementById('dg-prev');
    var nextBtn = document.getElementById('dg-next');
    var countEl = document.getElementById('dg-count');
    var qtyInput = form.elements['qty'];
    var productName = {!! json_encode((string)$p['n'], JSON_UNESCAPED_UNICODE) !!};
    var idx = 0;

    function paint(el, value) {
        if (!el) return;
        if (window.bHerb && bHerb.setPrice) { bHerb.setPrice(el, value); return; }
        el.setAttribute('data-price', value);
        el.textContent = '৳' + Number(value).toLocaleString('en-US');
    }
    function setLabel(id, text) {
        var el = document.getElementById(id);
        if (el && text) el.textContent = text;
    }

    /* ---------- gallery ---------- */
    function show(i, focusThumb) {
        if (!thumbs.length) return;
        idx = (i + thumbs.length) % thumbs.length;
        var btn = thumbs[idx];
        thumbs.forEach(function (b, j) {
            var on = j === idx;
            b.classList.toggle('on', on);
            b.setAttribute('aria-selected', on ? 'true' : 'false');
            b.tabIndex = on ? 0 : -1;
        });
        if (countEl) countEl.textContent = (idx + 1) + ' / ' + thumbs.length;
        if (focusThumb && btn.focus) btn.focus({ preventScroll: true });
        if (btn.scrollIntoView) btn.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
        var full = btn.getAttribute('data-full');
        if (mainImg && full && mainImg.getAttribute('src') !== full) {
            mainImg.classList.add('swapping');
            var pre = new Image();
            var done = function () {
                mainImg.src = full;
                mainImg.alt = productName + ' — image ' + (idx + 1);
                mainImg.classList.remove('swapping');
            };
            pre.onload = done;
            pre.onerror = done;
            pre.src = full;
        }
    }

    thumbs.forEach(function (btn, i) {
        btn.addEventListener('click', function () { show(i); galStart(); });
        btn.addEventListener('keydown', function (e) {
            var k = e.key, n = thumbs.length, t = null;
            if (k === 'ArrowRight') t = thumbs[(i + 1) % n];
            else if (k === 'ArrowLeft') t = thumbs[(i + n - 1) % n];
            else if (k === 'Home') t = thumbs[0];
            else if (k === 'End') t = thumbs[n - 1];
            if (!t) return;
            e.preventDefault();
            show(thumbs.indexOf(t), true);
            galStart();
        });
    });
    if (prevBtn) prevBtn.addEventListener('click', function () { show(idx - 1); galStart(); });
    if (nextBtn) nextBtn.addEventListener('click', function () { show(idx + 1); galStart(); });

    /* swipe through images on touch screens */
    if (mainImg && thumbs.length > 1) {
        var sx = 0, sy = 0, tracking = false;
        mainImg.addEventListener('touchstart', function (e) {
            if (e.touches.length !== 1) { tracking = false; return; }
            tracking = true;
            sx = e.touches[0].clientX;
            sy = e.touches[0].clientY;
            galPause();
        }, { passive: true });
        mainImg.addEventListener('touchend', function (e) {
            if (!tracking) return;
            tracking = false;
            var dx = e.changedTouches[0].clientX - sx;
            var dy = e.changedTouches[0].clientY - sy;
            if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy) * 1.4) show(idx + (dx < 0 ? 1 : -1));
            galStart();
        }, { passive: true });
    }

    /* auto-advance the gallery: 4s per image, pauses on hover / touch / hidden tab,
       loops from the last image back to the first (show() wraps the index) */
    var galTimer = null, galHeld = 0;
    var galBox = document.querySelector('.detail-gallery');
    var galReduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    function galStop() { if (galTimer) { clearInterval(galTimer); galTimer = null; } }
    function galPause() { galHeld++; galStop(); }
    function galStart() {
        galStop();
        if (galReduce || galHeld || document.hidden || thumbs.length < 2) return;
        galTimer = setInterval(function () { if (!galHeld && !document.hidden) show(idx + 1); }, 4000);
    }
    if (galBox) {
        galBox.addEventListener('mouseenter', galPause);
        galBox.addEventListener('mouseleave', function () { galHeld = Math.max(0, galHeld - 1); galStart(); });
        galBox.addEventListener('focusin', galPause);
        galBox.addEventListener('focusout', function () { setTimeout(function () { galHeld = Math.max(0, galHeld - 1); galStart(); }, 0); });
    }
    document.addEventListener('visibilitychange', galStart);
    galStart();

    /* ---------- quantity stepper ---------- */
    document.querySelectorAll('[data-stepper] button').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = btn.parentNode.querySelector('input');
            var next = (parseInt(input.value, 10) || 1) + parseInt(btn.getAttribute('data-step'), 10);
            input.value = Math.max(1, Math.min(99, next));
            recalc();
        });
    });
    if (qtyInput) qtyInput.addEventListener('input', recalc);

    /* variant radios live in the middle column but belong to the purchase form (form="detail-form"),
       so look them up through form.elements instead of descendants */
    function selected(name) {
        var els = form.elements[name];
        if (!els) return null;
        if (els.length === undefined) return els.checked ? els : null;
        for (var i = 0; i < els.length; i++) if (els[i].checked) return els[i];
        return null;
    }

    /* ---------- free shipping progress ---------- */
    function ship(unit) {
        var box = document.getElementById('pb-ship');
        if (!box) return;
        var from = parseFloat(box.getAttribute('data-from')) || 0;
        var qty = Math.max(1, parseInt(qtyInput && qtyInput.value, 10) || 1);
        var sub = unit * qty;
        var left = Math.max(0, Math.round(from - sub));
        var pct = from > 0 ? Math.min(100, Math.max(4, Math.round((sub / from) * 100))) : 100;
        var fill = document.getElementById('pb-ship-fill');
        if (fill) fill.style.width = pct + '%';
        var bar = box.querySelector('.pb-ship-bar');
        if (bar) bar.setAttribute('aria-valuenow', pct);
        var leftWrap = document.getElementById('pb-ship-left');
        var okWrap = document.getElementById('pb-ship-ok');
        if (left > 0) {
            if (leftWrap) leftWrap.hidden = false;
            if (okWrap) okWrap.hidden = true;
            paint(document.getElementById('pb-ship-amt'), left);
        } else {
            if (leftWrap) leftWrap.hidden = true;
            if (okWrap) okWrap.hidden = false;
        }
    }

    /* ---------- variant pricing: price · save badge · pack cards · shipping ---------- */
    function recalc() {
        var mult = 1, parts = [];
        var pot = selected('potency');
        var pack = selected('pack');
        if (pot) {
            mult *= parseFloat(pot.getAttribute('data-mult')) || 1;
            parts.push(pot.value);
            setLabel('pot-label', pot.getAttribute('data-label'));
        }
        if (pack) {
            mult *= parseFloat(pack.getAttribute('data-mult')) || 1;
            if (pack.value !== '1') parts.push(pack.value);
            setLabel('pack-label', pack.getAttribute('data-label'));
        }
        var unit = Math.round(base * mult);
        paint(document.querySelector('#dp-price b'), unit);

        var oldEl = document.getElementById('dp-old');
        var saveEl = document.getElementById('dp-save');
        if (oldEl) {
            var oldUnit = oldBase > base ? Math.round(oldBase * mult) : 0;
            if (oldUnit > unit) {
                oldEl.hidden = false;
                paint(oldEl, oldUnit);
                if (saveEl) {
                    saveEl.hidden = false;
                    saveEl.textContent = 'Save ' + Math.round(((oldUnit - unit) / oldUnit) * 100) + '%';
                }
            } else {
                oldEl.hidden = true;
                if (saveEl) saveEl.hidden = true;
            }
        }

        var potMult = pot ? (parseFloat(pot.getAttribute('data-mult')) || 1) : 1;
        document.querySelectorAll('#pack-opts .pack-card').forEach(function (card) {
            var inp = card.querySelector('input');
            if (!inp) return;
            card.classList.toggle('on', inp.checked);
            var el = card.querySelector('.pack-price');
            if (el) paint(el, Math.round(base * potMult * (parseFloat(inp.getAttribute('data-mult')) || 1)));
        });

        var variantInput = document.getElementById('variant-input');
        if (variantInput) variantInput.value = parts.join('#');
        ship(unit);
    }

    root.addEventListener('change', recalc);
    root.addEventListener('input', recalc);

    /* ---------- review / Q&A placeholders ---------- */
    document.querySelectorAll('[data-soon]').forEach(function (el) {
        el.addEventListener('click', function () {
            var msg = el.getAttribute('data-soon') || 'This feature is coming soon';
            if (window.bHerb && bHerb.toast) bHerb.toast(msg);
        });
    });

    recalc();
})();
</script>

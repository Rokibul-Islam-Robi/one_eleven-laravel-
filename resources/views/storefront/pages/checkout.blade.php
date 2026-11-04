@php
/* Checkout: /checkout — co-* pharmaceutical layout, prescription drag-drop,
          courier + coupon, SSLCommerz popup, direct bKash / Nagad redirects or Cash on Delivery. */

\App\Support\PageMeta::$title = 'Secure Checkout — bHerb';

$snap = \App\Support\Cart::snapshot();
$subtotal = $snap['total'];
$promos = \App\Services\CourierService::active();
$checkoutValues = array_replace($checkoutDefaults ?? [], session('checkout_old', []));

$selectedCourier = (string)(request()->query('courier') ?? 'redx');
$payError = (string)(request()->query('pay') ?? '');
$payNotice = [
    'fail'      => 'Payment was not completed. Your cart is intact — please try again.',
    'cancelled' => 'Payment was cancelled. Your cart is intact whenever you are ready.',
][$payError] ?? '';

if (!$snap['items']) {
    echo '<main class="wrap page"><div class="empty"><h1>Your cart is empty</h1>'
        . '<p>Add some wellness essentials before checking out.</p>'
        . '<a class="btn-green" href="./">Shop products</a></div></main>';
    return;
}

/* Payment options — what is actually offered:
   · SSLCommerz hosted checkout (cards + bKash/Nagad channels live INSIDE that page) — always
     available; runs the local demo gateway until sandbox/live credentials are configured.
   · Direct bKash (Tokenized Checkout) and Nagad merchant APIs when their credentials exist;
     in demo mode (no credentials at all) both are still listed so the full flow can be
     exercised offline against the simulated gateway.
   · Cash on Delivery. */
$demoGateway = ! \App\Services\Payments\PaymentConfig::configured('sslcommerz');
$bkashReady  = \App\Services\Payments\PaymentConfig::configured('bkash');
$nagadReady  = \App\Services\Payments\PaymentConfig::configured('nagad');
$demoStack   = \App\Services\Payments\PaymentConfig::env() === 'demo';
$showBkash   = $bkashReady || $demoStack;
$showNagad   = $nagadReady || $demoStack;
$districtGroups = \App\Support\Districts::byDivision();

/** Official brand logo from assets/images/payment/{key}.(svg|png|webp|jpg) when the
    approved asset exists (SSLCommerz, bKash, Nagad); otherwise a plain text chip
    (logos are never redrawn or approximated). */
function pay_brand(string $key, string $label): string
{
    foreach (['svg', 'png', 'webp', 'jpg'] as $ext) {
        $rel = 'assets/images/payment/' . $key . '.' . $ext;
        if (is_file(public_path($rel))) {
            return '<span class="pay-brand has-logo" title="' . e($label) . '">'
                 . '<img src="' . e($rel) . '" alt="' . e($label) . '" height="22" loading="lazy" decoding="async">'
                 . '</span>';
        }
    }
    return '<span class="pay-brand">' . e($label) . '</span>';
}

/* trust badges under the h1 */
$trust = [
    ['shield',  'SSL 256-bit encrypted checkout'],
    ['truck',   'Free standard delivery over ' . money(BHERB_FREE_SHIPPING_FROM)],
    ['refresh', 'Easy 30-day returns on eligible items'],
    ['flask',   'Pharmacist-reviewed prescriptions'],
];
@endphp <main class="wrap page order-ok">
    <div class="ok-hero">
        <h1>Secure Checkout</h1>
        <div class="ok-sub">
            <span>Home</span><span class="ok-dot">/</span>
            <span>Cart</span><span class="ok-dot">/</span>
            <b>Checkout</b>
        </div>
    </div>

    @if ($payNotice)        <div class="alert warn">{{ $payNotice }}</div>
    @endif
    <div class="co-grid">
        <!-- ==================== form ==================== -->
        <form method="post" enctype="multipart/form-data" class="checkout-form" id="checkout-form"
              action="checkout" novalidate>@csrf
            <input type="hidden" name="action" value="checkout">

            <div class="co-main">
                <!-- 1 · Contact -->
                <div class="co-card">
                    <h2><span class="co-step">1</span> Contact information</h2>
                    <div class="co-fields">
                        <div class="co-field">
                            <label for="co-name">Full name <span class="req">*</span></label>
                            <input id="co-name" class="co-input" name="name" autocomplete="name" required minlength="2"
                                   value="{{ old('name', $checkoutValues['name'] ?? '') }}" placeholder="e.g. Nusrat Jahan">
                            <small class="co-err" data-err="name"></small>
                        </div>
                        <div class="co-field">
                            <label for="co-phone">Mobile number <span class="req">*</span></label>
                            <input id="co-phone" class="co-input" name="phone" type="tel" autocomplete="tel" required
                                   pattern="^(?:\+?880|0)1[3-9][0-9]{8}$" value="{{ old('phone', $checkoutValues['phone'] ?? '') }}" placeholder="01XXXXXXXXX">
                            <small class="co-err" data-err="phone"></small>
                        </div>
                        <div class="co-field co-field-wide">
                            <label for="co-email">Email <span style="font-weight:400;color:#98a0a6">(optional)</span></label>
                            <input id="co-email" class="co-input" name="email" type="email" autocomplete="email"
                                   value="{{ old('email', $checkoutValues['email'] ?? '') }}" placeholder="you@example.com">
                            <small class="co-err" data-err="email"></small>
                        </div>
                    </div>
                </div>

                <!-- 2 · Delivery address -->
                <div class="co-card">
                    <h2><span class="co-step">2</span> Delivery address</h2>
                    <div class="co-fields">
                        <div class="co-field co-field-wide" data-district>
                            <label for="co-area">District <span class="req">*</span></label>
                            <!-- Native select = no-JS / mobile fallback; checkout JS upgrades it to a searchable list. -->
                            <select id="co-area" class="co-input co-select" name="area" required autocomplete="address-level1">
                                <option value="">Select your district…</option>
                                @foreach ($districtGroups as $division => $list)                                    <optgroup label="{{ $division }} division">
                                        @foreach ($list as $dName)                                            <option value="{{ $dName }}" @selected(old('area', $checkoutValues['area'] ?? '') === $dName)>{{ $dName }}</option>
                                        @endforeach                                    </optgroup>
                                @endforeach                            </select>
                            <small class="co-err" data-err="area"></small>
                        </div>
                        <div class="co-field co-field-wide">
                            <label for="co-addr">Street address <span class="req">*</span></label>
                            <textarea id="co-addr" class="co-input co-textarea" name="address" rows="2" required minlength="10"
                                      autocomplete="street-address"
                                      placeholder="House, road, village / block, thana / upazila, landmark">{{ old('address', $checkoutValues['address'] ?? '') }}</textarea>
                            <small class="co-hint">Your district is chosen above — include house, road, area and landmark here.</small>
                            <small class="co-err" data-err="address"></small>
                        </div>
                        <div class="co-field co-field-wide">
                            <label for="co-note">Delivery note <span style="font-weight:400;color:#98a0a6">(optional)</span></label>
                            <input id="co-note" class="co-input" name="note" value="{{ old('note', $checkoutValues['note'] ?? '') }}" placeholder="e.g. Call before delivery, leave at the gate">
                        </div>
                    </div>
                </div>

                <!-- 3 · Delivery method -->
                <div class="co-card">
                    <h2><span class="co-step">3</span> Delivery method</h2>
                    <div class="courier-list" id="courier-list">
                        @foreach ($promos as $c)
@php
$free = $subtotal >= (float)$c['free_delivery_min'] || $subtotal >= BHERB_FREE_SHIPPING_FROM;
                            $fee = $free ? 0 : (float)$c['fee'];
@endphp                            <label class="courier-opt{!! $c['code'] === $selectedCourier ? ' on' : '' !!}">
                                <input type="radio" name="courier" value="{{ $c['code'] }}"
                                       data-fee="{!! (int)$fee !!}" data-codfee="{!! (int)$c['cod_fee'] !!}"
                                       data-label="{{ $c['name'] }}"
                                       {!! $c['code'] === $selectedCourier ? 'checked' : '' !!}>
                                <span>
                                    <span style="font-weight:700;font-size:13.5px">{{ $c['name'] }}</span>
                                    <small style="display:block;font-weight:400;color:#6d757c;font-size:11.5px">
                                        Delivery in {{ $c['estimated_days'] }}
                                        @if ($free) · <span style="color:var(--green,#16813b);font-weight:700">FREE over {!! money((float)$c['free_delivery_min']) !!}</span>@endif                                    </small>
                                </span>
                                <span class="courier-fee">{!! $fee === 0 ? '<em style="color:var(--green,#16813b);font-style:normal">FREE</em>' : money($fee) !!}</span>
                            </label>
                        @endforeach                    </div>
                </div>

                <!-- 4 · Prescription -->
                <div class="co-card">
                    <h2><span class="co-step">4</span> Doctor's prescription <span style="font-size:12px;font-weight:600;color:#98a0a6;margin-left:auto">optional</span></h2>
                    <p style="font-size:13px;color:#6d757c;margin:0 0 14px">
                        Ordering medicines or need a pharmacist review? Upload your prescription — we verify it before dispatch.
                    </p>
                    <div class="rx-drop" id="rx-drop" tabindex="0" role="button" aria-label="Upload prescription">
                        <input type="file" name="rx_file" id="rx-file" accept=".jpg,.jpeg,.png,.webp,.pdf" hidden>
                        <span class="rx-ico">📄</span>
                        <b>Drag &amp; drop your prescription here</b>
                        <small>or click to browse · JPG, PNG, WEBP or PDF · up to 5 MB</small>
                        <div class="rx-file" id="rx-file-chip" hidden>
                            <span class="rx-name"></span>
                            <button type="button" class="rx-remove" aria-label="Remove file">✕</button>
                        </div>
                    </div>
                    <small class="co-err" data-err="rx_file"></small>
                </div>

                <!-- 5 · Payment -->
                <div class="co-card">
                    <h2><span class="co-step">5</span> Payment</h2>
                    <div class="pay-opts" role="radiogroup" aria-label="Payment method">
                        <label class="pay-opt on">
                            <input type="radio" name="payment_method" value="sslcommerz" checked>
                            <span class="pay-radio on" aria-hidden="true"></span>
                            <span class="ci-info">
                                <b class="pay-title">Pay online — SSLCommerz secure checkout
                                    @if ($demoGateway)<span class="pay-demo-tag">SANDBOX / DEMO</span>@endif                                </b>
                                <small class="pay-desc">
                                    Cards, bKash, Nagad and other mobile banking channels are chosen on the SSLCommerz payment page
                                    (shown only if enabled on the merchant account).
                                </small>
                                <span class="pay-brands" aria-label="Accepted payment channels">
                                    {!! pay_brand('sslcommerz', 'SSLCommerz') !!}
                                    {!! pay_brand('bkash', 'bKash') !!}
                                    {!! pay_brand('nagad', 'Nagad') !!}
                                </span>
                                @if ($demoGateway)                                    <small class="pay-demo-note">
                                        Demo mode: you will see a simulated payment page. No real money is charged and the order is
                                        clearly marked as a demo payment.
                                    </small>
                                @endif                            </span>
                        </label>
                        @if ($showBkash)                            <label class="pay-opt">
                                <input type="radio" name="payment_method" value="bkash">
                                <span class="pay-radio" aria-hidden="true"></span>
                                <span class="ci-info">
                                    <b class="pay-title">Pay with bKash
                                        @if (!$bkashReady)<span class="pay-demo-tag">SANDBOX / DEMO</span>@endif                                    </b>
                                    <small class="pay-desc">
                                        bKash Tokenized Checkout — you approve the payment from your bKash wallet,
                                        then we are sent back here automatically.
                                    </small>
                                    <span class="pay-brands" aria-label="bKash">{!! pay_brand('bkash', 'bKash') !!}</span>
                                    @if (!$bkashReady)                                        <small class="pay-demo-note">
                                            Demo mode: a simulated bKash page is shown. No real money is charged and the
                                            order is clearly marked as a demo payment.
                                        </small>
                                    @endif                                </span>
                            </label>
                        @endif                        @if ($showNagad)                            <label class="pay-opt">
                                <input type="radio" name="payment_method" value="nagad">
                                <span class="pay-radio" aria-hidden="true"></span>
                                <span class="ci-info">
                                    <b class="pay-title">Pay with Nagad
                                        @if (!$nagadReady)<span class="pay-demo-tag">SANDBOX / DEMO</span>@endif                                    </b>
                                    <small class="pay-desc">
                                        Nagad merchant checkout — confirm the payment in your Nagad app or on the
                                        hosted Nagad page.
                                    </small>
                                    <span class="pay-brands" aria-label="Nagad">{!! pay_brand('nagad', 'Nagad') !!}</span>
                                    @if (!$nagadReady)                                        <small class="pay-demo-note">
                                            Demo mode: a simulated Nagad page is shown. No real money is charged and the
                                            order is clearly marked as a demo payment.
                                        </small>
                                    @endif                                </span>
                            </label>
                        @endif                        <label class="pay-opt">
                            <input type="radio" name="payment_method" value="cod">
                            <span class="pay-radio" aria-hidden="true"></span>
                            <span class="ci-info">
                                <b class="pay-title">Cash on Delivery</b>
                                <small class="pay-desc">Pay the courier in cash when your order arrives · a small COD fee applies</small>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
        </form>

        <!-- ==================== summary ==================== -->
        <aside class="co-sum" id="order-summary">
            <h2>Your order</h2>
            <ul class="co-items">
                @foreach ($snap['items'] as $p)                    <li>
                        <img src="{{ $p['i'] }}" alt="" loading="lazy">
                        <span class="ci-info">
                            <small class="ci-brand">{{ $p['b'] }}</small>
                            <b class="ci-name">{{ $p['n'] }}</b>
                            @if (!empty($p['variant_label']))<small class="ci-sub">{{ $p['variant_label'] }}</small>@endif                            <small class="ci-sub">Qty {!! (int)$p['qty'] !!}</small>
                        </span>
                        <b class="ci-total" data-price="{!! (int)$p['line'] !!}">{!! money($p['line']) !!}</b>
                    </li>
                @endforeach            </ul>

            <div class="sum-coupon">
                <input id="coupon-input" class="co-input" placeholder="Coupon code" autocomplete="off">
                <button type="button" id="coupon-apply" class="ok-btn primary" style="padding:10px 14px">Apply</button>
            </div>
            <p class="sum-coupon-msg" id="coupon-msg" hidden></p>

            <dl class="co-totals">
                <div><dt>Subtotal</dt><dd data-price="{!! (int)$subtotal !!}">{!! money($subtotal) !!}</dd></div>
                <div id="sum-discount-row" class="save" hidden><dt>Discount <small id="sum-coupon-code"></small></dt><dd id="sum-discount">−{!! money(0) !!}</dd></div>
                <div><dt>Shipping</dt><dd id="sum-shipping">—</dd></div>
                <div id="sum-cod-row" hidden><dt>COD fee</dt><dd id="sum-cod">—</dd></div>
                <div class="total"><dt>Total</dt><dd id="sum-total" data-price="{!! (int)$subtotal !!}">{!! money($subtotal) !!}</dd></div>
            </dl>
            <input type="hidden" id="sum-base" value="{!! (int)$subtotal !!}">

            <button class="ok-btn primary co-place" id="pay-btn" type="submit" form="checkout-form"
                    data-online-label="{!! $demoGateway ? 'Continue to demo payment' : 'Continue to payment' !!}">
                <span id="pay-btn-label">{!! $demoGateway ? 'Continue to demo payment' : 'Continue to payment' !!}</span> ·
                <span id="pay-btn-total" data-price="{!! (int)$subtotal !!}">{!! money($subtotal) !!}</span>
            </button>

            <p class="co-placing-note" style="display:flex;align-items:center;gap:6px;justify-content:center">
                🔒 256-bit SSL encrypted · Your details never touch our servers during payment
            </p>

            <ul class="co-trust" style="list-style:none;padding:0;margin-top:14px">
                @foreach ($trust as [$icon, $txt])                    <li>{!! ic($icon, 'ic-bh') !!} {{ $txt }}</li>
                @endforeach            </ul>

            <a class="co-back" href="cart">← Back to cart</a>
        </aside>
    </div>
</main>

<script src="https://sandbox.sslcommerz.com/embed.min.js" defer></script>
<script>
/* Checkout: validation, live totals, drag-drop Rx, coupon via /api/quote.php,
   SSLCommerz embed popup (window.open fallback, demo mode uses local gateway). */
(function () {
    var form = document.getElementById('checkout-form');
    if (!form) return;
    var base = parseInt(document.getElementById('sum-base').value, 10) || 0;
    var discount = 0, couponCode = '';
    var DISTRICTS = {!! json_encode(\App\Support\Districts::all(), JSON_UNESCAPED_UNICODE) !!};

    function money(n) { return '৳' + Math.round(n).toLocaleString(); }
    function courier() { return form.querySelector('input[name=courier]:checked'); }
    function payment() { return form.querySelector('input[name=payment_method]:checked').value; }
    function shipFee() { var c = courier(); return c ? (parseFloat(c.getAttribute('data-fee')) || 0) : 0; }
    function codFee() {
        if (payment() !== 'cod') return 0;
        var c = courier(); return c ? (parseFloat(c.getAttribute('data-codfee')) || 0) : 0;
    }

    function recalc() {
        var s = shipFee(), cf = codFee();
        var total = Math.max(0, base - discount) + s + cf;
        document.getElementById('sum-shipping').innerHTML = s === 0 ? '<em style="font-style:normal;color:var(--green,#16813b);font-weight:800">FREE</em>' : money(s);
        var codRow = document.getElementById('sum-cod-row'); codRow.hidden = cf === 0;
        document.getElementById('sum-cod').textContent = money(cf);
        var el = document.getElementById('sum-total'); el.textContent = money(total);
        if (window.bHerb && bHerb.setPrice) bHerb.setPrice(el, total);
        var bt = document.getElementById('pay-btn-total'); bt.textContent = money(total);
        if (window.bHerb && bHerb.setPrice) bHerb.setPrice(bt, total);
        document.getElementById('pay-btn-label').textContent = payment() === 'cod'
            ? 'Place order'
            : (document.getElementById('pay-btn').getAttribute('data-online-label') || 'Continue to payment');
    }
    form.addEventListener('change', recalc);

    /* courier / payment card selection */
    form.querySelectorAll('.courier-opt input').forEach(function (r) {
        r.addEventListener('change', function () {
            form.querySelectorAll('.courier-opt').forEach(function (c) { c.classList.remove('on'); });
            r.closest('.courier-opt').classList.add('on');
        });
    });
    form.querySelectorAll('.pay-opt input').forEach(function (r) {
        r.addEventListener('change', function () {
            form.querySelectorAll('.pay-opt').forEach(function (c) { c.classList.remove('on'); });
            r.closest('.pay-opt').classList.add('on');
            form.querySelectorAll('.pay-radio').forEach(function (pr) { pr.classList.remove('on'); });
            var radio = r.closest('.pay-opt').querySelector('.pay-radio');
            if (radio) radio.classList.add('on');
        });
    });

    /* validation */
    var MSG = {
        name: 'Enter your full name (at least 2 characters).',
        phone: 'Enter a valid Bangladeshi mobile, e.g. 01712345678.',
        email: 'Enter a valid email address.',
        address: 'Enter your full delivery address (at least 10 characters).',
        area: 'Select your district from the list.',
    };
    function showErr(field, msg) {
        var el = form.querySelector('[data-err="' + field + '"]');
        if (el) { el.textContent = msg || ''; el.style.display = msg ? 'block' : 'none'; }
        var input = form.elements[field];
        if (input && input.classList) input.classList.toggle('err', !!msg);
        var proxy = form.querySelector('[data-for="' + field + '"]');   /* searchable district input */
        if (proxy) { proxy.classList.toggle('err', !!msg); proxy.setAttribute('aria-invalid', msg ? 'true' : 'false'); }
    }
    function validate() {
        var ok = true, data = {};
        ['name', 'phone', 'email', 'address', 'area', 'note'].forEach(function (k) {
            data[k] = (form.elements[k] ? form.elements[k].value : '').trim();
        });
        showErr('name', data.name.length < 2 ? MSG.name : ''); if (data.name.length < 2) ok = false;
        var phoneOk = /^(?:\+?880|0)1[3-9]\d{8}$/.test(data.phone.replace(/[\s\-]/g, ''));
        showErr('phone', phoneOk ? '' : MSG.phone); if (!phoneOk) ok = false;
        var emailOk = data.email === '' || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email);
        showErr('email', emailOk ? '' : MSG.email); if (!emailOk) ok = false;
        showErr('address', data.address.length < 10 ? MSG.address : ''); if (data.address.length < 10) ok = false;
        var areaOk = DISTRICTS.indexOf(data.area) !== -1;
        showErr('area', areaOk ? '' : MSG.area); if (!areaOk) ok = false;
        if (!ok) {
            var first = form.querySelector('.co-err:not([style*="none"])');
            if (first) {
                var target = first.closest('.co-field');
                if (target) target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
        return ok ? data : null;
    }
    form.querySelectorAll('input,textarea').forEach(function (el) {
        if (el.hasAttribute('data-for')) return;      /* the district search box validates on selection/submit */
        el.addEventListener('blur', function () { validate(); });
    });

    /* ---------------- searchable district list (progressive enhancement of the <select>) ---------------- */
    (function districtCombo() {
        var sel = form.elements.area;
        if (!sel || sel.tagName !== 'SELECT') return;
        var label = form.querySelector('label[for="co-area"]');
        var groups = [];
        Array.prototype.forEach.call(sel.querySelectorAll('optgroup'), function (g) {
            groups.push({
                name: g.label.replace(/ division$/, ''),
                items: Array.prototype.map.call(g.querySelectorAll('option'), function (o) { return o.value; })
            });
        });

        var wrap = document.createElement('div');
        wrap.className = 'dd';
        var input = document.createElement('input');
        input.type = 'text';
        input.id = 'co-area-input';
        input.className = 'co-input dd-input';
        input.setAttribute('data-for', 'area');
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-expanded', 'false');
        input.setAttribute('aria-controls', 'co-area-list');
        input.setAttribute('aria-haspopup', 'listbox');
        input.setAttribute('autocomplete', 'off');
        input.setAttribute('autocapitalize', 'off');
        input.setAttribute('spellcheck', 'false');
        input.setAttribute('placeholder', 'Search or select your district (64 districts)');
        var caret = document.createElement('span');
        caret.className = 'dd-caret'; caret.setAttribute('aria-hidden', 'true'); caret.textContent = '▾';
        var list = document.createElement('ul');
        list.id = 'co-area-list'; list.className = 'dd-list'; list.setAttribute('role', 'listbox');
        list.setAttribute('aria-label', 'Districts'); list.hidden = true;
        wrap.appendChild(input); wrap.appendChild(caret); wrap.appendChild(list);
        sel.parentNode.insertBefore(wrap, sel);
        sel.hidden = true; sel.setAttribute('aria-hidden', 'true'); sel.tabIndex = -1;
        sel.removeAttribute('required');                      /* validation is handled by validate() */
        if (label) label.setAttribute('for', 'co-area-input');

        var opts = [], active = -1;
        function build() {
            list.innerHTML = '';
            opts = [];
            groups.forEach(function (g) {
                var gl = document.createElement('li');
                gl.className = 'dd-group'; gl.setAttribute('role', 'presentation'); gl.textContent = g.name + ' division';
                gl.setAttribute('data-group', g.name);
                list.appendChild(gl);
                g.items.forEach(function (name) {
                    var li = document.createElement('li');
                    li.className = 'dd-opt'; li.setAttribute('role', 'option');
                    li.id = 'dd-' + name.toLowerCase().replace(/[^a-z]+/g, '-');
                    li.setAttribute('data-value', name); li.setAttribute('data-group', g.name);
                    li.textContent = name;
                    li.setAttribute('aria-selected', sel.value === name ? 'true' : 'false');
                    list.appendChild(li);
                    opts.push(li);
                });
            });
            var none = document.createElement('li');
            none.className = 'dd-empty'; none.setAttribute('role', 'presentation'); none.hidden = true;
            none.textContent = 'No matching district';
            list.appendChild(none);
        }
        build();

        function visible() { return opts.filter(function (o) { return !o.hidden; }); }
        function setActive(i) {
            var v = visible();
            active = v.length ? Math.max(0, Math.min(v.length - 1, i)) : -1;
            opts.forEach(function (o) { o.classList.remove('active'); });
            if (active >= 0) {
                v[active].classList.add('active');
                input.setAttribute('aria-activedescendant', v[active].id);
                v[active].scrollIntoView({ block: 'nearest' });
            } else {
                input.removeAttribute('aria-activedescendant');
            }
        }
        function filter(q) {
            q = q.trim().toLowerCase();
            /* district-name matches win; a division name (e.g. "Sylhet") lists its districts only when no district matches */
            var byName = opts.some(function (o) { return o.getAttribute('data-value').toLowerCase().indexOf(q) !== -1; });
            opts.forEach(function (o) {
                var hit = !q || (byName
                    ? o.getAttribute('data-value').toLowerCase().indexOf(q) !== -1
                    : o.getAttribute('data-group').toLowerCase().indexOf(q) === 0);
                o.hidden = !hit;
            });
            Array.prototype.forEach.call(list.querySelectorAll('.dd-group'), function (g) {
                g.hidden = !opts.some(function (o) { return !o.hidden && o.getAttribute('data-group') === g.getAttribute('data-group'); });
            });
            list.querySelector('.dd-empty').hidden = visible().length > 0;
        }
        function open() {
            list.hidden = false; input.setAttribute('aria-expanded', 'true'); wrap.classList.add('open');
        }
        function close() {
            list.hidden = true; input.setAttribute('aria-expanded', 'false'); wrap.classList.remove('open');
            input.removeAttribute('aria-activedescendant'); active = -1;
            opts.forEach(function (o) { o.classList.remove('active'); });
        }
        function choose(name) {
            sel.value = name;
            input.value = name;
            opts.forEach(function (o) { o.setAttribute('aria-selected', o.getAttribute('data-value') === name ? 'true' : 'false'); });
            showErr('area', '');
            close();
            sel.dispatchEvent(new Event('change', { bubbles: true }));
        }
        function reset() { sel.value = ''; opts.forEach(function (o) { o.setAttribute('aria-selected', 'false'); }); }

        if (sel.value) input.value = sel.value;               /* back-button / autofill restore */

        input.addEventListener('focus', function () { filter(''); open(); setActive(Math.max(0, visible().findIndex(function (o) { return o.getAttribute('data-value') === sel.value; }))); });
        input.addEventListener('click', function () { if (list.hidden) { filter(''); open(); } });
        input.addEventListener('input', function () {
            reset(); filter(input.value); open(); setActive(0);
        });
        input.addEventListener('keydown', function (e) {
            var v = visible();
            if (e.key === 'ArrowDown') { e.preventDefault(); if (list.hidden) { filter(''); open(); } setActive(active + 1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); if (list.hidden) { filter(''); open(); } setActive(active - 1); }
            else if (e.key === 'Home' && !list.hidden) { e.preventDefault(); setActive(0); }
            else if (e.key === 'End' && !list.hidden) { e.preventDefault(); setActive(v.length - 1); }
            else if (e.key === 'Enter') {
                if (!list.hidden && active >= 0 && v[active]) { e.preventDefault(); choose(v[active].getAttribute('data-value')); }
                else if (!list.hidden) { e.preventDefault(); }
            }
            else if (e.key === 'Escape' && !list.hidden) { e.preventDefault(); e.stopPropagation(); close(); }
        });
        list.addEventListener('mousedown', function (e) { e.preventDefault(); });   /* keep focus in the input */
        list.addEventListener('click', function (e) {
            var o = e.target.closest('.dd-opt');
            if (o) { choose(o.getAttribute('data-value')); input.focus(); close(); }
        });
        caret.addEventListener('mousedown', function (e) { e.preventDefault(); if (list.hidden) { input.focus(); } else { close(); } });
        input.addEventListener('blur', function () {
            /* typed an exact district name but did not pick it: accept it */
            var typed = input.value.trim().toLowerCase();
            if (!sel.value && typed) {
                var hit = opts.filter(function (o) { return o.getAttribute('data-value').toLowerCase() === typed; })[0];
                if (hit) { choose(hit.getAttribute('data-value')); return; }
            }
            if (!sel.value) input.value = typed ? input.value : '';
            close();
            if (!sel.value && input.value.trim()) showErr('area', MSG.area);
        });
        document.addEventListener('click', function (e) { if (!wrap.contains(e.target)) close(); });
    })();

    /* ---------------- prescription drag & drop ---------------- */
    var drop = document.getElementById('rx-drop');
    var fileInput = document.getElementById('rx-file');
    var chip = document.getElementById('rx-file-chip');
    var MAX = 5 * 1024 * 1024;
    var OK_EXT = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

    function setFile(f) {
        showErr('rx_file', '');
        if (!f) { fileInput.value = ''; chip.hidden = true; drop.classList.remove('has-file'); return; }
        var ext = (f.name.split('.').pop() || '').toLowerCase();
        if (OK_EXT.indexOf(ext) === -1) { showErr('rx_file', 'Only JPG, PNG, WEBP or PDF files are accepted.'); fileInput.value = ''; chip.hidden = true; return; }
        if (f.size > MAX) { showErr('rx_file', 'File must be 5 MB or smaller.'); fileInput.value = ''; chip.hidden = true; return; }
        chip.hidden = false;
        chip.querySelector('.rx-name').textContent = f.name + ' · ' + (f.size / 1024 / 1024).toFixed(2) + ' MB';
        drop.classList.add('has-file');
    }
    if (drop) {
        ['dragenter', 'dragover'].forEach(function (ev) {
            drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('over'); });
        });
        ['dragleave', 'drop'].forEach(function (ev) {
            drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('over'); });
        });
        drop.addEventListener('drop', function (e) {
            if (e.dataTransfer.files.length) { fileInput.files = e.dataTransfer.files; setFile(fileInput.files[0]); }
        });
        drop.addEventListener('click', function (e) {
            if (e.target.closest('.rx-remove')) return;
            fileInput.click();
        });
        drop.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); fileInput.click(); }
        });
        fileInput.addEventListener('change', function () { setFile(fileInput.files[0]); });
        chip.querySelector('.rx-remove').addEventListener('click', function (e) {
            e.stopPropagation(); fileInput.value = ''; chip.hidden = true; drop.classList.remove('has-file');
        });
    }

    /* ---------------- coupon via /api/quote.php ---------------- */
    var couponBtn = document.getElementById('coupon-apply');
    var couponInput = document.getElementById('coupon-input');
    var couponMsg = document.getElementById('coupon-msg');
    if (couponBtn) {
        couponBtn.addEventListener('click', function () {
            var code = couponInput.value.trim().toUpperCase();
            couponInput.value = code;
            if (code === '') { discount = 0; couponCode = ''; couponMsg.hidden = true; recalc(); return; }
            couponBtn.disabled = true;
            fetch('api/quote.php?code=' + encodeURIComponent(code) + '&subtotal=' + base, { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (j) {
                    couponBtn.disabled = false;
                    couponMsg.hidden = false;
                    if (j.ok) {
                        discount = parseFloat(j.discount) || 0;
                        couponCode = code;
                        couponMsg.textContent = j.message || ('Coupon applied — you save ' + money(discount) + '.');
                        couponMsg.className = 'sum-coupon-msg ok';
                        document.getElementById('sum-discount-row').hidden = false;
                        document.getElementById('sum-coupon-code').textContent = '(' + code + ')';
                        document.getElementById('sum-discount').textContent = '−' + money(discount);
                    } else {
                        discount = 0; couponCode = '';
                        couponMsg.textContent = j.message || 'That coupon code is not valid.';
                        couponMsg.className = 'sum-coupon-msg bad';
                        document.getElementById('sum-discount-row').hidden = true;
                    }
                    recalc();
                })
                .catch(function () {
                    couponBtn.disabled = false;
                    couponMsg.hidden = false;
                    couponMsg.textContent = 'Could not check the coupon — please try again.';
                    couponMsg.className = 'sum-coupon-msg bad';
                });
        });
    }

    /* ---------------- submit: COD or an online gateway ---------------- */
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var data = validate();
        if (!data) return;
        var c = courier();

        var fd = new FormData(form);
        fd.set('courier', c ? c.value : 'redx');
        fd.set('payment_method', payment());
        fd.set('coupon', couponCode);
        var btn = document.getElementById('pay-btn');
        btn.disabled = true;
        var label = document.getElementById('pay-btn-label');
        var prevLabel = label.textContent;
        label.textContent = 'Processing…';

        fetch('api/checkout.php', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
            .then(function (res) {
                btn.disabled = false;
                label.textContent = prevLabel;
                var j = res.j;
                if (!res.ok || j.status !== 'success') {
                    if (j.errors) { Object.keys(j.errors).forEach(function (k) { showErr(k, j.errors[k]); }); }
                    if (window.bHerb && bHerb.toast) bHerb.toast(j.reason || 'Checkout failed — please review the form.', 'bad');
                    return;
                }
                if (j.redirect) { window.location.href = j.redirect; return; }

                var url = j.data && j.data.GatewayPageURL;
                if (!url) { window.location.href = 'order-success'; return; }

                /* official embed.min.js popup when loaded */
                if (window.SSLCommerz && !j.demo && j.postdata) {
                    try {
                        var pop = document.createElement('button');
                        pop.id = 'sslczPayBtn';
                        pop.setAttribute('token', 'init');
                        pop.setAttribute('postdata', JSON.stringify(j.postdata));
                        pop.setAttribute('order', j.order_number);
                        pop.setAttribute('endpoint', 'api/checkout.php');
                        document.body.appendChild(pop);

                        var script = document.querySelector('script.sslcz');
                        if (!script) {
                            script = document.createElement('script');
                            script.className = 'sslcz';
                            script.src = 'https://sandbox.sslcommerz.com/embed.min.js';
                            script.setAttribute('data-payment_url', j.gateway_api || 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php');
                            document.head.appendChild(script);
                        } else {
                            script.setAttribute('data-payment_url', j.gateway_api || 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php');
                        }

                        window.SSLCommerz({
                            payment_status: url,
                            customer: { name: data.name, phone: data.phone, email: data.email },
                            onSuccess: function () { window.location.href = 'order-success'; },
                            onFail: function () { window.location.href = 'checkout?pay=fail'; },
                            onCancel: function () { window.location.href = 'checkout?pay=cancelled'; },
                        });
                        return;
                    } catch (err) { /* fall through */ }
                }

                /* demo mode or embed failure: window.open (popup) */
                window.open(url, 'sslcz', 'width=520,height=680,noopener');
                var tries = 0;
                var poll = setInterval(function () {
                    tries++;
                    if (tries > 60) { clearInterval(poll); return; }
                    fetch('api/cart.php?action=status', { credentials: 'same-origin' })
                        .then(function (r) { return r.json(); })
                        .then(function (s) { if (s.count === 0) { clearInterval(poll); window.location.href = 'order-success'; } })
                        .catch(function () {});
                }, 1000);
            })
            .catch(function () {
                btn.disabled = false;
                label.textContent = prevLabel;
                if (window.bHerb && bHerb.toast) bHerb.toast('Network error — please try again.', 'bad');
            });
    });

    recalc();
})();
</script>

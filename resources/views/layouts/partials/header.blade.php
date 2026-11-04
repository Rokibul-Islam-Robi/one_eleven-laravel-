{{-- ============================================================
     REPLACE everything from <!-- top bar --> down to the closing </header>
     in your layout. Mobile menu, cart drawer and auth modals stay as-is.
     Add this line after home.css in <head>:
     <link rel="stylesheet" href="assets/css/header-iherb.css?v={!! $cssV !!}">
     ============================================================ --}}

<!-- ============================= top bar ============================= -->
<div class="topbar">
    <div class="tb-in">
        <div class="tb-pills">
            @php
                $pill1 = storefront_content('pill_1', ['title' => 'Supplements Experts Take Daily', 'link_url' => 'category.php?cat=supplements']);
                $pill2 = storefront_content('pill_2', ['title' => 'Holiday Gift Guide', 'link_url' => 'deals']);
            @endphp
            <a class="promo-pill" href="{{ $pill1['link_url'] }}">
                {!! ic('sparkle', 'ic-pill') !!}<span>{{ $pill1['title'] }}</span>{!! ic('chevron', 'ic-pill-arrow') !!}
            </a>
            <a class="promo-pill plain" href="{{ $pill2['link_url'] }}">
                {!! ic('tag', 'ic-pill') !!}<span>{{ $pill2['title'] }}</span>{!! ic('chevron', 'ic-pill-arrow') !!}
            </a>
        </div>

        <div class="tb-meta">
            {{-- Clickable pill: opens the Site Preferences popup --}}
            <button type="button" class="tb-locale" id="prefs-open" aria-haspopup="dialog"
                    aria-controls="prefs-modal" aria-label="Site preferences">
                <svg class="ic-globe" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3.2 3 14.8 0 18M12 3c-3 3.2-3 14.8 0 18"/>
                </svg>
                <span id="prefs-region-code">BD</span><i class="tb-sdiv"></i><span id="prefs-lang-code">EN</span><i class="tb-sdiv"></i><span id="prefs-cur-code">USD</span>
            </button>

            {{-- Hidden originals: your existing JS keeps reading/writing these ids --}}
            <div class="prefs-sync" hidden>
                <select id="ship-select-t" aria-label="Shipping region">
                    @foreach ($REGIONS as $rg)
                        <option value="{{ $rg['code'] }}" data-currency="{{ $rg['currency'] }}"
                            {!! ($rg['code'] === 'BD') ? 'selected' : '' !!}>{{ $rg['label'] }}</option>
                    @endforeach
                </select>
                <select id="lang-select" aria-label="Language">
                    @foreach ($LANGUAGES as $l)
                        <option value="{{ $l['code'] }}">{{ $l['label'] }}</option>
                    @endforeach
                </select>
                <select id="currency-select" aria-label="Currency">
                    @foreach ($CURRENCIES as $c)
                        <option value="{{ $c['code'] }}" data-rate="{!! $c['rate'] !!}"
                            data-locale="{{ $c['locale'] }}" data-symbol="{{ $c['symbol'] }}">{{ $c['label'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

<!-- ============================= header ============================= -->
<header class="hdr">
    <div class="hdr-bar">
        <div class="hdr-in">
            <button class="hamb" id="hamb" aria-label="Open menu" aria-controls="mobmenu" aria-expanded="false">☰</button>
            <a class="logo" href="./">One Eleven</a>

            <form class="sbox hdr-search-wrap" role="search" action="search" method="get" id="search-form">
                <input id="site-search" type="search" name="q" placeholder="Search all of bHerb"
                       value="{{ $search }}" aria-label="Search all bHerb" autocomplete="off"
                       role="combobox" aria-expanded="false" aria-controls="search-so" aria-autocomplete="list">
                <button type="submit" aria-label="Search">{!! ic('search', 'ic-search') !!}</button>
                <div class="so" id="search-so" role="listbox" aria-label="Search suggestions" hidden></div>
            </form>

            <div class="acts">
                <div class="act acct" id="account-widget">
                    <button type="button" class="acct-btn" id="account-btn" aria-haspopup="menu" aria-expanded="false"
                            aria-controls="account-menu" aria-label="Account menu">
                        <span class="act-ico">{!! ic('user', 'ic-act') !!}</span>
                        <small id="account-label">{!! $authUser ? e(auth_display_name($authUser)) : 'Sign in' !!}</small>
                        {!! ic('chevron', 'ic-act-caret') !!}
                    </button>
                    <div class="acct-menu" id="account-menu" role="menu" hidden>
                        <div class="acct-block acct-guest"{!! $authUser ? ' hidden' : '' !!}>
                            <div class="acct-head"><b>Welcome to bHerb</b><small>Sign in with your mobile or email — instant, no OTP.</small></div>
                            <button type="button" class="acct-link" role="menuitem" data-auth-open="login">Sign In</button>
                            <button type="button" class="acct-link" role="menuitem" data-auth-open="create">Create Account</button>
                        </div>
                        <div class="acct-block acct-authed"{!! $authUser ? '' : ' hidden' !!}>
                            <div class="acct-head"><b>Hi, <span id="acct-name">{!! $authUser ? e($authUser['name']) : '' !!}</span></b><small id="acct-contact">{!! $authUser ? e($authUser['contact']) : '' !!}</small></div>
                            <a class="acct-link" role="menuitem" href="account">My account</a>
                            <a class="acct-link" role="menuitem" href="wishlist">Wishlist</a>
                            <form method="post" action="account" data-auth-form data-auth-endpoint="{{ url('/api/auth.php') }}">@csrf
                                <input type="hidden" name="action" value="logout">
                                <input type="hidden" name="redirect" value="{{ $path . ((request()->getQueryString() ?? '') !== '' ? '?' . request()->getQueryString() : '') }}">
                                <button type="submit" class="acct-link acct-signout" role="menuitem">Sign out</button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Wishlist kept in markup (JS references it) but hidden on desktop via CSS; still reachable from account menu --}}
                <a class="act act-wish" href="wishlist" id="wish-link" aria-label="Wishlist">
                    <span class="act-ico">{!! ic('heart', 'ic-act') !!}<i class="dot dot-wish" id="wish-badge"{!! $wishCount ? '' : ' hidden' !!}>{!! $wishCount !!}</i></span>
                </a>

                <button type="button" class="act act-cart" id="cart-open" data-open-cart aria-haspopup="dialog"
                        aria-controls="cart-drawer" aria-label="Open cart">
                    <span class="act-ico">{!! ic('cart', 'ic-act') !!}<i class="dot dot-cart" id="cart-badge"{!! $cartCount ? '' : ' hidden' !!}>{!! $cartCount !!}</i></span>
                </button>
            </div>
        </div>
    </div>

    <!-- ---------------- sub navigation ---------------- -->
    <nav class="mainnav" id="mainnav-desktop" aria-label="Main navigation">
        <div class="mainnav-in">
            <div class="nav-left">
                @php
                    $primaryNav = [
                        'supplements' => 'Supplements', 'sports' => 'Sports', 'bath' => 'Bath', 'beauty' => 'Beauty',
                        'grocery' => 'Grocery', 'home' => 'Home', 'baby' => 'Baby', 'pets' => 'Pets',
                    ];
                @endphp
                @foreach ($primaryNav as $slug => $label)
                    @php
                        $cat = null;
                        foreach ($NAV_CATEGORIES as $n) { if ($n['slug'] === $slug) { $cat = $n; break; } }
                        $groups = $cat ? ($NAV_GROUPS[$slug] ?? null) : null;
                        if ($cat && !$groups) {
                            $groups = [];
                            $per = max(1, (int) ceil(count($cat['subs']) / 3));
                            foreach (['Popular', 'Shop by need', 'More'] as $gi => $gHead) {
                                $slice = array_slice($cat['subs'], $gi * $per, $per);
                                if ($slice) $groups[] = [$gHead, array_map(fn($x) => $x[1], $slice)];
                            }
                        }
                    @endphp

                    @if ($cat)
                        <div class="navitem" data-nav="{{ $slug }}">
                            <button type="button" class="navbtn" aria-expanded="false" aria-haspopup="true">{{ $label }}</button>
                            <div class="navpanel" hidden>
                                <div class="np-in">
                                    <div class="np-main">
                                        <div class="np-head">
                                            <span class="np-ico tone-{{ $cat['tone'] }}">{!! ic($cat['icon'], 'ic-np') !!}</span>
                                            <div><b>{{ $cat['label'] }}</b><small>{{ $cat['blurb'] }}</small></div>
                                            <a class="np-all" href="category.php?cat={{ $slug }}">Shop all {!! ic('arrow', 'ic-arrow-sm') !!}</a>
                                        </div>
                                        <div class="np-cols">
                                            @foreach ($groups as [$gHead, $gSlugs])
                                                <div class="np-col">
                                                    <b class="np-col-t">{{ $gHead }}</b>
                                                    @foreach ($gSlugs as $sub)
                                                        @continue(!isset($SUB_LABELS[$sub]))
                                                        <a href="category.php?cat={!! rawurlencode($sub) !!}">{{ $SUB_LABELS[$sub] }}</a>
                                                    @endforeach
                                                </div>
                                            @endforeach
                                        </div>
                                        <a class="np-more np-more-foot" href="category.php?cat={{ $slug }}">Browse all {{ $cat['label'] }} {!! ic('arrow', 'ic-arrow-sm') !!}</a>
                                    </div>
                                    <aside class="np-rail" aria-label="Trending brands">
                                        <b class="np-rail-t">{!! ic('sparkle', 'ic-np-rail') !!} Trending Brands</b>
                                        @foreach (array_slice($TRENDING_BRANDS, 0, 8) as $tb)
                                            @php $ini = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', (string) $tb['name']), 0, 2) ?: 'B'); @endphp
                                            <a class="np-brand" href="brand.php?id={{ $tb['slug'] }}">
                                                <span class="np-brand-ico">{{ $ini }}</span>
                                                <span class="np-brand-tx"><b>{{ $tb['name'] }}</b>
                                                    <small>{!! (int) $tb['count'] !!} product{!! (int) $tb['count'] === 1 ? '' : 's' !!} · {!! number_format((int) $tb['reviews']) !!} reviews</small>
                                                </span>
                                                {!! ic('arrow', 'ic-arrow-sm') !!}
                                            </a>
                                        @endforeach
                                        <a class="np-more" href="brands">See all brands {!! ic('arrow', 'ic-arrow-sm') !!}</a>
                                    </aside>
                                </div>
                            </div>
                        </div>
                    @else
                        <a class="navbtn" href="category.php?cat={{ $slug }}">{{ $label }}</a>
                    @endif
                @endforeach

                <i class="nav-sep" aria-hidden="true"></i>

                <div class="navitem navitem-sm" data-nav="brands">
                    <button type="button" class="navbtn" aria-expanded="false" aria-haspopup="true">Brands A–Z</button>
                    <div class="navdrop navdrop-wide" hidden>
                        <div class="np-brands">
                            @foreach (array_slice($NAV_BRANDS, 0, 18) as $brand)
                                <a href="brand.php?id={{ brand_slug($brand) }}">{{ $brand }}</a>
                            @endforeach
                        </div>
                        <a class="np-more" href="brands">See all brands {!! ic('arrow', 'ic-arrow-sm') !!}</a>
                    </div>
                </div>

                <div class="navitem navitem-sm" data-nav="topics">
                    <button type="button" class="navbtn" aria-expanded="false" aria-haspopup="true">Health Topics</button>
                    <div class="navdrop" hidden>
                        @foreach ($NAV_MENUS['topics']['items'] as [$tLabel, $tHref])
                            <a href="{{ $tHref }}">{{ $tLabel }}</a>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="nav-right">
                <a class="navbtn nav-deals" href="deals">Deals</a>
                <a class="navbtn nav-em" href="bestsellers">Best Sellers</a>
                <a class="navbtn nav-em" href="deals">Holiday Gift Guide</a>
                <a class="navbtn nav-em" href="new">New</a>
                <a class="navbtn nav-wellness" href="./#hub">Wellness Hub</a>
            </div>
        </div>
    </nav>
</header>

<div class="mob-backdrop" id="mob-backdrop" hidden></div>
<aside class="mobmenu" id="mobmenu" aria-label="Mobile menu" aria-hidden="true" inert>
    <div class="mm-head">
        <a class="logo" href="./">One Eleven</a>
        <button type="button" class="mm-close" id="mm-close" aria-label="Close menu">×</button>
    </div>
    <div class="mm-scroll">
        <div class="mm-user">
            @if ($authUser)
                <div class="mm-user-name">Hi, {{ $authUser['name'] }}</div>
                <a class="mm-link" href="account">My account</a>
                <a class="mm-link" href="wishlist">Wishlist</a>
                <form method="post" action="account" data-auth-form data-auth-endpoint="{{ url('/api/auth.php') }}">@csrf
                    <input type="hidden" name="action" value="logout">
                    <input type="hidden" name="redirect" value="{{ $path . ((request()->getQueryString() ?? '') !== '' ? '?' . request()->getQueryString() : '') }}">
                    <button type="submit" class="btn-outline">Sign out</button>
                </form>
            @else
                <button type="button" class="btn-green full" data-auth-open="login">Sign in / Register</button>
            @endif
        </div>
        <nav aria-label="Mobile navigation">
            @foreach ($primaryNav as $slug => $label)
                <a class="mm-link" href="category.php?cat={{ $slug }}">{{ $label }}</a>
            @endforeach
            <a class="mm-link" href="deals">Deals</a>
            <a class="mm-link" href="bestsellers">Best Sellers</a>
            <a class="mm-link" href="new">New</a>
            <a class="mm-link" href="wishlist">Wishlist</a>
        </nav>
    </div>
</aside>

<div class="auth-backdrop" id="auth-backdrop" hidden></div>
<div class="auth-modal" id="auth-modal" role="dialog" aria-modal="true" aria-label="Account access" hidden>
    <button type="button" class="auth-close" id="auth-close" aria-label="Close">×</button>
    <section class="auth-panel" data-auth-panel="login">
        <h2>Sign In</h2>
        <p class="auth-sub">Sign in with your mobile or email — instant, no OTP.</p>
        <form method="post" action="account" data-auth-form data-auth-endpoint="{{ url('/api/auth.php') }}">@csrf
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="redirect" value="{{ $path . ((request()->getQueryString() ?? '') !== '' ? '?' . request()->getQueryString() : '') }}">
            <label>Email or mobile number<input required type="text" name="contact" autocomplete="username" placeholder="Email or mobile number"></label>
            <p class="auth-err" data-auth-err role="alert" hidden></p>
            <button type="submit" class="btn-green full">Sign In</button>
        </form>
        <p class="auth-switch">New to bHerb? <button type="button" data-auth-open="create">Create Account</button></p>
    </section>
    <section class="auth-panel" data-auth-panel="create" hidden>
        <h2>Create Account</h2>
        <p class="auth-sub">Instant access to your orders, wishlist and exclusive offers.</p>
        <form method="post" action="account" data-auth-form data-auth-endpoint="{{ url('/api/auth.php') }}">@csrf
            <input type="hidden" name="action" value="create_account">
            <input type="hidden" name="redirect" value="{{ $path . ((request()->getQueryString() ?? '') !== '' ? '?' . request()->getQueryString() : '') }}">
            <label>Full name<input required type="text" name="name" autocomplete="name" placeholder="Your full name"></label>
            <label>Email or mobile number<input required type="text" name="contact" autocomplete="username" placeholder="Email or mobile number"></label>
            <p class="auth-err" data-auth-err role="alert" hidden></p>
            <button type="submit" class="btn-green full">Create Account</button>
        </form>
        <p class="auth-switch">Already have an account? <button type="button" data-auth-open="login">Sign In</button></p>
    </section>
</div>

<!-- ============================= cart drawer ============================= -->
<div class="dr-backdrop" id="dr-backdrop" hidden></div>
<aside class="drawer" id="cart-drawer" role="dialog" aria-modal="true" aria-labelledby="dr-title" aria-hidden="true">
    <div class="dr-head">
        <h2 id="dr-title">Shopping Cart <span class="dr-count" id="dr-count">{!! $cartCount ? '(' . $cartCount . ')' : '' !!}</span></h2>
        <button type="button" class="dr-close" id="dr-close" aria-label="Close cart">{!! ic('close', 'ic-dr') !!}</button>
    </div>
    <div class="dr-body" id="dr-body">@include('partials.cart-drawer-body')</div>
    <div class="dr-foot" id="dr-foot">@include('partials.cart-drawer-foot')</div>
</aside>

<!-- ========================= site preferences popup ========================= -->
<div class="prefs-backdrop" id="prefs-backdrop" hidden></div>
<div class="prefs-modal" id="prefs-modal" role="dialog" aria-modal="true" aria-labelledby="prefs-title" hidden>
    <div class="prefs-head">
        <h2 id="prefs-title">Site Preferences</h2>
        <button type="button" class="prefs-x" id="prefs-close" aria-label="Close">×</button>
    </div>

    <div class="prefs-body">
        <label class="pf-field"><span>Country / Region</span>
            <select id="prefs-region">
                @foreach ($REGIONS as $rg)
                    <option value="{{ $rg['code'] }}" data-currency="{{ $rg['currency'] }}">{{ $rg['label'] }}</option>
                @endforeach
            </select>
        </label>

        <label class="pf-field"><span>Language</span>
            <select id="prefs-lang">
                @foreach ($LANGUAGES as $l)
                    <option value="{{ $l['code'] }}">{{ $l['label'] }}</option>
                @endforeach
            </select>
        </label>

        <label class="pf-field"><span>Currency</span>
            <select id="prefs-currency">
                @foreach ($CURRENCIES as $c)
                    <option value="{{ $c['code'] }}">{{ $c['label'] }}</option>
                @endforeach
            </select>
        </label>

        <label class="pf-field"><span>Units of Measure</span>
            <select id="prefs-units">
                <option value="metric">Metric (kg, cm)</option>
                <option value="imperial">Imperial (lb, in)</option>
            </select>
        </label>

        <div class="pf-info">
            <div class="pf-box">
                <p>Available payment methods</p>
                <div class="pf-chips">
                    <span>Visa</span><span>Mastercard</span><span>Amex</span><span>bKash</span>
                    <span>Discover</span><span>JCB</span><span>Maestro</span><span>UnionPay</span><span>PayPal</span>
                </div>
            </div>
            <div class="pf-box">
                <p>Available shipping methods</p>
                <div class="pf-chips"><span>FedEx</span><span>DHL</span></div>
            </div>
        </div>
    </div>

    <div class="prefs-foot">
        <button type="button" class="pf-btn pf-cancel" id="prefs-cancel">Cancel</button>
        <button type="button" class="pf-btn pf-save" id="prefs-save">Save</button>
    </div>
</div>

<script>
(function () {
    var $ = function (id) { return document.getElementById(id); };
    var modal = $('prefs-modal'), bd = $('prefs-backdrop'), openBtn = $('prefs-open');
    if (!modal || !openBtn) return;

    /* [popup select, hidden original select, pill label] */
    var pairs = [
        ['prefs-region',   'ship-select-t',   'prefs-region-code'],
        ['prefs-lang',     'lang-select',     'prefs-lang-code'],
        ['prefs-currency', 'currency-select', 'prefs-cur-code']
    ];
    var units = $('prefs-units');

    function refreshPill() {
        pairs.forEach(function (p) {
            var src = $(p[1]);
            if (src && src.value) $(p[2]).textContent = String(src.value).toUpperCase();
        });
    }
    function fill() {
        pairs.forEach(function (p) { if ($(p[1])) $(p[0]).value = $(p[1]).value; });
        try { units.value = localStorage.getItem('bherb.units') || 'metric'; } catch (e) {}
    }
    function open() {
        fill();
        bd.hidden = false; modal.hidden = false;
        document.body.classList.add('prefs-lock');
        $('prefs-region').focus();
    }
    function close() {
        bd.hidden = true; modal.hidden = true;
        document.body.classList.remove('prefs-lock');
        openBtn.focus();
    }
    function save() {
        pairs.forEach(function (p) {
            var dst = $(p[1]);
            if (!dst) return;
            var changed = dst.value !== $(p[0]).value;
            dst.value = $(p[0]).value;
            if (changed) dst.dispatchEvent(new Event('change', { bubbles: true }));
        });
        try { localStorage.setItem('bherb.units', units.value); } catch (e) {}
        document.dispatchEvent(new CustomEvent('bherb:units', { detail: units.value }));
        refreshPill();
        close();
    }

    /* choosing a country suggests its currency */
    $('prefs-region').addEventListener('change', function () {
        var cur = this.options[this.selectedIndex].getAttribute('data-currency');
        if (cur && $('prefs-currency').querySelector('option[value="' + cur + '"]')) $('prefs-currency').value = cur;
    });

    openBtn.addEventListener('click', open);
    $('prefs-close').addEventListener('click', close);
    $('prefs-cancel').addEventListener('click', close);
    $('prefs-save').addEventListener('click', save);
    bd.addEventListener('click', close);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) close(); });

    /* keep the pill in sync when your other JS changes the hidden selects */
    pairs.forEach(function (p) { if ($(p[1])) $(p[1]).addEventListener('change', refreshPill); });
    refreshPill();
    window.addEventListener('load', function () { setTimeout(refreshPill, 150); });
})();
</script>
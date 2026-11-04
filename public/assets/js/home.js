/* ============================================================
   bHerb homepage interactions (modular PHP build)
   - cart drawer (slide-over) + AJAX cart mutations
   - search autocomplete (api/search.php)
   - multi level desktop navigation + mobile menu
   - currency / language preferences
   - hero slider (Swiper.js, CSS fallback lives in site.js)
   ============================================================ */
(function () {
    'use strict';

    var $ = function (s, r) { return (r || document).querySelector(s); };
    var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function store(key, val) {
        try {
            if (val === undefined) return localStorage.getItem('bherb.' + key);
            localStorage.setItem('bherb.' + key, val);
        } catch (e) { /* private mode */ }
        return null;
    }

    /* ------------------------------------------------------------
       Toasts
       ------------------------------------------------------------ */
    var toastWrap = $('#toast-wrap');
    function toast(msg, kind) {
        if (!toastWrap) return;
        var el = document.createElement('div');
        el.className = 'toast' + (kind ? ' ' + kind : '');
        el.innerHTML = (kind === 'ok' ? '<svg class="ic-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>' : '') +
            '<span>' + esc(msg) + '</span>';
        toastWrap.appendChild(el);
        setTimeout(function () {
            el.classList.add('out');
            setTimeout(function () { el.remove(); }, 260);
        }, 2600);
    }

    /* ------------------------------------------------------------
       Currency + language preferences
       ------------------------------------------------------------ */
    var currencySelects = $$('#currency-select, #currency-select-m, #currency-select-f');
    var langSelects = $$('#lang-select, #lang-select-m, #lang-select-f');

    function currentCurrency() {
        var code = store('currency') || 'BDT';
        var sel = currencySelects[0];
        var opt = null;
        if (sel) {
            for (var i = 0; i < sel.options.length; i++) {
                if (sel.options[i].value === code) { opt = sel.options[i]; break; }
            }
        }
        return opt ? { code: code, rate: parseFloat(opt.dataset.rate) || 1, locale: opt.dataset.locale || 'en-US', symbol: opt.dataset.symbol || '৳' } :
            { code: 'BDT', rate: 1, locale: 'en-US', symbol: '৳' };
    }

    function formatPrice(base, c) {
        var v = base * c.rate;
        if (c.code === 'BDT') return '৳' + Math.round(v).toLocaleString('en-US');
        try {
            return new Intl.NumberFormat(c.locale, {
                style: 'currency', currency: c.code,
                maximumFractionDigits: v < 10 ? 2 : 0
            }).format(v);
        } catch (e) {
            return c.symbol + v.toFixed(v < 10 ? 2 : 0);
        }
    }

    function applyCurrency() {
        var c = currentCurrency();
        $$('[data-price]').forEach(function (el) {
            var base = parseFloat(el.dataset.price);
            if (isNaN(base)) return;
            el.textContent = formatPrice(base, c);
        });
    }

    function syncSelects(list, value) {
        list.forEach(function (s) { if (s.value !== value) s.value = value; });
    }

    currencySelects.forEach(function (s) {
        s.addEventListener('change', function () {
            store('currency', s.value);
            syncSelects(currencySelects, s.value);
            applyCurrency();
            toast('Prices shown in ' + s.value);
        });
    });

    langSelects.forEach(function (s) {
        s.addEventListener('change', function () {
            store('lang', s.value);
            syncSelects(langSelects, s.value);
            document.documentElement.lang = s.value.toLowerCase();
            toast('Language preference saved: ' + s.value);
        });
    });

    (function initPrefs() {
        var cur = store('currency');
        if (cur) syncSelects(currencySelects, cur);
        var lang = store('lang');
        if (lang) { syncSelects(langSelects, lang); document.documentElement.lang = lang.toLowerCase(); }
        applyCurrency();
    })();

    /* ------------------------------------------------------------
       Ship-to region (utility bar, mobile menu, footer)
       ------------------------------------------------------------ */
    var regionSelects = $$('#ship-select-t, #ship-select-m, #ship-select');
    regionSelects.forEach(function (s) {
        s.addEventListener('change', function () {
            store('region', s.value);
            syncSelects(regionSelects, s.value);
            toast('Shipping to ' + s.options[s.selectedIndex].text.trim());
        });
    });
    (function initRegion() {
        var region = store('region');
        if (region) syncSelects(regionSelects, region);
    })();

    /* ------------------------------------------------------------
       Top bar share + email actions
       ------------------------------------------------------------ */
    $$('[data-share]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var url = encodeURIComponent(location.href);
            var text = encodeURIComponent(document.title + ' — bHerb');
            var kind = btn.getAttribute('data-share');
            if (kind === 'facebook') {
                window.open('https://www.facebook.com/sharer/sharer.php?u=' + url, '_blank', 'noopener,width=640,height=560');
            } else if (kind === 'x') {
                window.open('https://twitter.com/intent/tweet?url=' + url + '&text=' + text, '_blank', 'noopener,width=640,height=560');
            } else {
                location.href = 'mailto:?subject=' + text + '&body=' + url;
            }
        });
    });

    /* ------------------------------------------------------------
       Top bar ticker
       ------------------------------------------------------------ */
    (function ticker() {
        var root = $('#tb-ticker');
        if (!root) return;
        var items = $$('.tb-tick', root);
        if (items.length < 2) return;
        var i = 0;
        setInterval(function () {
            items[i].classList.remove('on');
            i = (i + 1) % items.length;
            items[i].classList.add('on');
        }, 4200);
    })();

    /* ------------------------------------------------------------
       Multi level desktop navigation
       ------------------------------------------------------------ */
    (function desktopNav() {
        var items = $$('.mainnav .navitem');
        if (!items.length) return;
        var closeAll = function (except) {
            items.forEach(function (it) {
                if (it === except) return;
                it.classList.remove('open');
                var b = $('.navbtn', it);
                var p = $('.navpanel,.navdrop', it);
                if (b) b.setAttribute('aria-expanded', 'false');
                if (p) p.hidden = true;
            });
        };
        var open = function (it) {
            closeAll(it);
            it.classList.add('open');
            var b = $('.navbtn', it);
            var p = $('.navpanel,.navdrop', it);
            if (b) b.setAttribute('aria-expanded', 'true');
            if (p) p.hidden = false;
        };
        var close = function (it) {
            it.classList.remove('open');
            var b = $('.navbtn', it);
            var p = $('.navpanel,.navdrop', it);
            if (b) b.setAttribute('aria-expanded', 'false');
            if (p) p.hidden = true;
        };

        items.forEach(function (it) {
            var btn = $('.navbtn', it);
            if (!btn) return;
            var hoverCapable = window.matchMedia('(hover:hover) and (min-width:901px)');

            if (hoverCapable) {
                it.addEventListener('mouseenter', function () { if (hoverCapable.matches) open(it); });
                it.addEventListener('mouseleave', function () { if (hoverCapable.matches) close(it); });
            }
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                it.classList.contains('open') ? close(it) : open(it);
            });
            btn.addEventListener('focus', function () { if (window.innerWidth > 900) open(it); });
            it.addEventListener('focusout', function (e) {
                if (!it.contains(e.relatedTarget)) close(it);
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeAll(null);
        });
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.mainnav')) closeAll(null);
        });
    })();

    /* ------------------------------------------------------------
       Mobile menu
       ------------------------------------------------------------ */
    (function mobileMenu() {
        var menu = $('#mobmenu'), backdrop = $('#mob-backdrop'), hamb = $('#hamb'), closeBtn = $('#mm-close');
        if (!menu || !hamb) return;

        var setOpen = function (open) {
            menu.classList.toggle('open', open);
            menu.setAttribute('aria-hidden', open ? 'false' : 'true');
            menu.inert = !open;
            hamb.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (backdrop) backdrop.hidden = !open;
            document.body.classList.toggle('mm-lock', open);
            document.body.style.overflow = open ? 'hidden' : (drawer && drawer.classList.contains('open') ? 'hidden' : '');
            if (open) { var f = menu.querySelector('button,a'); if (f) f.focus(); }
        };

        hamb.addEventListener('click', function () { setOpen(!menu.classList.contains('open')); });
        if (closeBtn) closeBtn.addEventListener('click', function () { setOpen(false); });
        if (backdrop) backdrop.addEventListener('click', function () { setOpen(false); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && menu.classList.contains('open')) setOpen(false);
        });

        $$('.mm-acc', menu).forEach(function (btn) {
            btn.addEventListener('click', function () {
                var panel = btn.nextElementSibling;
                var isOpen = btn.getAttribute('aria-expanded') === 'true';
                $$('.mm-acc', menu).forEach(function (other) {
                    if (other === btn) return;
                    other.setAttribute('aria-expanded', 'false');
                    if (other.nextElementSibling) other.nextElementSibling.hidden = true;
                });
                btn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
                if (panel) panel.hidden = isOpen;
            });
        });
        $$('a', menu).forEach(function (a) {
            a.addEventListener('click', function () { setOpen(false); });
        });
    })();

    /* ------------------------------------------------------------
       Cart drawer (slide-over)
       ------------------------------------------------------------ */
    var drawer = $('#cart-drawer'), drBackdrop = $('#dr-backdrop');

    function openDrawer() {
        if (!drawer) return;
        drawer.classList.add('open');
        drawer.setAttribute('aria-hidden', 'false');
        if (drBackdrop) drBackdrop.hidden = false;
        document.body.style.overflow = 'hidden';
        var c = $('#dr-close');
        if (c) setTimeout(function () { c.focus(); }, 60);
    }
    function closeDrawer() {
        if (!drawer) return;
        drawer.classList.remove('open');
        drawer.setAttribute('aria-hidden', 'true');
        if (drBackdrop) drBackdrop.hidden = true;
        if (!document.body.classList.contains('mm-lock')) document.body.style.overflow = '';
    }

    $$('[data-open-cart]').forEach(function (b) { b.addEventListener('click', openDrawer); });
    var drClose = $('#dr-close');
    if (drClose) drClose.addEventListener('click', closeDrawer);
    if (drBackdrop) drBackdrop.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && drawer && drawer.classList.contains('open')) closeDrawer();
    });
    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-close-cart]')) closeDrawer();
    });

    /* ------------------------------------------------------------
       AJAX cart + wishlist
       ------------------------------------------------------------ */
    function applyCartState(res) {
        if (!res || !res.ok) return;
        var badge = $('#cart-badge');
        if (badge) {
            badge.textContent = res.count;
            badge.hidden = !res.count;
        }
        var wishBadge = $('#wish-badge');
        if (wishBadge) {
            wishBadge.textContent = res.wish;
            wishBadge.hidden = !res.wish;
        }
        var count = $('#dr-count');
        if (count) count.textContent = res.count ? '(' + res.count + ')' : '';
        if (res.body != null) { var b = $('#dr-body'); if (b) b.innerHTML = res.body; }
        if (res.foot != null) { var f = $('#dr-foot'); if (f) f.innerHTML = res.foot; }
        if (res.page != null) { var page = $('#cart-content'); if (page) page.innerHTML = res.page; }
        applyCurrency();
    }

    function postCart(form, submitter) {
        var data = new FormData(form);
        if (submitter && submitter.name) data.set(submitter.name, submitter.value);
        if (!data.get('action')) data.set('action', 'add');
        return fetch('api/cart.php', { method: 'POST', body: data, credentials: 'same-origin' })
            .then(function (r) {
                return r.json().then(function (res) {
                    if (!r.ok || !res || res.ok !== true
                        || !Number.isInteger(res.count) || typeof res.subtotal !== 'number'
                        || typeof res.body !== 'string' || typeof res.foot !== 'string'
                        || typeof res.page !== 'string') {
                        throw new Error('Invalid cart response');
                    }
                    return res;
                });
            });
    }

    /* add to cart (product cards, product detail) */
    $$('form[data-add]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            form.classList.add('busy');
            postCart(form).then(function (res) {
                form.classList.remove('busy');
                form.classList.add('done');
                setTimeout(function () { form.classList.remove('done'); }, 900);
                applyCartState(res);
                var qty = parseInt(new FormData(form).get('qty'), 10) || 1;
                toast((res.product || 'Item') + (qty > 1 ? ' × ' + qty : '') + ' added to cart', 'ok');
                openDrawer();
            }).catch(function () {
                form.classList.remove('busy');
                toast('Updating your cart…');
                HTMLFormElement.prototype.submit.call(form);
            });
        });
    });

    /* wishlist toggles — session (api/cart.php) is the source of truth; the UI only paints what it returns */
    function paintWish(form, on) {
        form.classList.toggle('on', on);
        var btn = form.querySelector('button');
        if (btn) {
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            btn.setAttribute('aria-label', on ? 'Remove from wishlist' : 'Add to wishlist');
        }
        var txt = form.querySelector('[data-wish-label]');
        if (txt) txt.textContent = on ? 'Saved to wishlist' : 'Add to wishlist';
    }
    function wishId(form) { var i = form.querySelector('input[name="id"]'); return i ? i.value : ''; }

    $$('form[data-wish]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (form.classList.contains('busy')) return;               /* no duplicate requests */
            var wasOn = form.classList.contains('on');
            form.classList.add('busy');
            if (form.querySelector('button')) form.querySelector('button').setAttribute('aria-busy', 'true');
            postCart(form).then(function (res) {
                form.classList.remove('busy');
                if (form.querySelector('button')) form.querySelector('button').removeAttribute('aria-busy');
                if (!res || !res.ok || typeof res.wished !== 'boolean') throw new Error('bad wishlist response');
                applyCartState(res);
                var id = wishId(form);
                /* keep every heart for this product (card, detail page, related rows) in sync */
                $$('form[data-wish]').forEach(function (f) { if (wishId(f) === id) paintWish(f, res.wished); });
                form.classList.add('pop');
                setTimeout(function () { form.classList.remove('pop'); }, 400);
                toast(res.wished ? 'Saved to your wishlist' : 'Removed from wishlist', res.wished ? 'ok' : '');

                /* wishlist page: un-saving removes the card in place, no reload */
                var page = form.closest('.wishlist-page');
                if (page && !res.wished) {
                    var card = form.closest('.pcard');
                    if (card) {
                        card.classList.add('wish-removing');
                        setTimeout(function () {
                            card.remove();
                            var left = page.querySelectorAll('.pcard').length;
                            var cnt = $('#wish-page-count');
                            if (cnt) cnt.textContent = left ? '(' + left + ')' : '';
                            if (!left) {
                                var grid = $('#wish-grid'), empty = $('#wish-empty');
                                if (grid) grid.hidden = true;
                                if (empty) empty.hidden = false;
                            }
                        }, 260);
                    }
                }
            }).catch(function () {
                /* failure: restore the prior state and say so (never silently flip) */
                form.classList.remove('busy');
                if (form.querySelector('button')) form.querySelector('button').removeAttribute('aria-busy');
                paintWish(form, wasOn);
                toast('Could not update your wishlist — please try again.', 'bad');
            });
        });
    });

    /* delegated: drawer quantity steppers + remove + empty-state buttons */
    if (drawer) {
        drawer.addEventListener('submit', function (e) {
            var form = e.target.closest('form[data-qty], form[data-remove]');
            if (!form) return;
            e.preventDefault();
            if (form.classList.contains('busy')) return;
            var submitter = e.submitter;
            var stepBtn = submitter && submitter.dataset.step;
            form.classList.add('busy');
            Array.prototype.forEach.call(form.querySelectorAll('button'), function (button) {
                button.disabled = true;
            });
            postCart(form, submitter).then(function (res) {
                applyCartState(res);
                if (form.matches('[data-remove]')) {
                    if (!res.count || res.count === 0) toast('Your cart is empty');
                    else toast('Item removed from cart');
                } else if (stepBtn === '-1' || stepBtn === '1') {
                    toast('Quantity updated', 'ok');
                }
            }).catch(function () {
                form.classList.remove('busy');
                Array.prototype.forEach.call(form.querySelectorAll('button'), function (button) {
                    button.disabled = false;
                });
                toast('Could not update your cart — please try again.', 'bad');
            });
        });
    }

    /* ------------------------------------------------------------
       Search autocomplete
       ------------------------------------------------------------ */
    (function search() {
        var input = $('#site-search');
        var box = $('#search-so');
        var form = $('#search-form');
        if (!input || !box || !form) return;

        var timer = null, active = -1, items = [];
        var POPULAR = ['Vitamin C', 'Collagen', 'Fish Oil', 'Probiotics', 'Plant Protein', 'Magnesium', 'K-Beauty', 'Ashwagandha'];
        var recent = [];
        try { recent = JSON.parse(store('recent') || '[]'); } catch (e) { recent = []; }

        function open() { box.hidden = false; input.setAttribute('aria-expanded', 'true'); }
        function close() { box.hidden = true; input.setAttribute('aria-expanded', 'false'); active = -1; }

        function chip(label, removable) {
            if (removable) {
                return '<span class="so-rchip"><button type="button" data-term="' + esc(label) + '">' + esc(label) +
                    '</button><button type="button" class="so-rx" data-drop="' + esc(label) + '" aria-label="Remove">✕</button></span>';
            }
            return '<button type="button" class="so-chip" data-term="' + esc(label) + '">' + esc(label) + '</button>';
        }

        function renderIdle() {
            var html = '';
            if (recent.length) {
                html += '<div class="so-sec"><div class="so-hd"><span>Recent searches</span><button type="button" data-clear>Clear</button></div>' +
                    '<div class="so-chips">' + recent.map(function (t) { return chip(t, true); }).join('') + '</div></div>';
            }
            html += '<div class="so-sec"><div class="so-hd"><span>Popular right now</span></div><div class="so-chips">' +
                POPULAR.map(function (t) { return chip(t, false); }).join('') + '</div></div>';
            box.innerHTML = html;
            open();
        }

        function renderResults(list, q) {
            items = list;
            active = -1;
            if (!list.length) {
                box.innerHTML = '<div class="so-empty">No matches for “' + esc(q) + '”. Try a brand, ingredient or category.</div>' +
                    '<button type="button" class="so-all" data-submit>See all search results</button>';
                open();
                return;
            }
            var html = '<div class="so-sec"><div class="so-hd"><span>Suggestions</span></div><ul class="so-list">';
            list.forEach(function (p, i) {
                html += '<li><a href="' + esc(p.url) + '" role="option" data-i="' + i + '">' +
                    '<img src="' + esc(p.image) + '" alt="" loading="lazy">' +
                    '<span class="so-info"><span class="so-name">' + esc(p.name) + '</span>' +
                    '<span class="so-brand">' + esc(p.brand) + ' · ' + esc(p.category) + '</span></span>' +
                    '<span class="so-right"><span class="so-price">' + esc(p.price) + '</span>' +
                    (p.off ? '<em class="so-off">' + p.off + '% off</em>' : '') + '</span></a></li>';
            });
            html += '</ul></div><button type="button" class="so-all" data-submit>See all results for “' + esc(q) + '”</button>';
            box.innerHTML = html;
            open();
        }

        function setActive(next) {
            var links = $$('.so-list a', box);
            if (!links.length) return;
            active = (next + links.length) % links.length;
            links.forEach(function (a, i) { a.classList.toggle('is-active', i === active); });
            links[active].scrollIntoView({ block: 'nearest' });
        }

        function searchNow(q) {
            fetch('api/search.php?q=' + encodeURIComponent(q), { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) { renderResults(data.items || [], q); })
                .catch(function () { close(); });
        }

        input.addEventListener('focus', function () {
            if (input.value.trim() === '') renderIdle(); else if (box.innerHTML.trim() === '') renderIdle();
            else open();
        });
        input.addEventListener('input', function () {
            var q = input.value.trim();
            clearTimeout(timer);
            if (q === '') { renderIdle(); return; }
            timer = setTimeout(function () { searchNow(q); }, 180);
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown') { e.preventDefault(); if (box.hidden) renderIdle(); setActive(active + 1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(active - 1); }
            else if (e.key === 'Escape') { close(); }
            else if (e.key === 'Enter') {
                var links = $$('.so-list a', box);
                if (active > -1 && links[active]) { e.preventDefault(); links[active].click(); }
                else remember(input.value.trim());
            }
        });

        box.addEventListener('click', function (e) {
            var term = e.target.closest('[data-term]');
            var drop = e.target.closest('[data-drop]');
            var clear = e.target.closest('[data-clear]');
            var submit = e.target.closest('[data-submit]');
            if (term) { input.value = term.dataset.term; remember(term.dataset.term); form.submit(); return; }
            if (drop) {
                e.stopPropagation();
                recent = recent.filter(function (t) { return t !== drop.dataset.drop; });
                store('recent', JSON.stringify(recent));
                renderIdle();
                return;
            }
            if (clear) { recent = []; store('recent', '[]'); renderIdle(); return; }
            if (submit) { remember(input.value.trim()); form.submit(); return; }
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('#search-form')) close();
        });

        var pressedInBox = false;
        box.addEventListener('mousedown', function () {
            pressedInBox = true;
            setTimeout(function () { pressedInBox = false; }, 250);
        });
        input.addEventListener('blur', function () {
            setTimeout(function () {
                if (pressedInBox) return;
                if (box.contains(document.activeElement)) return;
                close();
            }, 160);
        });

        form.addEventListener('submit', function () { remember(input.value.trim()); });

        function remember(q) {
            if (!q) return;
            recent = [q].concat(recent.filter(function (t) { return t.toLowerCase() !== q.toLowerCase(); })).slice(0, 5);
            store('recent', JSON.stringify(recent));
        }
    })();

    /* ------------------------------------------------------------
       Hero slider — Swiper.js engine with custom arrows / dots
       (site.js runs the plain CSS fallback when Swiper is absent)
       ------------------------------------------------------------ */
    (function hero() {
        var vp = $('#hero-slider');
        if (!vp || typeof window.Swiper === 'undefined') return;     /* site.js runs the CSS fallback */
        var dots = $$('.hero-dot');
        var count = $('#hero-count');
        var main = vp.closest('.hero-main');
        if (!dots.length || vp.swiper) return;                      /* one slider only — never initialise twice */

        var reduceMq = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
        var reduce = !!(reduceMq && reduceMq.matches);

        var sw = new Swiper(vp, {
            loop: true,
            speed: reduce ? 0 : 650,                                  /* restrained 500–700ms slide */
            slidesPerView: 1,
            spaceBetween: 0,
            grabCursor: true,
            threshold: 6,
            touchStartPreventDefault: false,                          /* keeps vertical page scroll smooth on touch */
            observer: true,
            observeParents: true,
            keyboard: { enabled: true, onlyInViewport: true },
            navigation: { prevEl: '#hero-prev', nextEl: '#hero-next' },
            a11y: {
                enabled: true,
                prevSlideMessage: 'Previous slide',
                nextSlideMessage: 'Next slide',
                slideLabelMessage: 'Slide {{index}} of {{slidesLength}}'
            },
            autoplay: reduce ? false : { delay: 4000, disableOnInteraction: false, pauseOnMouseEnter: true },
            on: {
                init: function () { sync(this); },
                slideChange: function () { sync(this); }
            }
        });

        function sync(s) {
            var i = s.realIndex;
            dots.forEach(function (d, k) {
                d.classList.toggle('on', k === i);
                if (k === i) d.setAttribute('aria-current', 'true'); else d.removeAttribute('aria-current');
            });
            if (count) count.textContent = (i + 1) + ' / ' + dots.length;
        }

        dots.forEach(function (d, i) {
            d.addEventListener('click', function () { sw.slideToLoop(i); });
        });

        /* pause while the visitor is interacting (hover is handled by Swiper; add keyboard focus + hidden tab) */
        function pause() { if (sw.autoplay && sw.autoplay.running) sw.autoplay.pause(); }
        function resume() { if (sw.autoplay && !reduce && !(main && main.matches(':hover, :focus-within'))) sw.autoplay.resume(); }
        if (main && sw.autoplay) {
            main.addEventListener('focusin', pause);
            main.addEventListener('focusout', function () { setTimeout(resume, 0); });
        }
        document.addEventListener('visibilitychange', function () {
            if (!sw.autoplay) return;
            if (document.hidden) pause(); else resume();
        });
        if (reduceMq && reduceMq.addEventListener) {
            reduceMq.addEventListener('change', function (e) {
                if (!sw.autoplay) return;
                if (e.matches) sw.autoplay.stop(); else sw.autoplay.start();
            });
        }
        window.addEventListener('load', function () { sw.update(); });
    })();

    /* ------------------------------------------------------------
       Account: header dropdown + Sign In / Create Account modals
       ------------------------------------------------------------ */
    (function account() {
        var btn = $('#account-btn'), menu = $('#account-menu'), widget = $('#account-widget');
        var backdrop = $('#auth-backdrop'), modal = $('#auth-modal'), closeBtn = $('#auth-close');
        if (!btn || !menu) return;

        var setMenu = function (open) {
            menu.hidden = !open;
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        };
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            setMenu(menu.hidden);
        });
        document.addEventListener('click', function (e) {
            if (!menu.hidden && widget && !widget.contains(e.target)) setMenu(false);
        });

        var modalReturnFocus = null;
        var showPanel = function (mode) {
            $$('[data-auth-panel]', modal).forEach(function (p) {
                p.hidden = p.getAttribute('data-auth-panel') !== mode;
            });
        };
        var setModal = function (open, mode, trigger) {
            if (!modal || !backdrop) return;
            if (open && modal.hidden) {
                modalReturnFocus = trigger && trigger.closest('#mobmenu') ? $('#hamb') : btn;
            }
            modal.hidden = !open;
            backdrop.hidden = !open;
            document.body.style.overflow = open ? 'hidden' : '';
            if (open) {
                showPanel(mode || 'login');
                $$('[data-auth-err]', modal).forEach(function (p) { p.hidden = true; });
                var f = modal.querySelector('[data-auth-panel]:not([hidden]) input:not([type=hidden])');
                if (f) f.focus();
            } else if (modalReturnFocus) {
                modalReturnFocus.focus();
                modalReturnFocus = null;
            }
        };
        $$('[data-auth-open]').forEach(function (el) {
            el.addEventListener('click', function () {
                var mmClose = $('#mm-close');
                if (mmClose) mmClose.click();          /* close the mobile menu first */
                setMenu(false);
                setModal(true, el.getAttribute('data-auth-open'), el);
            });
        });
        if (closeBtn) closeBtn.addEventListener('click', function () { setModal(false); });
        if (backdrop) backdrop.addEventListener('click', function () { setModal(false); });
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            if (modal && !modal.hidden) setModal(false);
            else if (!menu.hidden) setMenu(false);
        });

        var applyUser = function (u, display) {
            var label = $('#account-label');
            if (label) label.textContent = display || 'Sign In';
            var guest = menu.querySelector('.acct-guest'), authed = menu.querySelector('.acct-authed');
            if (u) {
                var n = $('#acct-name'), c = $('#acct-contact');
                if (n) n.textContent = u.name || '';
                if (c) c.textContent = u.contact || '';
                if (guest) guest.hidden = true;
                if (authed) authed.hidden = false;
            } else {
                if (guest) guest.hidden = false;
                if (authed) authed.hidden = true;
            }
        };

        $$('[data-auth-form]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var data = new FormData(form);
                var action = data.get('action') || 'login';
                var errBox = form.querySelector('[data-auth-err]');
                fetch(form.getAttribute('data-auth-endpoint') || '/api/auth.php', { method: 'POST', body: data, credentials: 'same-origin' })
                    .then(function (r) {
                        return r.json().then(function (j) { return { ok: r.ok, j: j }; });
                    })
                    .then(function (res) {
                        if (!res.ok || !res.j.ok) {
                            if (errBox) { errBox.textContent = (res.j && res.j.error) || 'Something went wrong — please try again.'; errBox.hidden = false; }
                            return;
                        }
                        if (errBox) errBox.hidden = true;
                        if (action === 'logout') {
                            applyUser(null, 'Sign In');
                            toast('You have been signed out');
                        } else {
                            var u = res.j.user || {};
                            applyUser(u, res.j.display || u.name || 'Account');
                            toast((action === 'create_account' ? 'Welcome to bHerb, ' : 'Welcome back, ') + (u.name || ''), 'ok');
                        }
                        setModal(false);
                        setMenu(false);
                        var redirect = data.get('redirect');
                        if (redirect) {
                            var target = new URL(redirect, window.location.href);
                            if (target.origin === window.location.origin) window.location.assign(target.href);
                        }
                    })
                    .catch(function () {
                        if (errBox) {
                            errBox.textContent = 'Could not connect — please try again.';
                            errBox.hidden = false;
                        } else toast('Could not sign out — please try again.');
                    });
            });
        });
    })();

    /* ------------------------------------------------------------
       First-order offer: floating banner → interactive modal
       ------------------------------------------------------------ */
    (function offer() {
        var promo = $('#float-promo'), openBtn = $('#offer-open');
        var modal = $('#offer-modal'), backdrop = $('#offer-backdrop');
        if (!promo || !modal || !backdrop || !openBtn) return;

        var formStep = $('#offer-form-step'), doneStep = $('#offer-done');
        var form = $('#offer-form'), email = $('#offer-email'), err = $('#offer-err');

        var setOpen = function (open) {
            modal.hidden = !open;
            backdrop.hidden = !open;
            document.body.style.overflow = open ? 'hidden' : '';
            if (open && email) email.focus();
        };

        if (store('offer25')) { promo.remove(); return; }   /* already activated earlier */

        openBtn.addEventListener('click', function () { setOpen(true); });
        var closeBtn = $('#offer-close'), doneClose = $('#offer-done-close');
        if (closeBtn) closeBtn.addEventListener('click', function () { setOpen(false); });
        if (doneClose) doneClose.addEventListener('click', function () { setOpen(false); });
        backdrop.addEventListener('click', function () { setOpen(false); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.hidden) setOpen(false);
        });

        if (form) form.addEventListener('submit', function (e) {
            e.preventDefault();
            var mail = ((email && email.value) || '').trim();
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(mail)) {
                if (err) { err.textContent = 'Please enter a valid email address.'; err.hidden = false; }
                return;
            }
            if (err) err.hidden = true;
            store('offer25', mail);
            var doneMail = $('#offer-done-email');
            if (doneMail) doneMail.textContent = mail;
            if (formStep) formStep.hidden = true;
            if (doneStep) doneStep.hidden = false;
            toast('25% off activated — we’ll apply it to your first order', 'ok');
        });
    })();

    /* ------------------------------------------------------------
       Public API for page-level progressive enhancement
       (product variants, checkout quotes, third-party widgets)
       ------------------------------------------------------------ */
    window.bHerb = {
        toast: toast,
        applyCurrency: applyCurrency,
        setPrice: function (el, baseValue) {
            if (!el) return;
            var base = parseFloat(baseValue);
            if (isNaN(base)) return;
            el.setAttribute('data-price', base === Math.round(base) ? String(Math.round(base)) : String(base));
            el.textContent = formatPrice(base, currentCurrency());
        },
        openCart: openDrawer,
        closeCart: closeDrawer,
        refreshCart: function () {
            return fetch('api/cart.php?action=status', { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (res) { applyCartState(res); return res; });
        }
    };
})();

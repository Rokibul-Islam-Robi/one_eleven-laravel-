@php
/* Brand store page.
   Entry points: brand.php?id={brand-slug} and the clean /brand/{slug}
   route. iHerb-style: brand banner + logo, left accordion filter
   sidebar (category, price, rating, offers) and a product grid that
   only shows items belonging to that brand. */

$brandParam = trim((string)(request()->query('id') ?? ''));
if ($brandParam === '') {
    $brandParam = basename(rtrim($path, '/'));      /* clean route fallback */
}

$idx = brand_index();
$brandSlug = $brandParam;
$brand = $idx[$brandSlug] ?? null;

if (!$brand) {
    \App\Support\PageMeta::$status = 404;
    \App\Support\PageMeta::$title = 'Brand not found — bHerb';
    echo '<main class="wrap page"><div class="empty"><h1>Brand not found</h1>'
        . '<p>We could not find that brand. Browse the full directory instead.</p>'
        . '<a class="btn-green" href="brands">Browse all brands</a></div></main>';
    return;
}

$brandName = $brand['name'];
$brandUrl  = 'brand.php?id=' . rawurlencode($brandSlug);
\App\Support\PageMeta::$title = $brandName . ' — bHerb';
\App\Support\PageMeta::$desc = 'Shop ' . $brandName . ' products at bHerb — authentic, quality-checked and delivered across Bangladesh.';

$all = productsByBrand($brandSlug);

/* ------------------------- filters (GET) ---------------------------- */
/** Multi-value filters arrive as arrays (checkbox groups) or CSV strings. */
$multi = static function ($val): array {
    if (is_array($val)) return array_values(array_filter($val, 'is_string'));
    return array_values(array_filter(explode(',', (string)$val)));
};
$fCats  = $multi(request()->query('cat') ?? '');
$fMin   = (int)(request()->query('min') ?? 0);
$fMax   = (int)(request()->query('max') ?? 0);
$fRate  = (float)(request()->query('rating') ?? 0);
$fSale  = request()->query->has('sale');
$fSort  = (string)(request()->query('sort') ?? 'featured');

/* Facet sources computed from the unfiltered brand set. */
$facets = ['cats' => [], 'maxPrice' => 0, 'reviews' => 0];
foreach ($all as $p) {
    $c = (string)($p['c'] ?? '');
    if ($c !== '') {
        if (!isset($facets['cats'][$c])) $facets['cats'][$c] = ['slug' => $c, 'label' => $CAT_LABELS[$c] ?? ucfirst($c), 'count' => 0];
        $facets['cats'][$c]['count']++;
    }
    $facets['maxPrice'] = max($facets['maxPrice'], (int)priceval($p['p']));
    $facets['reviews']  += (int)($p['rc'] ?? 0);
}
$priceSteps = [0, 1000, 2000, 3000, 5000];

/* --------------------------- filtering ------------------------------ */
$list = array_values(array_filter($all, function ($p) use ($fCats, $fMin, $fMax, $fRate, $fSale) {
    if ($fCats && !in_array((string)($p['c'] ?? ''), $fCats, true)) return false;
    $price = (int)priceval($p['p']);
    if ($fMin && $price < $fMin) return false;
    if ($fMax && $price > $fMax) return false;
    if ($fRate && (float)($p['r'] ?? 0) < $fRate) return false;
    if ($fSale && discPct($p) <= 0) return false;
    return true;
}));

usort($list, function ($a, $b) use ($fSort) {
    switch ($fSort) {
        case 'low':  return priceval($a['p']) <=> priceval($b['p']);
        case 'high': return priceval($b['p']) <=> priceval($a['p']);
        case 'name': return strcmp((string)$a['n'], (string)$b['n']);
        case 'rate': return (float)($b['r'] ?? 0) <=> (float)($a['r'] ?? 0);
        case 'disc': return discPct($b) <=> discPct($a);
        default:
            $rank = fn($p) => ($p['row'] ?? '') === 'deals' ? 0 : (($p['row'] ?? '') === 'trending' ? 1 : 2);
            $ra = $rank($a); $rb = $rank($b);
            return $ra !== $rb ? $ra <=> $rb : (int)($b['rc'] ?? 0) <=> (int)($a['rc'] ?? 0);
    }
});

$activeFilters = count($fCats) + ($fMin ? 1 : 0) + ($fMax ? 1 : 0) + ($fRate ? 1 : 0) + ($fSale ? 1 : 0);
$initials = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $brandName), 0, 2) ?: 'B');
@endphp <main class="wrap page brand-page">

    <!-- ==================== brand banner ==================== -->
    <section class="brand-hero">
        <div class="bh-logo tone-{{ $all[0]['t'] ?? 'g' }}">{{ $initials }}</div>
        <div class="bh-copy">
            <div class="breadcrumbs">
                <a href="./">Home</a> / <a href="brands">Brands</a> / {{ $brandName }}
            </div>
            <h1>{{ $brandName }}</h1>
            <p>{!! count($all) !!} products · {!! number_format($facets['reviews']) !!} reviews · Authentic, quality-checked and ready to ship across Bangladesh.</p>
            <div class="bh-badges">
                <span>{!! ic('shield', 'ic-bh') !!} Verified brand</span>
                <span>{!! ic('truck', 'ic-bh') !!} Fast delivery</span>
                <span>{!! ic('refresh', 'ic-bh') !!} Easy returns</span>
            </div>
        </div>
    </section>

    <div class="brand-layout">
        <!-- ==================== filter sidebar ==================== -->
        <aside class="cat-side brand-side filter-side" id="brand-side">
            <form method="get" action="brand.php" id="brand-filters">
                <input type="hidden" name="id" value="{{ $brandSlug }}">
                <input type="hidden" name="sort" value="{{ $fSort }}">

                <div class="bf-head">
                    <b>Filter</b>
                    @if ($activeFilters)                        <a class="bf-clear" href="{{ $brandUrl }}">Clear all</a>
                    @endif                </div>

                <details class="bf-group" open>
                    <summary>{!! ic('chevron', 'ic-bf') !!} Category</summary>
                    <div class="bf-body">
                        @foreach ($facets['cats'] as $facet)                            <label class="bf-check">
                                <input type="checkbox" name="cat[]" value="{{ $facet['slug'] }}"
                                    {!! in_array($facet['slug'], $fCats, true) ? 'checked' : '' !!}>
                                <span>{{ $facet['label'] }}</span>
                                <small>{!! (int)$facet['count'] !!}</small>
                            </label>
                        @endforeach                    </div>
                </details>

                <details class="bf-group" open>
                    <summary>{!! ic('chevron', 'ic-bf') !!} Price</summary>
                    <div class="bf-body">
                        <div class="bf-price">
                            <label>Min <input type="number" name="min" min="0"  value="{!! $fMin ?: '' !!}" placeholder="0"></label>
                            <i>–</i>
                            <label>Max <input type="number" name="max" min="0"  value="{!! $fMax ?: '' !!}" placeholder="{!! (int)$facets['maxPrice'] !!}"></label>
                        </div>
                        <div class="bf-price-pills">
                            @foreach ($priceSteps as $i => $step)                                @php
$hi = $priceSteps[$i + 1] ?? 0; if (!$hi) continue;
@endphp                                <button type="button" class="pill{!! ($fMin === $step && $fMax === $hi) ? ' on' : '' !!}"
                                    data-min="{!! $step !!}" data-max="{!! $hi !!}">৳{!! number_format($step) !!}–{!! number_format($hi) !!}</button>
                            @endforeach                        </div>
                    </div>
                </details>

                <details class="bf-group" open>
                    <summary>{!! ic('chevron', 'ic-bf') !!} Rating</summary>
                    <div class="bf-body">
                        @foreach ([4.5, 4.0, 3.5] as $r)                            <label class="bf-check">
                                <input type="radio" name="rating" value="{!! $r !!}" {!! $fRate == $r ? 'checked' : '' !!}>
                                <span class="bf-stars">★★★★★</span>
                                <span>{!! $r !!} &amp; up</span>
                            </label>
                        @endforeach                        <label class="bf-check">
                            <input type="radio" name="rating" value="" {!! !$fRate ? 'checked' : '' !!}>
                            <span>Any rating</span>
                        </label>
                    </div>
                </details>

                <details class="bf-group" open>
                    <summary>{!! ic('chevron', 'ic-bf') !!} Offers</summary>
                    <div class="bf-body">
                        <label class="bf-check">
                            <input type="checkbox" name="sale" value="1" {!! $fSale ? 'checked' : '' !!}>
                            <span>On sale only</span>
                            <small>deals</small>
                        </label>
                    </div>
                </details>

                <button class="btn-green full bf-apply" type="submit">Apply filters</button>
            </form>
        </aside>
        <div class="cat-side-backdrop" id="cat-side-backdrop" hidden></div>

        <!-- ==================== product grid ==================== -->
        <section class="brand-main">
            <div class="listing-head">
                <div>
                    <h2>{{ $brandName }} products</h2>
                    <p id="brand-count">{!! count($list) !!} of {!! count($all) !!} products<?php ?>@if ($activeFilters) · {!! (int)$activeFilters !!} filter{!! $activeFilters === 1 ? '' : 's' !!} applied<?php ?>@endif </p>
                </div>
                <div class="lh-tools">
                    <button type="button" class="btn-outline filters-toggle" id="filters-toggle" aria-controls="brand-side" aria-expanded="false">
                        Filters<?php ?>@if ($activeFilters) <i class="filters-count">{!! (int)$activeFilters !!}</i>@endif                    </button>
                    <label class="sort-wrap">
                        <span>Sort by</span>
                        <select id="sort-products">
                            <option value="featured" {!! $fSort === 'featured' ? 'selected' : '' !!}>Featured</option>
                            <option value="low" {!! $fSort === 'low' ? 'selected' : '' !!}>Price: low to high</option>
                            <option value="high" {!! $fSort === 'high' ? 'selected' : '' !!}>Price: high to low</option>
                            <option value="rate" {!! $fSort === 'rate' ? 'selected' : '' !!}>Top rated</option>
                            <option value="disc" {!! $fSort === 'disc' ? 'selected' : '' !!}>Biggest discount</option>
                            <option value="name" {!! $fSort === 'name' ? 'selected' : '' !!}>Name A–Z</option>
                        </select>
                    </label>
                </div>
            </div>

            @if ($activeFilters)                <div class="applied-filters" id="applied-filters">
                    @foreach ($fCats as $c)                        <button type="button" class="pill on" data-drop="cat" data-val="{{ $c }}">{{ $CAT_LABELS[$c] ?? ucfirst($c) }} ✕</button>
                    @endforeach                    @if ($fMin || $fMax)                        <span class="pill on">৳{!! number_format($fMin) !!}–{!! number_format($fMax ?: (int)$facets['maxPrice']) !!}</span>
                    @endif                    @if ($fRate)<span class="pill on">{{ $fRate }}★ &amp; up</span>@endif                    @if ($fSale)<span class="pill on">On sale</span>@endif                    <a class="pill bf-clear" href="{{ $brandUrl }}">Clear all</a>
                </div>
            @endif
            <div class="product-grid" id="listing-grid">
                @php
foreach ($list as $p) echo pcard($p);
@endphp            </div>

            @if (!$list)                <div class="empty">
                    <h2>No products match these filters</h2>
                    <p>Try widening the price range or clearing a filter or two.</p>
                    <a class="btn-green" href="{{ $brandUrl }}">Clear filters</a>
                </div>
            @endif        </section>
    </div>

    <section class="sec">
        <div class="sec-head"><div class="sec-t"><h2>More brands to explore</h2></div></div>
        <div class="brand-cloud">
            @foreach ($idx as $s => $b)                @php
if ($s === $brandSlug) continue;
@endphp                <a class="pill" href="brand.php?id={!! rawurlencode($s) !!}">{{ $b['name'] }} <small>{!! (int)$b['count'] !!}</small></a>
            @endforeach        </div>
    </section>
</main>

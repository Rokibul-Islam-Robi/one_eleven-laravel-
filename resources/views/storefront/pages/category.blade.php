@php
/* Category / sub-category listing page.
   Entry points: category.php?cat={slug} (also ?sub={slug}) and the clean
   /category/{slug} route. iHerb-style layout: breadcrumb + heading,
   left accordion filter sidebar, sortable product grid with discount
   badges, ratings, prices and add-to-cart. */

$catParam = trim((string)(request()->query('cat') ?? ''));
if ($catParam === '') {
    $catParam = explode('/', $path)[2] ?? '';      /* clean route fallback */
}
$subParam = trim((string)(request()->query('sub') ?? ''));
if ($catParam === '' && $subParam !== '') $catParam = $subParam;
if ($catParam === 'category.php') $catParam = '';

/* ------------------------- resolve the slug ------------------------- */
$catNode  = null;   /* $NAV_CATEGORIES entry for the parent category    */
$catSlug  = '';     /* page identity slug (category or sub-category)    */
$catLabel = '';
$isSub    = false;

foreach ($NAV_CATEGORIES as $node) {
    if ($node['slug'] === $catParam) {
        $catNode = $node;
        $catSlug = $node['slug'];
        $catLabel = $node['label'];
        break;
    }
}
if ($catNode === null && isset($SUB_PARENT[$catParam])) {
    $parentSlug = $SUB_PARENT[$catParam];
    foreach ($NAV_CATEGORIES as $node) {
        if ($node['slug'] === $parentSlug) { $catNode = $node; break; }
    }
    if ($catNode !== null) {
        $isSub = true;
        $catSlug = $catParam;
        $catLabel = $SUB_LABELS[$catParam] ?? ucwords(str_replace('-', ' ', $catParam));
    }
}

/* Legacy links: /category/{slug}?sub={sub} or ?sub={sub} on its own. */
if (!$isSub && $subParam !== '' && isset($SUB_PARENT[$subParam])) {
    $parentSlug = $SUB_PARENT[$subParam];
    foreach ($NAV_CATEGORIES as $node) {
        if ($node['slug'] === $parentSlug) { $catNode = $node; break; }
    }
    if ($catNode !== null) {
        $isSub = true;
        $catSlug = $subParam;
        $catLabel = $SUB_LABELS[$subParam] ?? ucwords(str_replace('-', ' ', $subParam));
    }
}

if ($catNode === null) {
    \App\Support\PageMeta::$status = 404;
    \App\Support\PageMeta::$title = 'Category not found — bHerb';
    echo '<main class="wrap page"><div class="empty"><h1>Category not found</h1>'
        . '<p>We could not find that category. Try one of the departments below.</p>'
        . '<a class="btn-green" href="category.php?cat=supplements">Shop Supplements</a></div></main>';
    return;
}

$parentSlug  = (string)$catNode['slug'];
$parentLabel = (string)$catNode['label'];

/* --------------------------- base catalog --------------------------- */
$base = productsByCategory($parentSlug);
$subFallback = false;
if ($isSub) {
    $base = array_values(array_filter($base, fn($p) => (string)($p['sub'] ?? '') === $catSlug));
    if (!$base) {                       /* nothing tagged yet: show the parent range */
        $base = productsByCategory($parentSlug);
        $subFallback = true;
    }
}

/* ------------------------- filters (GET) ---------------------------- */
/** Multi-value filters arrive as arrays (checkbox groups) or CSV strings. */
$multi = static function ($val): array {
    if (is_array($val)) return array_values(array_filter($val, 'is_string'));
    return array_values(array_filter(explode(',', (string)$val)));
};
$fSubs   = $multi(request()->query('fsub') ?? '');
$fBrands = $multi(request()->query('brand') ?? '');
$fMin    = (int)(request()->query('min') ?? 0);
$fMax    = (int)(request()->query('max') ?? 0);
$fRate   = (float)(request()->query('rating') ?? 0);
$fSale   = request()->query->has('sale');
$fSort   = (string)(request()->query('sort') ?? 'featured');

/* Facet sources computed from the unfiltered set. */
$facets = ['subs' => [], 'brands' => [], 'maxPrice' => 0];

/* Sub-category chips: the nav's sub-menu keeps siblings reachable from a
   sub page; category pages derive theirs from tagged products instead. */
if ($isSub) {
    foreach ($catNode['subs'] as [$label, $slug]) {
        $facets['subs'][$slug] = ['slug' => $slug, 'label' => $label, 'count' => 0];
    }
}

foreach ($base as $p) {
    $s = (string)($p['sub'] ?? '');
    if ($s !== '' && isset($SUB_LABELS[$s]) && ($SUB_PARENT[$s] ?? '') === $parentSlug) {
        if (!isset($facets['subs'][$s])) $facets['subs'][$s] = ['slug' => $s, 'label' => $SUB_LABELS[$s], 'count' => 0];
        $facets['subs'][$s]['count']++;
    }
    $b = trim((string)($p['b'] ?? ''));
    if ($b !== '') {
        if (!isset($facets['brands'][$b])) $facets['brands'][$b] = ['slug' => brand_slug($b), 'label' => $b, 'count' => 0];
        $facets['brands'][$b]['count']++;
    }
    $facets['maxPrice'] = max($facets['maxPrice'], (int)priceval($p['p']));
}
if (!$facets['subs'] && !$isSub) {
    /* parent category without tagged products: surface its sub-menu as chips */
    foreach ($catNode['subs'] as [$label, $slug]) {
        $facets['subs'][$slug] = ['slug' => $slug, 'label' => $label, 'count' => 0];
    }
}
uasort($facets['brands'], fn($a, $b) => $b['count'] <=> $a['count']);
$priceSteps = [0, 1000, 2000, 3000, 5000];

/* --------------------------- filtering ------------------------------ */
$list = array_values(array_filter($base, function ($p) use ($fSubs, $fBrands, $fMin, $fMax, $fRate, $fSale) {
    if ($fSubs && !in_array((string)($p['sub'] ?? ''), $fSubs, true)) return false;
    if ($fBrands && !in_array(brand_slug((string)($p['b'] ?? '')), $fBrands, true)) return false;
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

$activeFilters = count($fSubs) + count($fBrands) + ($fMin ? 1 : 0) + ($fMax ? 1 : 0) + ($fRate ? 1 : 0) + ($fSale ? 1 : 0);
$shopAllUrl = 'category.php?cat=' . rawurlencode($parentSlug);
$clearUrl  = 'category.php?cat=' . rawurlencode($catSlug);
$catTitle = $isSub ? $catLabel : $parentLabel;

\App\Support\PageMeta::$title = $catTitle . ' — bHerb';
\App\Support\PageMeta::$desc = 'Shop ' . $catTitle . ' products at bHerb — authentic, quality checked and delivered across Bangladesh.';
@endphp <main class="wrap page cat-page">

    <!-- ==================== heading ==================== -->
    <div class="breadcrumbs">
        <a href="./">Home</a> /
        @if ($isSub)            <a href="{{ $shopAllUrl }}">{{ $parentLabel }}</a> / {{ $catLabel }}
        @else            {{ $parentLabel }}
        @endif    </div>

    <div class="listing-head">
        <div>
            <h1>{{ $catTitle }}</h1>
            <p>{!! count($list) !!} product{!! count($list) === 1 ? '' : 's' !!}@if ($subFallback && $isSub) · showing the full {{ $parentLabel }} range<?php ?>@endif
@if ($activeFilters) · {!! (int)$activeFilters !!} filter{!! $activeFilters === 1 ? '' : 's' !!} applied<?php ?>@endif </p>
        </div>
        <div class="lh-tools">
            <button type="button" class="btn-outline filters-toggle" id="filters-toggle" aria-controls="cat-filters" aria-expanded="false">
                Filters<?php ?>@if ($activeFilters) <i class="filters-count">{!! (int)$activeFilters !!}</i>@endif            </button>
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

    <!-- ==================== sub-category chips ==================== -->
    <div class="plp-pills">
        @if ($isSub)            <a class="pill" href="{{ $shopAllUrl }}">All {{ $parentLabel }}</a>
        @endif        @foreach ($facets['subs'] as $s)            @php
$on = in_array($s['slug'], $fSubs, true) || ($isSub && $s['slug'] === $catSlug && !$fSubs);
@endphp            <a class="pill{!! $on ? ' on' : '' !!}"
               href="category.php?cat={!! rawurlencode($s['slug']) !!}">{{ $s['label'] }}</a>
        @endforeach    </div>

    <div class="cat-layout">
        <!-- ==================== filter sidebar ==================== -->
        <aside class="cat-side filter-side" id="cat-filters">
            <form method="get" action="category.php" id="cat-filter-form">
                <input type="hidden" name="cat" value="{{ $catSlug }}">
                <input type="hidden" name="sort" value="{{ $fSort }}">

                <div class="bf-head">
                    <b>Filter</b>
                    @if ($activeFilters)                        <a class="bf-clear" href="{{ $clearUrl }}">Clear all</a>
                    @endif                </div>

                @if (!$isSub && $facets['subs'])                <details class="bf-group" open>
                    <summary>{!! ic('chevron', 'ic-bf') !!} Subcategories</summary>
                    <div class="bf-body">
                        @foreach ($facets['subs'] as $s)                            <label class="bf-check">
                                <input type="checkbox" name="fsub[]" value="{{ $s['slug'] }}"
                                    {!! in_array($s['slug'], $fSubs, true) ? 'checked' : '' !!}>
                                <span>{{ $s['label'] }}</span>
                                <small>{!! (int)$s['count'] !!}</small>
                            </label>
                        @endforeach                    </div>
                </details>
                @endif
                <details class="bf-group" open>
                    <summary>{!! ic('chevron', 'ic-bf') !!} Brand</summary>
                    <div class="bf-body">
                        @foreach (array_slice($facets['brands'], 0, 12, true) as $f)                            <label class="bf-check">
                                <input type="checkbox" name="brand[]" value="{{ $f['slug'] }}"
                                    {!! in_array($f['slug'], $fBrands, true) ? 'checked' : '' !!}>
                                <span>{{ $f['label'] }}</span>
                                <small>{!! (int)$f['count'] !!}</small>
                            </label>
                        @endforeach                        @if (count($facets['brands']) > 12)                            <span class="bf-note">{!! count($facets['brands']) - 12 !!} more brands — use search to narrow down</span>
                        @endif                    </div>
                </details>

                <details class="bf-group" open>
                    <summary>{!! ic('chevron', 'ic-bf') !!} Price</summary>
                    <div class="bf-body">
                        <div class="bf-price">
                            <label>Min <input type="number" name="min" min="0" value="{!! $fMin ?: '' !!}" placeholder="0"></label>
                            <i>–</i>
                            <label>Max <input type="number" name="max" min="0" value="{!! $fMax ?: '' !!}" placeholder="{!! (int)$facets['maxPrice'] !!}"></label>
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
        <section class="cat-main">
            @if ($activeFilters)                <div class="applied-filters" id="applied-filters">
                    @foreach ($fSubs as $s)
@php
if (!isset($SUB_LABELS[$s])) continue;
@endphp                        <button type="button" class="pill on" data-drop="fsub" data-val="{{ $s }}">{{ $SUB_LABELS[$s] }} ✕</button>
                    @endforeach                    @foreach ($fBrands as $bs)                        <button type="button" class="pill on" data-drop="brand" data-val="{{ $bs }}">{{ ucwords(str_replace('-', ' ', $bs)) }} ✕</button>
                    @endforeach                    @if ($fMin || $fMax)                        <span class="pill on">৳{!! number_format($fMin) !!}–{!! number_format($fMax ?: (int)$facets['maxPrice']) !!}</span>
                    @endif                    @if ($fRate)<span class="pill on">{{ $fRate }}★ &amp; up</span>@endif                    @if ($fSale)<span class="pill on">On sale</span>@endif                    <a class="pill bf-clear" href="{{ $clearUrl }}">Clear all</a>
                </div>
            @endif
            <div class="product-grid" id="listing-grid">
                @php
foreach ($list as $p) echo pcard($p);
@endphp            </div>

            @if (!$list)                <div class="empty">
                    <h2>No products match these filters</h2>
                    <p>Try widening the price range or clearing a filter or two.</p>
                    <a class="btn-green" href="{{ $clearUrl }}">Clear filters</a>
                </div>
            @endif        </section>
    </div>
</main>

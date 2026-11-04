@php
/* Category / collection listing pages:
   /category/{slug}?sub={sub-slug}, /deals, /bestsellers, /new          */

$category = explode('/', $path)[2] ?? '';
$sub = trim((string)(request()->query('sub') ?? ''));
$subLabel = ($sub !== '' && isset($SUB_LABELS[$sub])) ? $SUB_LABELS[$sub] : '';

$title = $subLabel !== '' ? $subLabel : ucwords(str_replace('-', ' ', $category ?: trim($path, '/')));

$list = array_values(array_filter($products, function ($p) use ($path, $category, $sub) {
    if ($path === '/deals')     return ($p['row'] ?? '') === 'deals';
    if ($path === '/bestsellers') return in_array(($p['row'] ?? ''), ['trending', 'deals'], true);
    if ($path === '/new')       return ($p['row'] ?? '') === 'new';
    if (($p['c'] ?? '') !== $category) return false;
    if ($sub !== '') return ($p['sub'] ?? '') === $sub;
    return true;
}));

$subFallback = false;
if ($sub !== '' && !$list) {
    // No products tagged with this sub-category yet: show the whole category.
    $list = productsByCategory($category);
    $subFallback = true;
}

\App\Support\PageMeta::$title = $title . ' — bHerb';
@endphp <main class="wrap page">
    <div class="breadcrumbs">
        <a href="./">Home</a> /
        @if ($subLabel !== '')            <a href="category.php?cat={{ $category }}">{{ ucwords(str_replace('-', ' ', $category)) }}</a> / {{ $subLabel }}
        @else            {{ $title }}
        @endif    </div>

    <div class="listing-head">
        <div>
            <h1>{{ $title }}</h1>
            <p>{!! count($list) !!} products to explore{!! $subFallback && $subLabel !== '' ? ' · showing the full ' . e(ucwords(str_replace('-', ' ', $category))) . ' range' : '' !!}</p>
            @if ($subLabel !== '')                <div class="plp-pills">
                    <a class="pill" href="category.php?cat={{ $category }}">All {{ ucwords(str_replace('-', ' ', $category)) }}</a>
                    <span class="pill on">{{ $subLabel }}</span>
                </div>
            @endif        </div>
        <select id="sort-products">
            <option value="default">Featured</option>
            <option value="low">Price: low to high</option>
            <option value="high">Price: high to low</option>
            <option value="name">Name</option>
        </select>
    </div>

    <div class="product-grid" id="listing-grid">
        @php
foreach ($list as $p) echo pcard($p);
@endphp    </div>
    @if (!$list)        <p class="empty">No products found in this category yet.</p>
    @endif </main>

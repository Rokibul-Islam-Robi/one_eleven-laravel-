@php
/* Search results: /search?q=… */
$list = array_values(array_filter($products, function ($p) use ($search) {
    return !$search || stripos($p['n'] . ' ' . $p['b'] . ' ' . $p['s'] . ' ' . $p['c'], $search) !== false;
}));
\App\Support\PageMeta::$title = ($search !== '' ? 'Search: ' . $search : 'Search') . ' — bHerb';
@endphp <main class="wrap page">
    <div class="listing-head">
        <div>
            <h1>Search results</h1>
            <p>{!! count($list) !!} results for “{{ $search }}”</p>
        </div>
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
    @if (!$list)        <p class="empty">No products match your search. Try another keyword.</p>
    @endif </main>

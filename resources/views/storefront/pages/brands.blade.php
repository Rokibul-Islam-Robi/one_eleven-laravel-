@php
/* Brand directory: /brands — A–Z grid of every brand in the catalog. */
\App\Support\PageMeta::$title = 'All Brands A–Z — bHerb';
\App\Support\PageMeta::$desc = 'Browse every brand stocked at bHerb.';

$idx = brand_index();
uasort($idx, fn($a, $b) => strcmp($a['name'], $b['name']));

$letters = [];
foreach ($idx as $slug => $b) {
    $letter = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $b['name']), 0, 1) ?: '#');
    $letters[$letter][$slug] = $b;
}
ksort($letters);
@endphp <main class="wrap page">
    <div class="breadcrumbs"><a href="./">Home</a> / Brands</div>
    <div class="listing-head">
        <div>
            <h1>Brands A–Z</h1>
            <p>{!! count($idx) !!} authentic brands · every one quality checked</p>
        </div>
    </div>

    @foreach ($letters as $letter => $brands)        <section class="brand-letter">
            <h2>{{ $letter }}</h2>
            <div class="brand-cloud">
                @foreach ($brands as $slug => $b)                    <a class="pill" href="brand.php?id={!! rawurlencode($slug) !!}">{{ $b['name'] }} <small>{!! (int)$b['count'] !!}</small></a>
                @endforeach            </div>
        </section>
    @endforeach </main>

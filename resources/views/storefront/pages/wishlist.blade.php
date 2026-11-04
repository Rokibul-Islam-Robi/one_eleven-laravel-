@php
/* Wishlist: /wishlist */
\App\Support\PageMeta::$title = 'Your Wishlist — bHerb';
@endphp <main class="wrap page wishlist-page">
    <h1>Your Wishlist <span class="wish-count" id="wish-page-count">{!! $wishProducts ? '(' . count($wishProducts) . ')' : '' !!}</span></h1>
    @if (!$wishProducts)        <div class="empty" id="wish-empty">
            <p>Your wishlist is empty. Save products you love using the ♡ button.</p>
            <a class="btn-green" href="./">Explore products</a>
        </div>
    @else        <div class="product-grid" id="wish-grid">
            @php
foreach ($wishProducts as $p) echo pcard($p);
@endphp        </div>
        <div class="empty" id="wish-empty" hidden>
            <p>Your wishlist is empty. Save products you love using the ♡ button.</p>
            <a class="btn-green" href="./">Explore products</a>
        </div>
    @endif </main>

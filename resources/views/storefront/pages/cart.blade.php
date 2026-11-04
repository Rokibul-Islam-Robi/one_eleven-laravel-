@php
/* Full cart page: /cart */
\App\Support\PageMeta::$title = 'Your Cart — bHerb';
@endphp
<main class="wrap page">
    <h1>Your Cart</h1>
    <div id="cart-content">@include('partials.cart-page-content')</div>
</main>

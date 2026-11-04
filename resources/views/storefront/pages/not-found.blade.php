@php
/* Fallbacks: admin notice + 404 */
\App\Support\PageMeta::$title = ($isAdmin ? 'bHerb Admin' : 'Page not found') . ' — bHerb';
@endphp <main class="wrap page">
    @if ($isAdmin)        <h1>bHerb Admin</h1>
        <p>The original project includes an admin dashboard. This conversion package provides the storefront and local cart/checkout flows; admin CRUD and role-based authentication need a configured database before production use.</p>
        <a class="btn-green" href="./">Return to storefront</a>
    @else        <div class="empty">
            <h1>Page not found</h1>
            <p>The page you requested does not exist or has moved.</p>
            <a class="btn-green" href="./">Return to home</a>
        </div>
    @endif </main>

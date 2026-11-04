<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Seller portal' }} — bHerb</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <style>
        body{margin:0;background:#f6f7f3;color:#20251f;font:16px/1.5 system-ui,sans-serif}
        header{background:#fff;border-bottom:1px solid #e1e6df;padding:14px max(20px,calc((100% - 1100px)/2));display:flex;justify-content:space-between;align-items:center}
        header a,.seller-nav a{color:#245d38;text-decoration:none;font-weight:600}
        main{max-width:1100px;margin:32px auto;padding:0 20px}
        .seller-nav{display:flex;gap:18px;align-items:center}
        .seller-card{background:#fff;border:1px solid #e1e6df;border-radius:10px;padding:24px;margin:18px 0}
        .seller-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px}
        label{display:block;font-weight:600;margin:12px 0 5px}
        input,select,textarea{box-sizing:border-box;width:100%;padding:10px;border:1px solid #cbd4ca;border-radius:6px;font:inherit}
        button,.seller-button{display:inline-block;background:#245d38;color:#fff;border:0;border-radius:6px;padding:10px 16px;font:inherit;font-weight:600;text-decoration:none;cursor:pointer;margin-top:12px}
        .seller-status{padding:12px;background:#edf5ed;border-radius:6px}
        .seller-error{padding:12px;background:#fff0ee;color:#8e2922;border-radius:6px}
        small{color:#626b62}
        table{width:100%;border-collapse:collapse}
        th,td{text-align:left;padding:10px;border-bottom:1px solid #e5e9e3}
        @media(max-width:650px){header{align-items:flex-start;gap:12px;flex-direction:column}.seller-nav{flex-wrap:wrap}}
    </style>
</head>
<body>
<header>
    <a href="/seller/dashboard">bHerb Seller Center</a>
    @if (auth('seller')->check())
        <nav class="seller-nav" aria-label="Seller navigation">
            <a href="/seller/dashboard">Overview</a>
            <a href="/seller/profile">Profile</a>
            @if (auth('seller')->user()?->status === 'active' && auth('seller')->user()?->application_status === 'approved' && auth('seller')->user()?->is_active && auth('seller')->user()?->hasVerifiedEmail())
                <a href="/seller/products">My products</a>
                <a href="/seller/inventory">Inventory</a>
                <a href="/seller/orders">My orders</a>
            @endif
            <form method="post" action="/seller/logout">@csrf<button type="submit">Sign out</button></form>
        </nav>
    @else
        <nav class="seller-nav"><a href="/seller/login">Sign in</a><a href="/seller/register">Apply to sell</a></nav>
    @endif
</header>
<main>
    @if (session('status'))<div class="seller-status" role="status">{{ session('status') }}</div>@endif
    @if ($errors->any())
        <div class="seller-error" role="alert">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif
    @yield('content')
</main>
</body>
</html>

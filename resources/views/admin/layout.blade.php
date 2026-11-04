<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title }} · {{ config('admin.name') }} Admin</title>
<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}?v=2.0">
<link rel="stylesheet" href="{{ asset('assets/admin/css/admin-panel.css') }}?v=2.0">
</head>
<body>
@php
    $u = current_user();
    $badges = nav_badges();
@endphp
<div class="adm">
<div class="adm-shell">

  <aside class="adm-side" id="adm-side">
    <div class="adm-side-head">
      <span class="logo-mark">R</span>
      <span class="logo-txt">{{ config('admin.name') }}<small>{{ config('admin.sub') }}</small></span>
    </div>
    <nav class="adm-nav">
      @foreach (admin_nav() as [$key, $label, $path, $perm])
        @continue($label === $key)
        @if ($path === null)
          <div class="adm-nav-label">{{ $label }}</div>
        @elseif ($perm && can($perm))
          <a href="{{ aurl($path) }}" class="{{ $key === $activeKey ? 'on' : '' }}">
            {!! icon($key === 'doctor_coupons' ? 'coupon' : ($key === 'offers' ? 'offer' : ($key === 'sellers' ? 'store' : $key))) !!}
            <span>{{ $label }}</span>
            @if (!empty($badges[$key]))<span class="adm-nav-count">{{ (int) $badges[$key] }}</span>@endif
          </a>
        @endif
      @endforeach
    </nav>
    <div class="adm-side-foot">
      <a href="{{ site_url('') }}" target="_blank" rel="noopener">{!! icon('store') !!}<span>View storefront</span></a>
    </div>
  </aside>

  <div class="adm-main">
    <header class="adm-top">
      <button class="adm-burger" id="adm-burger" aria-label="Open menu">☰</button>
      <form class="adm-top-search" action="{{ aurl('search') }}" method="get" role="search">
        {!! icon('search') !!}
        <input name="q" value="{{ getq('q') }}" placeholder="Search products, orders, customers, doctors…" aria-label="Search">
      </form>
      <div class="adm-top-right">
        <span class="adm-role-sel"><span>Role</span>
          <select id="role-display" aria-label="Your role">
            <option selected>{{ ucfirst((string) ($u['role'] ?? '')) }}</option>
          </select>
        </span>
        <div class="adm-dd">
          <button class="adm-avatar" id="adm-user-btn" type="button" aria-haspopup="true">{{ strtoupper(mb_substr((string) ($u['name'] ?? '?'), 0, 1)) }}</button>
          <div class="adm-dd-menu" id="adm-user-menu" hidden>
            <div style="padding:8px 12px"><b>{{ $u['name'] ?? '' }}</b><br><small style="color:var(--a-muted)">{{ $u['email'] ?? '' }}</small></div>
            <div class="sep"></div>
            <form method="post" action="{{ aurl('logout') }}">
              @csrf
              <button type="submit" style="width:100%;text-align:left">{!! icon('logout') !!} Sign out</button>
            </form>
          </div>
        </div>
      </div>
    </header>

    <main class="adm-content{{ $wide ? ' adm-content-wide' : '' }}">
      @foreach (get_flashes() as $f)
        @php $cls = $f['type'] === 'error' ? 'danger' : ($f['type'] === 'warning' ? 'warning' : ($f['type'] === 'success' ? 'success' : 'info')); @endphp
        <div class="alert alert-{{ $cls }} alert-dismissible fade show adm-alert" role="alert">
          {{ $f['message'] }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      @endforeach
      {!! $content !!}
    </main>

    <footer class="adm-foot no-print">© {{ date('Y') }} {{ config('admin.name') }} — Admin CMS · Laravel + MySQL · v1.0</footer>
  </div>
</div>
</div>
<div class="adm-side-back" id="adm-side-back" hidden></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('assets/admin/js/admin.js') }}?v=2.0"></script>
</body>
</html>

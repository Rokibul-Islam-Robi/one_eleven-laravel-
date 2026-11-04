<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Sign in · {{ config('admin.name') }} Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}?v=2.0">
<link rel="stylesheet" href="{{ asset('assets/admin/css/admin-panel.css') }}?v=2.0">
</head>
<body>
<div class="adm">
  <div class="adm-login">
    <div class="adm-login-card">
      <div class="lg-logo">
        <span class="logo-mark">R</span>
        <div>
          <h1>{{ config('admin.name') }}</h1>
          <div style="font-size:12px;color:#5b6b7c;font-weight:600">{{ config('admin.sub') }}</div>
        </div>
      </div>
      <p class="lg-sub">Sign in to manage products, doctors, orders and the storefront.</p>

      @foreach (get_flashes() as $f)
        <div class="alert alert-{{ $f['type'] === 'error' ? 'danger' : ($f['type'] === 'warning' ? 'warning' : 'info') }}">{{ $f['message'] }}</div>
      @endforeach
      @if ($error)
        <div class="alert alert-danger">{{ $error }}</div>
      @endif

      <form method="post" action="{{ aurl('login') }}" autocomplete="off">
        @csrf
        <div class="afield">
          <label for="email">Email address</label>
          <input class="ainput" type="email" id="email" name="email" value="{{ getq('email') }}" required autofocus placeholder="you@example.com">
        </div>
        <div class="afield">
          <label for="password">Password</label>
          <input class="ainput" type="password" id="password" name="password" required placeholder="••••••••">
        </div>
        <button class="abtn primary w-100" style="height:44px">Sign in securely</button>
      </form>

      <div class="adm-demo-accounts">
        <div class="da-lbl">Demo accounts (created by database/seed.php)</div>
        <button type="button" data-fill="admin@rbspharma.com|Admin@12345"><span>Super Admin</span><span class="de">admin@rbspharma.com</span></button>
        <button type="button" data-fill="manager@rbspharma.com|Manager@12345"><span>Manager</span><span class="de">manager@rbspharma.com</span></button>
        <button type="button" data-fill="staff@rbspharma.com|Staff@12345"><span>Staff</span><span class="de">staff@rbspharma.com</span></button>
      </div>
      <p style="margin-top:16px;font-size:11.5px;color:#8a9aab">Protected by CSRF tokens, password hashing, login throttling and session timeouts. <a href="{{ site_url('') }}" style="color:#16813b">← Back to storefront</a></p>
    </div>
  </div>
</div>
<script>
document.querySelectorAll('.adm-demo-accounts button').forEach(function (b) {
  b.addEventListener('click', function () {
    var p = b.getAttribute('data-fill').split('|');
    document.getElementById('email').value = p[0];
    document.getElementById('password').value = p[1];
  });
});
</script>
</body>
</html>

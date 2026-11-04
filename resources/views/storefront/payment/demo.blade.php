<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
<title>SANDBOX DEMO · {{ $label }} simulator — {{ $tranId }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: system-ui, -apple-system, sans-serif; background: #f1f3f7; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
    .card { background: #fff; border-radius: 14px; box-shadow: 0 12px 40px rgba(15,30,60,.14); width: 100%; max-width: 420px; overflow: hidden; }
    .head { background: linear-gradient(135deg,#0d2b6b,#16429b); color: #fff; padding: 18px 22px; }
    .head b { font-size: 17px; display: block; }
    .head small { opacity: .8; font-size: 12px; }
    .body { padding: 22px; }
    .row { display: flex; justify-content: space-between; padding: 9px 0; border-bottom: 1px dashed #e3e7ef; font-size: 14px; }
    .row span { color: #667; }
    .row b { color: #122; }
    .amt { font-size: 22px; color: #0d2b6b; margin: 14px 0 4px; }
    .tag { display: inline-block; background: #fff4d6; color: #8a6100; border-radius: 99px; font-size: 11px; padding: 3px 10px; margin-bottom: 12px; }
    .acts { display: grid; gap: 10px; margin-top: 18px; }
    .btn { display: block; text-align: center; padding: 12px; border-radius: 9px; border: 0; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; }
    .ok { background: #1c7c54; color: #fff; }
    .no { background: #c0392b; color: #fff; }
    .cx { background: #eef1f6; color: #345; }
    .fine { font-size: 11px; color: #889; margin-top: 14px; text-align: center; }
</style>
</head>
<body>
<div class="card">
    <div class="head">
        <b>{{ $label }} · Gateway</b>
        <small>SANDBOX / DEMO simulator — not a real payment page</small>
    </div>
    <div class="body">
        <span class="tag">DEMO / SANDBOX — no real money is charged</span>
        <div class="row"><span>Merchant</span><b>bHerb</b></div>
        <div class="row"><span>Order ID</span><b>{{ $tranId }}</b></div>
        <div class="row"><span>Payment method</span><b>{{ $label }} · simulated wallet</b></div>
        <div class="amt">৳{{ $amount }}</div>
        <div class="acts">
            <a class="btn ok" href="{{ $success }}">✓ Simulate successful demo payment</a>
            <a class="btn no" href="{{ $fail }}">✕ Simulate failure</a>
            <a class="btn cx" href="{{ $cancel }}">← Cancel and return</a>
        </div>
        <p class="fine">This page simulates the {{ $label }} sandbox so the full checkout flow can be tested without credentials.</p>
    </div>
</div>
</body>
</html>

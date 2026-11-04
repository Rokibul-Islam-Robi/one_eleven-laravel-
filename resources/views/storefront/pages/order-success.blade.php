@php
/* Order confirmation: /order-success — invoice slip, payment + Rx status. */
\App\Support\PageMeta::$title = 'Order confirmed — bHerb';

/* Load the order: session first, then ?order={order_number} (survives lost session). */
$sessionOrder = session('last_order');
$order = null;
$items = [];
$rx = null;
$orderQuery = fn () => \Illuminate\Support\Facades\DB::table('orders as o')
    ->leftJoin('courier_methods as cm', 'cm.id', '=', 'o.courier_id')
    ->select('o.*', 'cm.name as courier_name', 'cm.estimated_days as courier_eta');

if ($sessionOrder && !empty($sessionOrder['db_id'])) {
    $row = $orderQuery()->where('o.id', (int)$sessionOrder['db_id'])->first();
    $order = $row ? (array)$row : null;
}
if (!$order) {
    $byNumber = (string)(request()->query('order') ?? '');
    if ($byNumber !== '') {
        $row = $orderQuery()->where('o.order_number', $byNumber)->first();
        $order = $row ? (array)$row : null;
    }
}
if ($order) {
    $items = \Illuminate\Support\Facades\DB::table('order_items')->where('order_id', (int)$order['id'])->get()->map(fn ($r) => (array)$r)->all();
    $rxId = (int)($order['prescription_id'] ?? 0);
    if ($rxId > 0) {
        $r = \Illuminate\Support\Facades\DB::table('prescriptions')->where('id', $rxId)->first();
        $rx = $r ? (array)$r : null;
    }
}

$returnMsg = (string)(session('return_msg', ''));
$returnOk   = (int)(session('return_ok', 0));
session()->forget(['return_msg', 'return_ok']);

if (!$order) {
    echo '<main class="wrap page order-ok"><div class="ok-hero">'
        . '<h1>No order found</h1>'
        . '<p class="ok-sub">Your confirmation will appear here after checkout, or check your email.</p>'
        . '<div class="ok-actions"><a class="ok-btn primary" href="./">Continue shopping</a></div>'
        . '</div></main>';
    return;
}

/* status helpers */
$statusClass = match ((string)$order['payment_status']) {
    'paid'    => 'paid',
    'failed'  => 'failed',
    default   => 'pending',
};
$isDemoPaid = (string)$order['payment_status'] === 'paid' && (int)($order['demo_payment'] ?? 0) === 1;
$statusLabel = match ((string)$order['payment_status']) {
    'paid'    => $isDemoPaid ? 'DEMO PAYMENT — sandbox only, no real money charged' : 'PAID',
    'failed'  => 'FAILED',
    'pending' => 'PENDING — awaiting payment confirmation',
    default   => 'UNPAID — ' . (str_contains((string)$order['payment_method'], 'cash') ? 'Pay on delivery' : 'Awaiting payment'),
};
$rxChipClass = match ((string)($rx['status'] ?? '')) {
    'approved' => 'paid',
    'rejected' => 'failed',
    default    => 'pending',
};
$rxStatusLabel = match ((string)($rx['status'] ?? '')) {
    'approved' => 'APPROVED',
    'rejected' => 'REJECTED',
    'reviewing'=> 'UNDER REVIEW',
    default    => 'PENDING REVIEW',
};

/* simple status timeline */
$steps = ['placed', 'paid', 'processing', 'shipped', 'delivered'];
$currentStep = match ((string)$order['status']) {
    'awaiting_prescription', 'pending' => 'placed',
    'processing' => 'processing',
    'shipped', 'out_for_delivery' => 'shipped',
    'delivered' => 'delivered',
    default => 'placed',
};
$stepIdx = array_search($currentStep, $steps, true);
if ($stepIdx === false) $stepIdx = 0;
@endphp <main class="wrap page order-ok">
    <div class="ok-hero">
        <div class="ok-check">✓</div>
        <h1>Thank you for your order!</h1>
        <p class="ok-id">Order reference: <b>{{ $order['order_number'] }}</b></p>
        <p class="ok-sub">
            {{ ucfirst(str_replace('_', ' ', (string)$order['status'])) }}
            @if ($order['courier_name'])                <span class="ok-dot">·</span> Delivery by <b>{{ $order['courier_name'] }}</b>
                ({{ $order['courier_eta'] ?? '' }})
            @endif        </p>
        <div class="ok-actions">
            <a class="ok-btn" href="./">Continue shopping</a>
            <button class="ok-btn" type="button" onclick="window.print()">Print invoice</button>
        </div>
    </div>
    @if ((string)$order['payment_status'] === 'paid' && in_array((string)$order['status'], ['cancelled', 'returned'], true))
        <div class="inv-ship" role="alert">
            Payment was received after this order was closed. The order has not been reopened. Please contact bHerb support for administrator refund review.
        </div>
    @endif

    <div class="invoice-slip">
        <!-- head -->
        <div class="inv-head">
            <div>
                <div class="inv-logo">bHerb<sup>®</sup></div>
                <div class="inv-tag">Wellness, delivered.</div>
            </div>
            <div class="inv-company">
                <b>Order</b> {{ $order['order_number'] }}<br>
                <b>Date</b> {{ date('d M Y, h:i A', strtotime((string)$order['created_at'])) }}<br>
                <b>Payment</b> {{ str_replace('_', ' ', (string)$order['payment_method']) }}
            </div>
        </div>
        <div class="inv-rule"></div>

        <!-- meta -->
        <div class="inv-meta">
            <div><span class="inv-lbl">Payment status</span>
                <span class="inv-chip {!! $statusClass !!}">{{ $statusLabel }}</span>
            </div>
            @if ((string)$order['payment_method'] !== 'cash_on_delivery')                <div><span class="inv-lbl">Gateway</span><span class="inv-val">{{ \App\Services\Payments\PaymentConfig::label((string)$order['payment_method']) }}</span></div>
            @endif            <div><span class="inv-lbl">Order status</span><span class="inv-val">{{ ucfirst(str_replace('_', ' ', (string)$order['status'])) }}</span></div>
            <div><span class="inv-lbl">Amount</span><span class="inv-val">{!! money((float)$order['total']) !!}</span></div>
        </div>

        <!-- transaction summary (online payments) -->
        @if (in_array((string)$order['payment_status'], ['paid', 'pending'], true))            <div class="inv-ship">
                <span class="inv-lbl">Transaction summary</span>
                <div class="inv-addr" style="margin-top:6px;display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px 18px">
                    <div>
                        <b>Gateway</b><br>
                        {{ \App\Services\Payments\PaymentConfig::label((string)$order['payment_method']) }}
                        {!! (int)($order['demo_payment'] ?? 0) === 1 ? ' · <span style="color:#8a6100">sandbox / demo</span>' : '' !!}
                    </div>
                    @if (!empty($order['val_id']))                        <div>
                            <b>Transaction ID</b><br>
                            <span style="font-size:11px;word-break:break-all">{{ $order['val_id'] }}</span>
                        </div>
                    @endif                    @if (!empty($order['bank_tran_id']))                        <div>
                            <b>Processor reference</b><br>
                            <span style="font-size:11px;word-break:break-all">{{ $order['bank_tran_id'] }}</span>
                        </div>
                    @endif                    @if (!empty($order['card_type']))                        <div>
                            <b>Instrument</b><br>
                            {{ $order['card_type'] }}
                        </div>
                    @endif                    <div>
                        <b>Amount</b><br>
                        {!! money((float)$order['total']) !!} ·
                        {!! (string)$order['payment_status'] === 'paid' ? 'settled' : 'awaiting confirmation' !!}
                    </div>
                </div>
                @if ((string)$order['payment_status'] === 'pending')                    <div class="inv-addr" style="margin-top:8px">
                        We received your payment request and are confirming it with
                        {{ \App\Services\Payments\PaymentConfig::label((string)$order['payment_method']) }}. This page updates as soon as the
                        confirmation arrives — no action is needed.
                    </div>
                @endif            </div>
        @endif
        <!-- prescription verification -->
        <div class="inv-ship">
            <span class="inv-lbl">Prescription verification</span>
            @if ($rx)                <span class="inv-chip {!! $rxChipClass !!}" style="margin-left:8px">{{ $rxStatusLabel }}</span>
                <div class="inv-addr" style="margin-top:4px">
                    File: <b>{{ $rx['original_name'] ?? 'prescription' }}</b>
                    @if ($rx['updated_at']) · Updated {{ date('d M Y, h:i A', strtotime((string)$rx['updated_at'])) }}@endif                    <br>Our pharmacist will confirm by phone if anything else is needed.
                </div>
            @elseif ((int)($order['requires_prescription'] ?? 0) === 1)                <span class="inv-chip pending" style="margin-left:8px">REQUIRED</span>
                <div class="inv-addr" style="margin-top:4px">
                    This order includes prescription items. Please contact us to submit your prescription.
                </div>
            @else                <div class="inv-addr" style="margin-top:4px">No prescription required for this order.</div>
            @endif        </div>

        <!-- shipping -->
        <div class="inv-ship">
            <span class="inv-lbl">Shipping to</span>
            <div class="inv-addr">
                <b>{{ $order['shipping_name'] }}</b> · {{ $order['shipping_phone'] }}<br>
                {!! nl2br(e($order['shipping_address'])) !!}
                @if ($order['shipping_area'])<br><b>District:</b> {{ $order['shipping_area'] }}@php
$divName = bd_division_of((string)$order['shipping_area']);
                    if ($divName !== '') echo ' <span style="color:#6d757c">(' . e($divName) . ' division)</span>';
@endphp @endif                @if (!empty($order['shipping_postal'])) — {{ $order['shipping_postal'] }}@endif            </div>
        </div>

        <!-- items -->
        <table class="inv-table">
            <thead>
                <tr>
                    <th class="inv-t-img"></th>
                    <th>Item</th>
                    <th class="inv-t-num">Qty</th>
                    <th class="inv-t-num">Unit</th>
                    <th class="inv-t-num">Total</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($items as $it)                <tr>
                    <td class="inv-t-img"><img src="{{ $it['image_url'] }}" alt="" loading="lazy"></td>
                    <td>
                        <span class="inv-item-name">{{ $it['product_name'] }}</span>
                        @if (!empty($it['variant_label']))                            <span class="inv-item-sub">{{ $it['variant_label'] }}</span>
                        @endif                    </td>
                    <td class="inv-t-num">{!! (int)$it['qty'] !!}</td>
                    <td class="inv-t-num">{!! money((float)$it['price']) !!}</td>
                    <td class="inv-t-num inv-bold">{!! money((float)$it['price'] * (int)$it['qty']) !!}</td>
                </tr>
            @endforeach            </tbody>
        </table>

        <!-- totals -->
        <div class="inv-totals">
            <dl>
                <div><dt>Subtotal</dt><dd>{!! money((float)$order['subtotal']) !!}</dd></div>
                @if ((float)$order['discount_amount'] > 0)                    <div class="inv-save"><dt>Discount <small>({{ $order['coupon_code'] }})</small></dt><dd>−{!! money((float)$order['discount_amount']) !!}</dd></div>
                @endif                <div><dt>Shipping</dt><dd>{!! (float)$order['shipping'] == 0 ? '<span class="inv-free">FREE</span>' : money((float)$order['shipping']) !!}</dd></div>
                @if ((float)$order['cod_fee'] > 0)                    <div><dt>COD fee</dt><dd>{!! money((float)$order['cod_fee']) !!}</dd></div>
                @endif                <div class="inv-grand"><dt>Total</dt><dd>{!! money((float)$order['total']) !!}</dd></div>
            </dl>
        </div>

        <!-- timeline -->
        <div style="margin-top:22px">
            <span class="inv-lbl">Order progress</span>
            <div style="display:flex;align-items:center;gap:6px;margin-top:8px;flex-wrap:wrap">
                @foreach ($steps as $i => $step)                    @if ($i > 0)                        <span style="flex:0 0 28px;height:2px;background:{!! $i <= $stepIdx ? 'var(--green,#16813b)' : '#e7e9e7' !!};border-radius:2px"></span>
                    @endif                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;color:{!! $i <= $stepIdx ? 'var(--green,#16813b)' : '#98a0a6' !!}">
                        <span style="width:9px;height:9px;border-radius:50%;background:{!! $i <= $stepIdx ? 'var(--green,#16813b)' : '#d7dbd8' !!};display:inline-block"></span>
                        {{ ucfirst($step) }}
                    </span>
                @endforeach            </div>
        </div>

        <!-- return request -->
        @if (in_array((string)$order['status'], ['delivered', 'failed_delivery'], true))            <div style="margin-top:22px;border-top:1px solid #eef0ef;padding-top:16px">
                <details>
                    <summary style="cursor:pointer;font-weight:700;font-size:14px">Request a return</summary>
                    <form method="post" style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap">@csrf
                        <input type="hidden" name="action" value="return_request">
                        <input type="hidden" name="order_id" value="{!! (int)$order['id'] !!}">
                        <input name="reason" required maxlength="255" placeholder="Reason for return"
                               style="flex:1;min-width:200px;border:1px solid #d7dbd8;border-radius:8px;padding:9px 12px;font:inherit;font-size:13px">
                        <button class="ok-btn primary">Submit</button>
                    </form>
                </details>
            </div>
        @endif
        <div class="inv-foot">
            Thank you for choosing bHerb. For any questions about this order, contact our support team.
        </div>
    </div>

    @if ($returnMsg !== '')        <p style="text-align:center;margin-top:16px;color:{!! $returnOk ? '#16813b' : '#e11' !!}"><b>{{ $returnMsg }}</b></p>
    @endif </main>

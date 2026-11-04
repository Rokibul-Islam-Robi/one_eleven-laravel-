@php
$snap = cart_snapshot();
@endphp @if (!$snap['items'])<div class="dr-empty">
    <span class="dr-empty-ico">{!! ic('cart', 'ic-empty') !!}</span>
    <b>Your cart is waiting for something good</b>
    <p>Explore wellness essentials picked for you.</p>
    <button type="button" class="btn-green" data-close-cart>Continue shopping</button>
</div>
@else <ul class="dr-list">
    @foreach ($snap['items'] as $p)        <li class="dr-item" data-id="{{ $p['cart_key'] ?? $p['id'] }}">
            <a class="dr-thumb" href="product/{{ $p['id'] }}"><img src="{{ $p['i'] }}" alt="{{ $p['n'] }}" loading="lazy"></a>
            <div class="dr-info">
                <small class="dr-brand">{{ $p['b'] }}</small>
                <a class="dr-name" href="product/{{ $p['id'] }}">{{ $p['n'] }}</a>
                @if (!empty($p['variant_label']))<small class="dr-variant">{{ $p['variant_label'] }}</small>@endif                <div class="dr-meta">
                    <form class="dr-qty" method="post" data-qty>@csrf
                        <input type="hidden" name="action" value="qty">
                        <input type="hidden" name="id" value="{{ $p['cart_key'] ?? $p['id'] }}">
                        <button type="submit" class="dr-step" name="qty" value="{!! max(1, $p['qty'] - 1) !!}" data-step="-1" aria-label="Decrease quantity"{!! $p['qty'] <= 1 ? ' disabled' : '' !!}>{!! ic('minus', 'ic-step') !!}</button>
                        <span class="dr-num" aria-live="polite">{!! (int)$p['qty'] !!}</span>
                        <button type="submit" class="dr-step" name="qty" value="{!! min(99, $p['qty'] + 1) !!}" data-step="1" aria-label="Increase quantity">{!! ic('plus', 'ic-step') !!}</button>
                    </form>
                    <form class="dr-rm" method="post" data-remove>@csrf
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="id" value="{{ $p['cart_key'] ?? $p['id'] }}">
                        <button type="submit" aria-label="Remove item">{!! ic('trash', 'ic-trash') !!}<span>Remove</span></button>
                    </form>
                </div>
            </div>
            <b class="dr-line" data-price="{!! (int)$p['line'] !!}">{!! money($p['line']) !!}</b>
        </li>
    @endforeach </ul>
@endif
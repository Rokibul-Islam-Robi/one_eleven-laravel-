@php
$snap = cart_snapshot();
@endphp @if (!$snap['items'])    <div class="dr-foot-empty">
        <a class="btn-green full" href="deals">Shop today's deals</a>
    </div>
@else    <div class="dr-sum">
        <p class="dr-row">
            <span>Subtotal ({!! $snap['count'] !!} item{!! $snap['count'] > 1 ? 's' : '' !!})</span>
            <b data-price="{!! (int)$snap['total'] !!}">{!! money($snap['total']) !!}</b>
        </p>
        <p class="dr-row dr-muted">
            <span>Delivery</span>
            <span>Calculated at checkout</span>
        </p>
        <p class="dr-row dr-total">
            <span>Estimated total</span>
            <b data-price="{!! (int)$snap['total'] !!}">{!! money($snap['total']) !!}</b>
        </p>
    </div>
    <a class="btn-green full" href="checkout">Proceed to Checkout {!! ic('arrow', 'ic-arrow-sm') !!}</a>
    <a class="dr-continue" href="cart">or view items in your cart</a>
    <p class="dr-secure">{!! ic('shield', 'ic-lock') !!} Secure checkout · Easy returns</p>
@endif
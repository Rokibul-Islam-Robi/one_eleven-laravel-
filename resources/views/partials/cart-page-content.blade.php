@if (!$cartProducts)        <div class="empty">
            <h2>Your cart is waiting for something good</h2>
            <p>Explore wellness essentials picked for you.</p>
            <a class="btn-green" href="./">Continue shopping</a>
        </div>
    @else        <div class="cart-layout">
            <div>
                @foreach ($cartProducts as $p)
@php
$ck = (string)($p['cart_key'] ?? $p['id']);
@endphp                    <div class="cart-row" data-id="{{ $ck }}">
                        <img src="{{ $p['i'] }}" alt="{{ $p['n'] }}">
                        <div class="cart-row-info">
                            <a href="product/{{ $p['id'] }}"><b>{{ $p['n'] }}</b></a>
                            <small>{{ $p['b'] }}</small>
                            @if (!empty($p['variant_label']))<small class="cr-variant">{{ $p['variant_label'] }}</small>@endif                            {!! price_html('b', (string)($p['unit'] ?? $p['p'])) !!}
                            <form method="post" class="qty-form">@csrf
                                <input type="hidden" name="action" value="qty">
                                <input type="hidden" name="id" value="{{ $ck }}">
                                <input type="number" name="qty" value="{{ $p['qty'] }}" min="1" max="99" aria-label="Quantity">
                                <button>Update</button>
                                <button name="action" value="remove">Remove</button>
                            </form>
                        </div>
                        {!! price_html('b', money((float)($p['line'] ?? priceval($p['p']) * $p['qty'])), 'row-total') !!}
                    </div>
                @endforeach            </div>

            <aside class="summary">
                <h2>Order Summary</h2>
                <p>Subtotal <b data-price="{!! (int)$cartTotal !!}">{!! money($cartTotal) !!}</b></p>
                <p>Delivery <b>Calculated at checkout</b></p>
                <hr>
                <p class="total">Estimated total <b data-price="{!! (int)$cartTotal !!}">{!! money($cartTotal) !!}</b></p>
                <a class="btn-green full" href="checkout">Proceed to checkout</a>
                <a class="continue" href="./">Continue shopping</a>
            </aside>
        </div>
    @endif

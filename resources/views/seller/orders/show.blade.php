@extends('seller.layout', ['title' => 'Order item'])
@section('content')
<section class="seller-card">
    <p><a href="/seller/orders">← My orders</a></p>
    <h1>Order {{ $item->order?->order_number ?: '#'.$item->order_id }}</h1>
    <p><strong>{{ $item->product_name }}</strong> · Quantity {{ number_format((int) $item->qty) }}</p>
    <p>Status: <strong>{{ ucfirst(str_replace('_', ' ', $item->status)) }}</strong></p>
    @if ($showDeliveryDetails && $item->order)
        <h2>Delivery details</h2>
        <p>{{ $item->order->shipping_name }} · {{ $item->order->shipping_phone }}</p>
        <p>{{ $item->order->shipping_address }}@if ($item->order->shipping_area), {{ $item->order->shipping_area }}@endif</p>
        @if ($item->tracking_code)<p>Tracking: {{ $item->tracking_code }}</p>@endif
    @elseif ($item->status === 'pending')
        <p>Customer delivery details are available after the order item is confirmed.</p>
    @endif

    @if ($nextStatuses)
        <form method="post" action="/seller/orders/{{ $item->id }}/status">
            @csrf
            <label for="status">Next fulfillment status</label>
            <select id="status" name="status" required>
                @foreach ($nextStatuses as $status)
                    <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <label for="tracking_code">Tracking code (optional)</label>
            <input id="tracking_code" name="tracking_code" maxlength="80" value="{{ old('tracking_code', $item->tracking_code) }}">
            <label for="seller_note">Seller note (optional)</label>
            <input id="seller_note" name="seller_note" maxlength="255" value="{{ old('seller_note', $item->seller_note) }}">
            <button type="submit">Update fulfillment</button>
        </form>
    @endif
</section>
@endsection

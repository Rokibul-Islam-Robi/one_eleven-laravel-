@extends('seller.layout', ['title' => 'Inventory'])
@section('content')
<section class="seller-card">
    <h1>Inventory</h1>
    <p>Adjust your product quantities. Stock adjustments are recorded and updated in the storefront inventory.</p>
    @if ($products->isEmpty())
        <p>No products to manage yet.</p>
    @else
        <div style="overflow-x:auto">
            <table>
                <thead><tr><th>Product</th><th>Listing status</th><th>On hand</th><th>Low-stock threshold</th><th>Adjust quantity</th></tr></thead>
                <tbody>
                @foreach ($products as $product)
                    <tr>
                        <td><strong>{{ $product->name }}</strong>@if ($product->sku)<br><small>SKU: {{ $product->sku }}</small>@endif</td>
                        <td>{{ ucfirst($product->approval_status) }}@if ($product->approval_status === 'approved' && ! $product->is_active) · inactive @endif</td>
                        <td>{{ number_format((int) $product->stock) }}</td>
                        <td>{{ number_format((int) $product->low_stock_threshold) }}</td>
                        <td>
                            <form method="post" action="/seller/products/{{ $product->id }}/inventory" class="seller-inventory-form">
                                @csrf
                                <label class="sr-only" for="adjustment-{{ $product->id }}">Quantity adjustment for {{ $product->name }}</label>
                                <input id="adjustment-{{ $product->id }}" type="number" name="adjustment" min="-1000000" max="1000000" required placeholder="+/- quantity">
                                <input name="note" maxlength="255" required placeholder="Reason">
                                <button type="submit">Update</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $products->links() }}
    @endif
</section>
<section class="seller-card">
    <h2>Inventory movement history</h2>
    <p>Stock holds, releases and adjustments for your products.</p>
    @if ($movements->isEmpty())
        <p>No inventory movements recorded yet.</p>
    @else
        <div style="overflow-x:auto">
            <table>
                <thead><tr><th>Date</th><th>Product</th><th>Movement</th><th>Change</th><th>Order / reason</th></tr></thead>
                <tbody>
                @foreach ($movements as $movement)
                    <tr>
                        <td>{{ \Illuminate\Support\Carbon::parse($movement->created_at)->format('d M Y, H:i') }}</td>
                        <td>{{ $movement->product_name }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $movement->type)) }}</td>
                        <td>{{ $movement->delta > 0 ? '+' : '' }}{{ number_format((int) $movement->delta) }}</td>
                        <td>{{ $movement->order_number ?: $movement->note }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $movements->links() }}
    @endif
</section>
<style>
    .seller-inventory-form{display:grid;grid-template-columns:110px minmax(140px,1fr) auto;gap:6px;align-items:center}
    .seller-inventory-form input{min-width:0}
    .seller-inventory-form button{margin:0;padding:8px 12px}
    .sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
    @media(max-width:760px){.seller-inventory-form{grid-template-columns:1fr}}
</style>
@endsection

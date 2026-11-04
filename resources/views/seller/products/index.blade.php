@extends('seller.layout', ['title' => 'My products'])
@section('content')
<section class="seller-card">
    <div class="seller-product-heading">
        <div>
            <h1>My products</h1>
            <p>Create product drafts, submit listings for review, and track moderation status.</p>
        </div>
        <a class="seller-button" href="/seller/products/create">Add a product</a>
    </div>
    @if ($products->isEmpty())
        <p>You have not added any products yet.</p>
    @else
        <div style="overflow-x:auto">
            <table>
                <thead><tr><th>Product</th><th>Status</th><th>Price</th><th>Stock</th><th>Updated</th><th></th></tr></thead>
                <tbody>
                @foreach ($products as $product)
                    <tr>
                        <td><strong>{{ $product->name }}</strong>@if ($product->sku)<br><small>SKU: {{ $product->sku }}</small>@endif</td>
                        <td>
                            <strong>{{ ucfirst($product->approval_status) }}</strong>
                            @if ($product->approval_status === 'rejected' && $product->rejection_reason)
                                <br><small>Reason: {{ $product->rejection_reason }}</small>
                            @endif
                            @if ($product->approval_status === 'approved' && ! $product->is_active)
                                <br><small>Inactive</small>
                            @endif
                        </td>
                        <td>৳{{ number_format((float) $product->price, 2) }}</td>
                        <td>{{ number_format((int) $product->stock) }}</td>
                        <td>{{ $product->updated_at?->format('M j, Y') ?? '—' }}</td>
                        <td>
                            @if ($product->approval_status !== 'pending')
                                <a class="seller-button" href="/seller/products/{{ $product->id }}/edit">Edit</a>
                            @else
                                <small>Under review</small>
                            @endif
                            @if ($product->approval_status === 'approved')
                                <a class="seller-button" href="/seller/inventory">Inventory</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $products->links() }}
    @endif
</section>
<style>
    .seller-product-heading{display:flex;justify-content:space-between;align-items:center;gap:16px}
    .seller-product-heading .seller-button{white-space:nowrap;margin-top:0}
    @media(max-width:650px){.seller-product-heading{align-items:flex-start;flex-direction:column}}
</style>
@endsection

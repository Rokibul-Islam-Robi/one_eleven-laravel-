@extends('seller.layout', ['title' => 'My orders'])
@section('content')
<section class="seller-card">
    <h1>My orders</h1>
    <p>Orders containing your products. Fulfillment updates are available after an order item is confirmed.</p>
    @if ($items->isEmpty())
        <p>No seller order items yet.</p>
    @else
        <div style="overflow-x:auto">
            <table>
                <thead><tr><th>Order</th><th>Product</th><th>Quantity</th><th>Status</th><th>Received</th><th></th></tr></thead>
                <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td>{{ $item->order?->order_number ?: '#'.$item->order_id }}</td>
                        <td>{{ $item->product_name }}</td>
                        <td>{{ number_format((int) $item->qty) }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $item->status)) }}</td>
                        <td>{{ $item->created_at?->format('M j, Y') ?? '—' }}</td>
                        <td><a class="seller-button" href="/seller/orders/{{ $item->id }}">View</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    @endif
</section>
@endsection

@extends('seller.layout', ['title' => 'Seller overview'])
@section('content')
<section class="seller-card">
    <h1>Seller overview</h1>
    <p>Welcome, {{ $seller->owner_name }} — {{ $seller->shop_name }}.</p>
    <p class="seller-status">
        Application: <strong>{{ ucfirst($seller->application_status) }}</strong> ·
        Account: <strong>{{ $seller->status }}</strong>
        @if (! $seller->is_active) · Inactive @endif
    </p>
    @if (! $seller->hasVerifiedEmail())
        <p>Your email address is not verified. Check your inbox or resend the verification link.</p>
        <form method="post" action="{{ route('verification.send') }}">@csrf<button type="submit">Resend verification link</button></form>
    @elseif ($seller->application_status === 'pending')
        <p>Your application is waiting for administrator review. You can update your application in your profile.</p>
    @elseif ($seller->application_status === 'rejected')
        <p>Your application was rejected. @if ($seller->status_reason)Reason: {{ $seller->status_reason }}@endif</p>
    @elseif ($seller->status === 'suspended')
        <p>Your account is suspended. @if ($seller->status_reason)Reason: {{ $seller->status_reason }}@endif</p>
    @elseif (! $seller->is_active)
        <p>Your seller account is inactive. Contact bHerb support for assistance.</p>
    @elseif ($seller->status !== 'active')
        <p>Your application has been approved and is awaiting account activation.</p>
    @else
        <p>Your seller account is active. You can create catalog listings, manage inventory, and track product review decisions.</p>
    @endif
</section>
@if ($seller->is_active && $seller->status === 'active' && $seller->application_status === 'approved' && $seller->hasVerifiedEmail())
<section class="seller-card">
    <h2>Product catalog</h2>
    <p>{{ $productSummary['total'] }} products · {{ $productSummary['pending'] }} awaiting review · {{ $productSummary['approved'] }} approved</p>
    <p>{{ $productSummary['low_stock'] }} products at or below their low-stock threshold.</p>
    <a class="seller-button" href="/seller/products">Manage products</a>
    <a class="seller-button" href="/seller/inventory">Manage inventory</a>
</section>
@endif
<section class="seller-card">
    <h2>Application documents</h2>
    @if ($documents->isEmpty())
        <p>No supporting documents have been uploaded.</p>
    @else
        <table>
            <thead><tr><th>Type</th><th>File</th><th>Status</th><th>Uploaded</th></tr></thead>
            <tbody>
            @foreach ($documents as $document)
                <tr>
                    <td>{{ str_replace('_', ' ', ucfirst($document->document_type)) }}</td>
                    <td><a href="/seller/documents/{{ $document->id }}">{{ $document->original_name }}</a></td>
                    <td>{{ ucfirst($document->status) }}</td>
                    <td>{{ $document->created_at->format('M j, Y') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</section>
@endsection

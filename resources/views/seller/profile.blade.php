@extends('seller.layout', ['title' => 'Seller profile'])
@section('content')
<section class="seller-card">
    <h1>Seller profile</h1>
    <p>Email: {{ $seller->email }} · {{ $seller->hasVerifiedEmail() ? 'Verified' : 'Not verified' }}</p>
    <p>Phone: {{ $seller->phone }} · {{ $seller->phone_verified_at ? 'Verified' : 'Not verified (no phone verification service is configured)' }}</p>
    <form method="post" action="/seller/profile">@csrf
        <div class="seller-grid">
            <div><label>Shop name</label><input name="shop_name" value="{{ old('shop_name', $seller->shop_name) }}" required maxlength="150"></div>
            <div><label>Legal business name</label><input name="legal_business_name" value="{{ old('legal_business_name', $seller->legal_business_name) }}" maxlength="150"></div>
            <div><label>Owner name</label><input name="owner_name" value="{{ old('owner_name', $seller->owner_name) }}" required maxlength="120"></div>
            <div><label>Category</label><input name="category" value="{{ old('category', $seller->category) }}" required maxlength="80"></div>
            <div><label>Phone</label><input name="phone" value="{{ old('phone', $seller->phone) }}" required maxlength="40"></div>
            <div><label>City</label><input name="city" value="{{ old('city', $seller->city) }}" maxlength="80"></div>
            <div><label>Tax identifier</label><input name="tax_identifier" value="{{ old('tax_identifier', $seller->tax_identifier) }}" maxlength="100"></div>
            <div><label>Trade license number</label><input name="trade_license" value="{{ old('trade_license', $seller->trade_license) }}" maxlength="80"></div>
            <div><label>Drug license number</label><input name="drug_license" value="{{ old('drug_license', $seller->drug_license) }}" maxlength="80"></div>
        </div>
        <label>Pickup address</label><textarea name="pickup_address" required maxlength="2000">{{ old('pickup_address', $seller->pickup_address ?? $seller->address) }}</textarea>
        <label>Return address</label><textarea name="return_address" maxlength="2000">{{ old('return_address', $seller->return_address) }}</textarea>
        <label>Description</label><textarea name="description" maxlength="5000">{{ old('description', $seller->description) }}</textarea>
        <button type="submit">Save profile</button>
    </form>
</section>
@if ($seller->is_active && $seller->status === 'active' && $seller->application_status === 'approved' && $seller->hasVerifiedEmail())
<section class="seller-card">
    <h2>Sensitive payout details</h2>
    @php
        $account = (string) $seller->payout_account;
        $maskedAccount = $account === '' ? 'Not set' : str_repeat('•', max(0, mb_strlen($account) - 4)) . mb_substr($account, -4);
    @endphp
    <p>Current account: {{ $maskedAccount }} · Verification: {{ $seller->payout_verified ? 'Verified' : 'Not verified' }}</p>
    <p>Changing payout details requires your current password and resets verification.</p>
    <form method="post" action="/seller/profile/payout">@csrf
        <div class="seller-grid">
            <div><label>Method</label><select name="payout_method" required>@foreach (['bkash' => 'bKash', 'nagad' => 'Nagad', 'bank' => 'Bank'] as $value => $label)<option value="{{ $value }}" @selected(old('payout_method', $seller->payout_method) === $value)>{{ $label }}</option>@endforeach</select></div>
            <div><label>Account name</label><input name="payout_account_name" value="{{ old('payout_account_name', $seller->payout_account_name) }}" required maxlength="120"></div>
            <div><label>Account number</label><input name="payout_account" value="" required maxlength="120" autocomplete="off"></div>
            <div><label>Current password</label><input type="password" name="current_password" required autocomplete="current-password"></div>
        </div>
        <button type="submit">Update payout details</button>
    </form>
</section>
@endif
@if ($seller->is_active && in_array($seller->status, ['pending', 'active'], true))
<section class="seller-card">
    <h2>Supporting documents</h2>
    <form method="post" action="/seller/profile/documents" enctype="multipart/form-data">@csrf
        <label>Document type</label>
        <select name="document_type" required><option value="trade_license">Trade license</option><option value="drug_license">Drug license</option><option value="tax">Tax document</option><option value="identity">Identity document</option><option value="other">Other</option></select>
        <label>File (PDF/JPG/PNG/WEBP, max 5 MB)</label><input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
        <button type="submit">Upload document</button>
    </form>
    @if ($documents->isNotEmpty())
        <ul>@foreach ($documents as $document)<li><a href="/seller/documents/{{ $document->id }}">{{ $document->original_name }}</a> — {{ ucfirst($document->status) }}</li>@endforeach</ul>
    @endif
</section>
@endif
@endsection

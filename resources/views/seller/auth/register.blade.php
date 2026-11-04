@extends('seller.layout', ['title' => 'Seller application'])
@section('content')
<section class="seller-card">
    <h1>Seller application</h1>
    <p>Applications are reviewed by bHerb. Dashboard access remains unavailable until approval, activation, and email verification.</p>
    <form method="post" action="/seller/register" enctype="multipart/form-data">
        @csrf
        <div class="seller-grid">
            <div><label>Shop name</label><input name="shop_name" value="{{ old('shop_name') }}" required maxlength="150"></div>
            <div><label>Legal business name (if applicable)</label><input name="legal_business_name" value="{{ old('legal_business_name') }}" maxlength="150"></div>
            <div><label>Owner / legal contact name</label><input name="owner_name" value="{{ old('owner_name') }}" required maxlength="120"></div>
            <div><label>Seller category</label><input name="category" value="{{ old('category') }}" required maxlength="80"></div>
            <div><label>Email</label><input name="email" type="email" value="{{ old('email') }}" required maxlength="190" autocomplete="email"></div>
            <div><label>Phone</label><input name="phone" type="tel" value="{{ old('phone') }}" required maxlength="40" autocomplete="tel"></div>
            <div><label>City</label><input name="city" value="{{ old('city') }}" maxlength="80"></div>
            <div><label>Tax identifier (if applicable)</label><input name="tax_identifier" value="{{ old('tax_identifier') }}" maxlength="100"></div>
            <div><label>Trade license number (if applicable)</label><input name="trade_license" value="{{ old('trade_license') }}" maxlength="80"></div>
            <div><label>Drug license number (if applicable)</label><input name="drug_license" value="{{ old('drug_license') }}" maxlength="80"></div>
        </div>
        <label>Pickup address</label><textarea name="pickup_address" required maxlength="2000">{{ old('pickup_address') }}</textarea>
        <label>Return address (if different)</label><textarea name="return_address" maxlength="2000">{{ old('return_address') }}</textarea>
        <label>Shop description</label><textarea name="description" maxlength="5000">{{ old('description') }}</textarea>
        <h2>Optional payout details</h2>
        <p>Details are private and are not displayed in full after submission.</p>
        <div class="seller-grid">
            <div><label>Method</label><select name="payout_method"><option value="">Select</option>@foreach (['bkash' => 'bKash', 'nagad' => 'Nagad', 'bank' => 'Bank'] as $value => $label)<option value="{{ $value }}" @selected(old('payout_method') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div><label>Account name</label><input name="payout_account_name" value="{{ old('payout_account_name') }}" maxlength="120"></div>
            <div><label>Account number</label><input name="payout_account" value="{{ old('payout_account') }}" maxlength="120" autocomplete="off"></div>
        </div>
        <h2>Supporting documents</h2>
        <p>PDF, JPG, PNG or WEBP; up to 5 MB per file. Files are stored privately.</p>
        <div class="seller-grid">
            <div><label>Document type</label><select name="document_type"><option value="other">Other</option><option value="trade_license">Trade license</option><option value="drug_license">Drug license</option><option value="tax">Tax document</option><option value="identity">Identity document</option></select></div>
            <div><label>Supporting document</label><input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp"></div>
        </div>
        <label>Password (at least 12 characters)</label><input type="password" name="password" required minlength="12" autocomplete="new-password">
        <label>Confirm password</label><input type="password" name="password_confirmation" required minlength="12" autocomplete="new-password">
        <button type="submit">Submit application</button>
    </form>
</section>
@endsection

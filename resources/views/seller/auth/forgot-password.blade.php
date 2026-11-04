@extends('seller.layout', ['title' => 'Reset password'])
@section('content')
<section class="seller-card">
    <h1>Reset seller password</h1>
    <form method="post" action="/seller/password/email">@csrf
        <label>Email</label><input type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
        <button type="submit">Send reset link</button>
    </form>
</section>
@endsection

@extends('seller.layout', ['title' => 'Choose password'])
@section('content')
<section class="seller-card">
    <h1>Choose a new seller password</h1>
    <form method="post" action="/seller/password/reset">@csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label>Email</label><input type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email">
        <label>New password (at least 12 characters)</label><input type="password" name="password" required minlength="12" autocomplete="new-password">
        <label>Confirm password</label><input type="password" name="password_confirmation" required minlength="12" autocomplete="new-password">
        <button type="submit">Update password</button>
    </form>
</section>
@endsection

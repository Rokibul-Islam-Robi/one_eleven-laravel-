@extends('seller.layout', ['title' => 'Seller sign in'])
@section('content')
<section class="seller-card">
    <h1>Seller sign in</h1>
    <form method="post" action="/seller/login">
        @csrf
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required autocomplete="current-password">
        <button type="submit">Sign in</button>
    </form>
    <p><a href="/seller/password/forgot">Forgot password?</a></p>
    <p>New to bHerb? <a href="/seller/register">Submit a seller application</a></p>
</section>
@endsection

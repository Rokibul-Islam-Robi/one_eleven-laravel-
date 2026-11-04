@extends('seller.layout', ['title' => 'Verify email'])
@section('content')
<section class="seller-card">
    <h1>Verify your email address</h1>
    <p>A verification link is required before the account can be activated. Phone verification is not currently available.</p>
    <form method="post" action="{{ route('verification.send') }}">@csrf<button type="submit">Resend verification link</button></form>
    <p><a href="/seller/dashboard">View application status</a></p>
</section>
@endsection

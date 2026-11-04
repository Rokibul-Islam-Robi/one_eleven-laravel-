@php
/* Account page: /account — instant sign in / account creation
   (name + mobile or email, no password), or the signed-in profile. */
\App\Support\PageMeta::$title = 'Account — bHerb';

$authError = (string)(session('auth_error', ''));
$authFlash = (string)(session('auth_flash', ''));
session()->forget(['auth_error', 'auth_flash', 'auth_mode']);

$me = auth_current_user();
$redirectTo = $path . ((request()->getQueryString() ?? '') !== '' ? '?' . request()->getQueryString() : '');
@endphp <main class="wrap page">
    @if ($me)        <div class="account-box">
            <h1>Hi, {{ $me['name'] }}</h1>
            <p>You're signed in as <b>{{ $me['contact'] }}</b>.</p>
            @if ($authFlash !== '')<p class="auth-flash ok">{{ $authFlash }}</p>@endif            <div class="acct-actions">
                <a class="btn-green" href="./">Continue shopping</a>
                <form method="post" action="account">@csrf
                    <input type="hidden" name="action" value="logout">
                    <input type="hidden" name="redirect" value="{{ $redirectTo }}">
                    <button type="submit" class="btn-outline">Sign out</button>
                </form>
            </div>
        </div>
    @else        @if ($authFlash !== '')<p class="auth-flash ok">{{ $authFlash }}</p>@endif        @if ($authError !== '')<p class="auth-flash bad">{{ $authError }}</p>@endif        <div class="account-grid">
            <div class="account-box">
                <h1>Sign In</h1>
                <p>Sign in with your mobile or email — instant, no OTP.</p>
                <form method="post" action="account" class="checkout-form">@csrf
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="redirect" value="{{ $redirectTo }}">
                    <label>Email or mobile number<input required type="text" name="contact" placeholder="Email or mobile number"></label>
                    <button type="submit" class="btn-green full">Sign In</button>
                </form>
            </div>
            <div class="account-box">
                <h1>Create Account</h1>
                <p>Instant access to your orders, wishlist and exclusive offers.</p>
                <form method="post" action="account" class="checkout-form">@csrf
                    <input type="hidden" name="action" value="create_account">
                    <input type="hidden" name="redirect" value="{{ $redirectTo }}">
                    <label>Full name<input required type="text" name="name" placeholder="Your full name"></label>
                    <label>Email or mobile number<input required type="text" name="contact" placeholder="Email or mobile number"></label>
                    <button type="submit" class="btn-green full">Create Account</button>
                </form>
            </div>
        </div>
    @endif </main>
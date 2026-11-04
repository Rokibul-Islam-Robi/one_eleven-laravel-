<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\SellerLog;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class SellerAuthController extends Controller
{
    public function loginForm()
    {
        if (Auth::guard('seller')->check()) {
            return redirect('/seller/dashboard');
        }

        return response()->view('seller.auth.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string', 'max:255'],
        ]);
        $email = Str::lower(trim($validated['email']));
        $key = 'seller-login:'.hash('sha256', $email.'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many sign-in attempts. Please wait before trying again.',
            ]);
        }

        if (! Auth::guard('seller')->attempt(['email' => $email, 'password' => $validated['password']])) {
            RateLimiter::hit($key, 60);
            usleep(random_int(120000, 260000));
            throw ValidationException::withMessages(['email' => 'The provided credentials are invalid.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $seller = Auth::guard('seller')->user();
        $seller->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => substr((string) $request->ip(), 0, 45),
        ])->save();
        $this->log($seller, 'seller.login', null);

        return redirect()->intended('/seller/dashboard');
    }

    public function registerForm()
    {
        return response()->view('seller.auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'shop_name' => ['required', 'string', 'min:2', 'max:150'],
            'legal_business_name' => ['nullable', 'string', 'max:150'],
            'owner_name' => ['required', 'string', 'min:2', 'max:120'],
            'category' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:190', Rule::unique('sellers', 'email')],
            'phone' => ['required', 'string', 'max:40', 'regex:/^[0-9+()\\-\\s]{7,40}$/'],
            'pickup_address' => ['required', 'string', 'max:2000'],
            'return_address' => ['nullable', 'string', 'max:2000'],
            'city' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:5000'],
            'tax_identifier' => ['nullable', 'string', 'max:100'],
            'trade_license' => ['nullable', 'string', 'max:80'],
            'drug_license' => ['nullable', 'string', 'max:80'],
            'payout_method' => ['nullable', Rule::in(['bkash', 'nagad', 'bank'])],
            'payout_account' => ['nullable', 'string', 'max:120'],
            'payout_account_name' => ['nullable', 'string', 'max:120'],
            'password' => ['required', 'string', 'min:12', 'max:255', 'confirmed'],
            'document' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,webp'],
            'document_type' => ['required_with:document', Rule::in(['trade_license', 'drug_license', 'tax', 'identity', 'other'])],
        ]);

        $files = $request->hasFile('document') ? [$validated['document']] : [];
        $types = $request->hasFile('document') ? [$validated['document_type']] : [];
        $email = Str::lower(trim($validated['email']));
        $storedPaths = [];

        try {
            $seller = DB::transaction(function () use ($validated, $email, $files, $types, $request, &$storedPaths) {
                $baseSlug = Str::slug($validated['shop_name']) ?: 'seller';
                $seller = Seller::query()->create([
                    'shop_name' => trim($validated['shop_name']),
                    'legal_business_name' => trim((string) ($validated['legal_business_name'] ?? '')) ?: null,
                    'slug' => $baseSlug.'-'.Str::lower(Str::random(8)),
                    'owner_name' => trim($validated['owner_name']),
                    'category' => trim($validated['category']),
                    'email' => $email,
                    'phone' => trim($validated['phone']),
                    'password_hash' => Hash::make($validated['password']),
                    'pickup_address' => trim($validated['pickup_address']),
                    'return_address' => trim((string) ($validated['return_address'] ?? '')) ?: null,
                    'address' => trim($validated['pickup_address']),
                    'city' => trim((string) ($validated['city'] ?? '')) ?: null,
                    'description' => trim((string) ($validated['description'] ?? '')) ?: null,
                    'tax_identifier' => trim((string) ($validated['tax_identifier'] ?? '')) ?: null,
                    'trade_license' => trim((string) ($validated['trade_license'] ?? '')) ?: null,
                    'drug_license' => trim((string) ($validated['drug_license'] ?? '')) ?: null,
                    'payout_method' => $validated['payout_method'] ?? null,
                    'payout_account' => trim((string) ($validated['payout_account'] ?? '')) ?: null,
                    'payout_account_name' => trim((string) ($validated['payout_account_name'] ?? '')) ?: null,
                    'status' => 'pending',
                    'application_status' => 'pending',
                    'is_active' => true,
                    'application_submitted_at' => now(),
                    'created_ip' => substr((string) $request->ip(), 0, 45),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($files as $index => $file) {
                    $path = $file->storeAs(
                        'seller-documents/'.$seller->id,
                        Str::random(40).'.'.$file->extension(),
                        'local'
                    );
                    if (! $path) {
                        throw ValidationException::withMessages(['documents' => 'A supporting document could not be stored.']);
                    }
                    $storedPaths[] = $path;
                    $seller->sellerDocuments()->create([
                        'document_type' => $types[$index],
                        'storage_path' => $path,
                        'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                        'mime_type' => (string) $file->getMimeType(),
                        'file_size' => (int) $file->getSize(),
                        'status' => 'pending',
                        'created_at' => now(),
                    ]);
                }

                $this->log($seller, 'application.submitted', 'Application submitted.');

                return $seller;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }

        Auth::guard('seller')->login($seller);
        $request->session()->regenerate();
        try {
            $seller->sendEmailVerificationNotification();
        } catch (Throwable $exception) {
            report($exception);

            return redirect('/seller/email/verify')
                ->with('status', 'Your application was saved. Verification email could not be sent; use resend after mail is configured.');
        }

        return redirect('/seller/email/verify')
            ->with('status', 'Application received. Verify your email before seller access can be activated.');
    }

    public function logout(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if ($seller) {
            $this->log($seller, 'seller.logout', null);
        }
        Auth::guard('seller')->logout();
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect('/seller/login')->with('status', 'You have been signed out.');
    }

    public function verificationNotice()
    {
        $seller = Auth::guard('seller')->user();
        if ($seller->hasVerifiedEmail()) {
            return redirect('/seller/dashboard');
        }

        return response()->view('seller.auth.verify-email');
    }

    public function sendVerification(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (! $seller->hasVerifiedEmail()) {
            $seller->sendEmailVerificationNotification();
        }

        return back()->with('status', 'A verification link has been sent if your address is still unverified.');
    }

    public function verifyEmail(Request $request, int $id, string $hash)
    {
        $seller = Auth::guard('seller')->user();
        abort_unless((int) $seller->getKey() === $id && hash_equals(sha1($seller->getEmailForVerification()), $hash), 403);

        if (! $seller->hasVerifiedEmail()) {
            $seller->markEmailAsVerified();
            $this->log($seller, 'email.verified', null);
        }

        return redirect('/seller/dashboard')->with('status', 'Email address verified.');
    }

    public function forgotPasswordForm()
    {
        return response()->view('seller.auth.forgot-password');
    }

    public function sendPasswordReset(Request $request)
    {
        $validated = $request->validate(['email' => ['required', 'email', 'max:190']]);
        Password::broker('sellers')->sendResetLink(['email' => Str::lower(trim($validated['email']))]);

        return back()->with('status', 'If an account exists for that address, a password reset link will be sent.');
    }

    public function resetPasswordForm(Request $request, string $token)
    {
        return response()->view('seller.auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string', 'min:12', 'max:255', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'max:255'],
        ]);
        $status = Password::broker('sellers')->reset([
            'email' => Str::lower(trim($validated['email'])),
            'password' => $validated['password'],
            'password_confirmation' => $validated['password_confirmation'],
            'token' => $validated['token'],
        ], function (Seller $seller, string $password) {
            $seller->forceFill(['password_hash' => Hash::make($password)])->save();
            event(new PasswordReset($seller));
            $this->log($seller, 'password.reset', null);
        });

        return $status === Password::PASSWORD_RESET
            ? redirect('/seller/login')->with('status', 'Password updated. You can now sign in.')
            : back()->withErrors(['email' => __($status)])->withInput($request->only('email'));
    }

    private function log(Seller $seller, string $action, ?string $detail): void
    {
        SellerLog::query()->create([
            'seller_id' => $seller->id,
            'actor_type' => 'seller',
            'actor_id' => $seller->id,
            'action' => $action,
            'detail' => $detail,
            'ip' => substr((string) request()->ip(), 0, 45),
            'created_at' => now(),
        ]);
    }
}

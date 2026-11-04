<?php

namespace App\Support;

use App\Models\Customer;

/**
 * Lightweight storefront accounts (Sign In / Create Account), passwordless.
 * Ported from includes/auth.php; storage = `customers` table, session key `user`.
 */
class CustomerAuth
{
    public static function current(): ?array
    {
        $u = session('user');
        if (! is_array($u) || ! isset($u['id'])) {
            return null;
        }
        $customer = Customer::find((int) $u['id']);
        if (! $customer) {
            session()->forget('user');
            return null;
        }

        return [
            'id' => (int) $customer->id,
            'name' => (string) $customer->full_name,
            'contact' => (string) ($u['contact'] ?? $customer->email ?? $customer->phone ?? ''),
        ];
    }

    public static function displayName(?array $user = null): string
    {
        $user = $user ?? self::current();
        if (! $user) {
            return '';
        }
        $parts = preg_split('/\s+/', trim((string) $user['name'])) ?: [];
        return ($parts[0] ?? '') !== '' ? $parts[0] : (string) $user['name'];
    }

    public static function normalizeContact($value): ?string
    {
        $v = trim((string) $value);
        if ($v === '') {
            return null;
        }
        if (filter_var($v, FILTER_VALIDATE_EMAIL) !== false) {
            return strtolower($v);
        }
        $digits = preg_replace('/[^0-9+]/', '', $v) ?? '';
        $digits = ltrim($digits, '+');
        if (strlen($digits) >= 7 && strlen($digits) <= 15) {
            return '+' . $digits;
        }
        return null;
    }

    public static function findByContact(string $contact): ?array
    {
        $col = str_contains($contact, '@') ? 'email' : 'phone';
        $row = Customer::where($col, $contact)->first();
        if ($row) {
            return ['id' => (int) $row->id, 'name' => (string) $row->full_name, 'contact' => $contact];
        }
        return null;
    }

    /** @return array{ok:bool,error:?string,user:?array} */
    public static function create(string $name, string $contact): array
    {
        $name = trim(preg_replace('/\s+/', ' ', $name) ?? '');
        if (mb_strlen($name) < 2) {
            return ['ok' => false, 'error' => 'Please enter your full name.', 'user' => null];
        }
        $contact = self::normalizeContact($contact);
        if ($contact === null) {
            return ['ok' => false, 'error' => 'Please enter a valid mobile number or email address.', 'user' => null];
        }
        if (self::findByContact($contact) !== null) {
            return ['ok' => false, 'error' => 'An account with this mobile/email already exists — please sign in instead.', 'user' => null];
        }
        $isEmail = str_contains($contact, '@');
        /* passwordless model: random unusable filler satisfies NOT NULL */
        $row = Customer::create([
            'full_name' => $name,
            'email' => $isEmail ? $contact : null,
            'phone' => $isEmail ? null : $contact,
            'password_hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
        ]);
        $user = ['id' => (int) $row->id, 'name' => $name, 'contact' => $contact];
        session()->regenerate();
        session(['user' => $user]);
        return ['ok' => true, 'error' => null, 'user' => $user];
    }

    /** @return array{ok:bool,error:?string,user:?array} */
    public static function login(string $contact): array
    {
        $contact = self::normalizeContact($contact);
        if ($contact === null) {
            return ['ok' => false, 'error' => 'Please enter a valid mobile number or email address.', 'user' => null];
        }
        $user = self::findByContact($contact);
        if ($user === null) {
            return ['ok' => false, 'error' => 'No account found for this mobile/email — create one first.', 'user' => null];
        }
        session()->regenerate();
        session(['user' => $user]);
        return ['ok' => true, 'error' => null, 'user' => $user];
    }

    public static function logout(): void
    {
        session()->invalidate();
        session()->regenerateToken();
    }
}

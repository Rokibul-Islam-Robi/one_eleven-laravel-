<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** require_login(): signed-in, not idle/expired, account still active. */
class AdminAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        if (! is_logged_in()) {
            if ($request->isMethod('get')) {
                session(['admin_login_return' => $request->getRequestUri()]);
            }
            return redirect_admin('login');
        }

        $s = config('admin.session');
        $now = time();
        if ($now - (int) session('admin_last_activity', $now) > $s['idle']) {
            return $this->expire('You were signed out after 30 minutes of inactivity.', 'warning');
        }
        if ($now - (int) session('admin_login_time', $now) > $s['absolute']) {
            return $this->expire('Your session reached the 8-hour limit. Please sign in again.', 'warning');
        }
        session(['admin_last_activity' => $now]);

        // re-validate that the account still exists and is active
        $row = qrow('SELECT id, name, email, role, status FROM users WHERE id = ?', [(int) session('admin_user.id')]);
        if (! $row || $row['status'] !== 'active') {
            return $this->expire('Your account is no longer active.', 'error');
        }
        session(['admin_user' => array_merge(session('admin_user'), [
            'name' => $row['name'], 'email' => $row['email'], 'role' => $row['role'],
        ])]);

        return $next($request);
    }

    private function expire(string $message, string $type)
    {
        session()->forget(['admin_user', 'admin_login_time', 'admin_last_activity']);
        session()->regenerate();
        flash($type, $message);
        return redirect_admin('login');
    }
}

<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Admin authentication helpers (port of admin/includes/auth.php + the router
 * resolution of admin/index.php). The session key is `admin_user`, separate from
 * the storefront customer session (`user`).
 */
class AdminAuth
{
    /** URL module => [controller class, entry method, permission module] */
    public static function modules(): array
    {
        $c = 'App\\Http\\Controllers\\Admin\\';
        return [
            'dashboard'      => [$c . 'DashboardController',     'mod_dashboard',     'dashboard'],
            'products'       => [$c . 'ProductsController',      'mod_products',      'products'],
            'doctors'        => [$c . 'DoctorsController',       'mod_doctors',       'doctors'],
            'doctor-coupons' => [$c . 'CouponsController',       'mod_dcoupons',      'doctor_coupons'],
            'offers'         => [$c . 'CouponsController',       'mod_offers',        'offers'],
            'courier'        => [$c . 'CourierController',       'mod_courier',       'courier'],
            'prescriptions'  => [$c . 'PrescriptionsController', 'mod_prescriptions', 'prescriptions'],
            'inventory'      => [$c . 'InventoryController',     'mod_inventory',     'inventory'],
            'orders'         => [$c . 'OrdersController',        'mod_orders',        'orders'],
            'customers'      => [$c . 'CustomersController',     'mod_customers',     'customers'],
            'sellers'         => [$c . 'SellersController',        'mod_sellers',        'sellers'],
            'content'        => [$c . 'ContentController',       'mod_content',       'content'],
            'reports'        => [$c . 'ReportsController',       'mod_reports',       'reports'],
            'search'         => [$c . 'SearchController',        'mod_search',        'search'],
        ];
    }

    /** [module, action, id] from /admin/{module}/{action}/{id} (with the ?p= fallback). */
    public static function resolve(Request $request): array
    {
        $module = trim((string) $request->route('module'), '/');
        if ($module === '' || $module === 'index.php') {
            $module = trim((string) $request->query('p'), '/') ?: 'dashboard';
        }
        $action = (string) ($request->route('action') ?: 'index');
        $routeId = $request->route('id');
        $id = $routeId !== null ? max(0, (int) $routeId) : max(0, (int) $request->post('id', 0));
        return [$module, $action, $id];
    }

    /** Minimum permission needed for the action: view | edit | delete. */
    public static function actionPerm(string $action): string
    {
        if ($action === 'delete') {
            return 'delete';
        }
        return in_array($action, ['new', 'create', 'edit', 'update', 'save'], true) ? 'edit' : 'view';
    }

    public static function throttleCheck(string $email): void
    {
        $s = config('admin.session');
        $ip = client_ip();
        $window = date('Y-m-d H:i:s', time() - $s['lockout']);
        $emailFails = (int) qval(
            'SELECT COUNT(*) FROM login_attempts WHERE email=? AND success=0 AND created_at >= ?', [$email, $window]
        );
        $ipFails = (int) qval(
            'SELECT COUNT(*) FROM login_attempts WHERE ip=? AND success=0 AND created_at >= ?', [$ip, $window]
        );
        if ($emailFails >= $s['max_attempts'] || $ipFails >= $s['max_attempts'] * 2) {
            throw new \RuntimeException('Too many failed sign-in attempts. Please wait 15 minutes and try again.');
        }
    }

    public static function recordAttempt(string $email, bool $success): void
    {
        try {
            q('INSERT INTO login_attempts (email, ip, success, created_at) VALUES (?,?,?,?)',
                [$email, client_ip(), $success ? 1 : 0, date('Y-m-d H:i:s')]);
        } catch (\Throwable $e) {
            error_log('record_login_attempt: ' . $e->getMessage());
        }
    }

    public static function attempt(string $email, string $password): array
    {
        self::throttleCheck($email);

        $user = qrow('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);
        $ok = $user && $user['status'] === 'active'
            && in_array($user['role'], ['admin', 'manager', 'staff'], true)
            && password_verify($password, (string) $user['password_hash']);

        if (! $ok) {
            self::recordAttempt($email, false);
            usleep(random_int(150000, 350000)); // slow down brute force
            throw new \RuntimeException('Invalid email or password.');
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            q('UPDATE users SET password_hash=? WHERE id=?', [password_hash($password, PASSWORD_DEFAULT), (int) $user['id']]);
        }

        self::recordAttempt($email, true);
        session()->regenerate();
        $admin = [
            'id' => (int) $user['id'], 'name' => $user['name'],
            'email' => $user['email'], 'role' => $user['role'],
        ];
        session([
            'admin_user' => $admin,
            'admin_login_time' => time(),
            'admin_last_activity' => time(),
        ]);

        try {
            q('UPDATE users SET last_login_at=?, last_login_ip=? WHERE id=?',
                [date('Y-m-d H:i:s'), client_ip(), (int) $user['id']]);
        } catch (\Throwable $e) { /* non-critical */ }

        log_action('login', 'user', (int) $user['id'], $user['email']);
        return $admin;
    }

    public static function logout(): void
    {
        if (is_logged_in()) {
            log_action('logout', 'user', (int) session('admin_user.id'), session('admin_user.email'));
        }
        session()->forget(['admin_user', 'admin_login_time', 'admin_last_activity', 'admin_login_return']);
        session()->regenerate();
    }
}

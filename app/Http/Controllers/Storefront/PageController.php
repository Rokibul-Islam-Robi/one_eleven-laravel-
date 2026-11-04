<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Support\Cart;
use App\Support\Catalog;
use App\Support\CustomerAuth;
use App\Support\PageMeta;
use App\Support\StorefrontData;
use Illuminate\Http\Request;

/**
 * Replaces the original index.php / category.php / brand.php front controllers:
 * resolve the view, apply classic POST actions (then redirect), render the page
 * between the shared header and footer.
 */
class PageController extends Controller
{
    public function show(Request $request)
    {
        $view = (string) ($request->route('view') ?? 'home');
        if ($request->isMethod('post')) {
            return $this->classicAction($request, $view);
        }
        if ($view === 'account' && CustomerAuth::current()) {
            return redirect(site_base_path() . '/account/profile');
        }
        return $this->render($view);
    }

    /** Render a page view inside the storefront chrome. Also used by the checkout controllers. */
    public static function render(string $view, array $data = [], ?int $status = null)
    {
        PageMeta::reset();
        view()->share(StorefrontData::globals());

        if ($view === 'home') {
            $sets = Catalog::homeSets();
            $sets['trending'] = Catalog::trending();
            $content = view('storefront.home', ['sets' => $sets])->render();
        } else {
            $content = view('storefront.pages.' . $view, $data)->render();
        }

        view()->share(['pageTitle' => PageMeta::$title, 'pageDesc' => PageMeta::$desc]);
        return response(view('layouts.storefront', ['content' => $content])->render(), $status ?? PageMeta::$status);
    }

    /** Port of includes/actions.php (POST -> mutate -> redirect). */
    private function classicAction(Request $request, string $view)
    {
        // brand.php / category.php keep no-JS posts on the exact URL (query string included)
        if (! $request->has('redirect') && in_array($view, ['brand', 'category'], true) && str_ends_with($request->path(), '.php')) {
            $qs = http_build_query($request->query());
            $request->merge(['redirect' => $request->path() . ($qs !== '' ? '?' . $qs : '')]);
        }

        $action = (string) $request->input('action', '');
        $id = (string) $request->input('id', '');
        $qty = (int) $request->input('qty', 1);
        $variant = trim((string) $request->input('variant', ''));
        if ($variant !== '' && $action === 'add') {
            $id .= '#' . $variant;
        }

        if ($action === 'add') {
            Cart::apply('add', $id, max(1, min(99, $qty)));
        } elseif ($action === 'qty') {
            Cart::apply($action, $id, max(1, min(99, $qty)));
        } elseif (in_array($action, ['wish', 'remove', 'clear'], true)) {
            Cart::apply($action, $id, max(0, min(99, $qty)));
        } elseif (in_array($action, ['apply_quote', 'checkout', 'return_request'], true)) {
            $ctl = 'App\\Http\\Controllers\\Storefront\\CheckoutController';
            if (class_exists($ctl)) {
                $resp = app($ctl)->legacyAction($request, $action);
                if ($resp) {
                    return $resp;
                }
            }
        } elseif ($action === 'create_account') {
            $res = CustomerAuth::create((string) $request->input('name', ''), (string) $request->input('contact', ''));
            if (! $res['ok']) {
                session(['auth_error' => (string) $res['error'], 'auth_mode' => 'create']);
            } else {
                session(['auth_flash' => 'Welcome, ' . $res['user']['name'] . '! Your account is ready.']);
            }
        } elseif ($action === 'login') {
            $res = CustomerAuth::login((string) $request->input('contact', ''));
            if (! $res['ok']) {
                session(['auth_error' => (string) $res['error'], 'auth_mode' => 'login']);
            } else {
                session(['auth_flash' => 'Welcome back, ' . $res['user']['name'] . '!']);
            }
        } elseif ($action === 'logout') {
            CustomerAuth::logout();
            session(['auth_flash' => 'You have been signed out.']);
        }

        $path = '/' . ltrim($request->path(), '/');
        $redirect = (string) $request->input('redirect', $path);
        if ($redirect === '' || $redirect[0] !== '/') {
            $redirect = '/' . $redirect;
        }
        return redirect(site_base_path() . $redirect);
    }
}

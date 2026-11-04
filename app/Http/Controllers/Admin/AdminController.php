<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminAuth;
use Illuminate\Http\Request;

/** Front controller of the admin CMS: login/logout + dispatch to the 12 module controllers. */
class AdminController extends Controller
{
    public function login(Request $request)
    {
        if (is_logged_in()) {
            aredirect('dashboard');
        }
        $error = null;

        if ($request->isMethod('post')) {
            $ok = false;
            try {
                AdminAuth::attempt(post('email'), (string) $request->post('password', ''));
                $ok = true;
            } catch (\RuntimeException $ex) {
                $error = $ex->getMessage();
            }
            if ($ok) {
                // (redirects live outside the try: HttpResponseException is itself a RuntimeException)
                $return = session()->pull('admin_login_return');
                // only allow internal admin redirects
                if ($return && str_starts_with((string) $return, '/') && ! str_starts_with((string) $return, '//')
                    && str_contains((string) $return, '/admin')) {
                    return redirect()->to(rtrim($request->getSchemeAndHttpHost(), '/') . $return);
                }
                aredirect('dashboard');
            }
        }

        return response()->view('admin.login', ['error' => $error]);
    }

    public function logout(Request $request)
    {
        if (! $request->isMethod('post')) {
            aredirect('dashboard');
        }
        AdminAuth::logout();
        aredirect('login');
    }

    public function handle(Request $request)
    {
        [$module, $action, $id] = AdminAuth::resolve($request);
        $GLOBALS['ADMIN_MODULE'] = $module;
        adm_status(200);

        $map = AdminAuth::modules();
        if (! isset($map[$module])) {
            adm_status(404);
            render_layout('Not found', '', '<div class="adm-card adm-card-pad adm-empty"><h3>Page not found</h3><p>The admin module “' . e($module) . '” does not exist.</p><a class="abtn primary mt-3" href="' . e(aurl('dashboard')) . '">Back to dashboard</a></div>');
        }

        [$class, $fn] = $map[$module];
        $class::$fn($action, $id);

        // module functions always end in render_layout()/aredirect()/adm_die(), which short-circuit
        abort(500, 'Admin module returned no response.');
    }
}

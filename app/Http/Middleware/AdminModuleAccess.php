<?php

namespace App\Http\Middleware;

use App\Support\AdminAuth;
use Closure;
use Illuminate\Http\Request;

/** require_module(perm, view|edit|delete) for the requested /admin/{module}/{action}. */
class AdminModuleAccess
{
    public function handle(Request $request, Closure $next)
    {
        [$module, $action] = AdminAuth::resolve($request);
        $map = AdminAuth::modules();
        if (isset($map[$module])) {
            $perm = AdminAuth::actionPerm($action);
            // any state-changing request needs at least edit rights (even for action names not listed as edit)
            if ($perm === 'view' && ! $request->isMethod('get') && ! $request->isMethod('head')) {
                $perm = 'edit';
            }
            require_module($map[$module][2], $perm);
        }
        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ActiveSeller
{
    public function handle(Request $request, Closure $next)
    {
        $seller = $request->attributes->get('seller');
        abort_unless(
            $seller
            && $seller->status === 'active'
            && $seller->application_status === 'approved'
            && $seller->is_active
            && $seller->hasVerifiedEmail(),
            403,
            'An active, approved, email-verified seller account is required.'
        );

        return $next($request);
    }
}

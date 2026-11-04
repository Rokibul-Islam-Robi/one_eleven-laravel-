<?php

namespace App\Http\Middleware;

use App\Models\Seller;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SellerAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        $sellerId = Auth::guard('seller')->id();
        $seller = $sellerId ? Seller::query()->find($sellerId) : null;

        if (! $seller) {
            Auth::guard('seller')->logout();
            return redirect('/seller/login')->with('status', 'Please sign in to continue.');
        }

        Auth::guard('seller')->setUser($seller);
        $request->attributes->set('seller', $seller);

        return $next($request);
    }
}

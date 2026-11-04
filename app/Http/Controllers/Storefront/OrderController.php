<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/** /order-success — confirmation page (+ classic return_request POST). */
class OrderController extends Controller
{
    public function show(Request $request)
    {
        if ($request->isMethod('post')) {
            $resp = app(CheckoutController::class)->legacyAction($request, (string) $request->input('action', 'return_request'));
            return $resp ?? redirect(site_base_path() . '/order-success');
        }
        return PageController::render('order-success');
    }
}

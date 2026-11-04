<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\CheckoutService;
use App\Services\CouponService;
use App\Services\CourierService;
use App\Services\OrderService;
use App\Services\Payments\PaymentConfig;
use App\Services\Payments\PaymentService;
use App\Services\PrescriptionService;
use App\Support\Cart;
use App\Support\CustomerAuth;
use Illuminate\Http\Request;

/** /checkout page, classic POST actions (apply_quote, checkout, return_request), api/checkout.php, api/quote.php. */
class CheckoutController extends Controller
{
    public function show(Request $request)
    {
        if ($request->isMethod('post')) {
            return $this->legacyAction($request, (string) $request->input('action', 'checkout'))
                ?? redirect(site_base_path() . '/checkout');
        }
        return PageController::render('checkout');
    }

    /** Port of the checkout-related branches of includes/actions.php. Returns a redirect response. */
    public function legacyAction(Request $request, string $action)
    {
        $base = site_base_path();

        if ($action === 'apply_quote') {
            $cy = (int) $request->input('courier_id', 0);
            $q = ($cy > 0 && CourierService::byId($cy)) ? $cy : 0;
            session(['checkout_quote' => [
                'courier_id' => (string) $q,
                'coupon_code' => strtoupper(trim((string) $request->input('coupon_code', ''))),
            ]]);
            return redirect($base . '/checkout');
        }

        if ($action === 'return_request') {
            $r = OrderService::requestReturn((int) $request->input('order_id', 0), trim((string) $request->input('reason', '')));
            session([
                'return_msg' => $r['ok'] ? 'Return request submitted for order #' . (int) $request->input('order_id', 0) . '.' : $r['error'],
                'return_ok' => $r['ok'] ? 1 : 0,
            ]);
            return redirect($base . '/order-success');
        }

        if ($action !== 'checkout') {
            return null;
        }

        $post = $request->all();
        if ($request->hasFile('rx_file') && PrescriptionService::present($request->file('rx_file'))) {
            $rx = PrescriptionService::store(
                $request->file('rx_file'),
                (string) $request->input('name', ''),
                (string) $request->input('phone', ''),
                CustomerAuth::current()['id'] ?? null
            );
            if (! isset($rx['error'])) {
                $post['prescription_id'] = (int) $rx['id'];
            }
        }
        if (empty($post['courier_id'])) {
            $post['courier_id'] = CourierService::resolveId((string) $request->input('courier', ''));
        }
        $post['payment_method'] = PaymentConfig::normalize((string) $request->input('payment_method', 'cod'));
        $result = CheckoutService::createOrder($post);

        if ($result['ok']) {
            $payMethod = (string) ($result['payment_method'] ?? 'cod');
            session(['last_order' => [
                'id' => $result['order_number'], 'db_id' => $result['order_id'],
                'name' => trim((string) $request->input('name', '')), 'phone' => trim((string) $request->input('phone', '')),
                'address' => trim((string) $request->input('address', '')), 'items' => [],
                'total' => (float) ($result['total'] ?? 0), 'payment_status' => 'unpaid',
            ]]);
            if ($payMethod === 'cod') {
                Cart::clear();
                session()->forget('checkout_quote');
                return redirect($base . '/order-success');
            }
            $customer = $result['customer'] ?? [
                'name' => (string) $request->input('name', ''), 'phone' => (string) $request->input('phone', ''),
                'email' => (string) $request->input('email', ''), 'address' => (string) $request->input('address', ''),
                'area' => (string) $request->input('area', ''),
            ];
            $init = PaymentService::initiate($payMethod, $this->gatewayOrder($result), $customer);
            if (! $init['ok']) {
                OrderService::markFailed((string) $result['order_number'], (string) $init['error']);
                session(['checkout_error' => (string) $init['error']]);
                return redirect($base . '/checkout');
            }
            OrderService::markPending((string) $result['order_number'], [
                'payment_method' => $payMethod, 'val_id' => (string) ($init['ref'] ?? ''), 'demo' => ! empty($init['demo']),
                'card_type' => PaymentConfig::label($payMethod) . (! empty($init['demo']) ? ' (demo)' : ''),
            ]);
            session(['last_order.payment_status' => 'pending']);
            return redirect()->away($init['gateway_url']);
        }

        session([
            'checkout_error' => $result['error'],
            'checkout_old' => [
                'name' => (string) $request->input('name', ''), 'phone' => (string) $request->input('phone', ''),
                'address' => (string) $request->input('address', ''), 'courier_id' => (string) $request->input('courier_id', ''),
                'coupon_code' => (string) $request->input('coupon_code', ''), 'payment_method' => (string) $request->input('payment_method', 'cod'),
            ],
        ]);
        return redirect($base . '/checkout');
    }

    private function gatewayOrder(array $result): array
    {
        return [
            'order_number' => (string) $result['order_number'],
            'total' => (float) ($result['total'] ?? 0),
            'shipping' => (float) ($result['shipping'] ?? 0),
            'items' => array_map(fn ($ln) => ['product_name' => $ln['name'], 'price' => $ln['price'], 'qty' => $ln['qty']],
                CheckoutService::resolveCart()['lines'] ?: []),
        ];
    }

    /** POST /api/checkout.php — JSON contract identical to the original. */
    public function apiCheckout(Request $request)
    {
        $json = fn (array $data, int $status = 200) => response()->json($data, $status, [], JSON_UNESCAPED_UNICODE)->header('Cache-Control', 'no-store');

        if (! $request->isMethod('post')) {
            return $json(['status' => 'fail', 'reason' => 'Method not allowed'], 405);
        }
        $input = $request->json()->all();
        if (! $input) {
            $input = $request->all();
        }
        $post = [
            'name' => trim((string) ($input['name'] ?? '')), 'phone' => trim((string) ($input['phone'] ?? '')),
            'email' => trim((string) ($input['email'] ?? '')), 'address' => trim((string) ($input['address'] ?? '')),
            'area' => trim((string) ($input['area'] ?? '')), 'note' => trim((string) ($input['note'] ?? '')),
            'payment_method' => PaymentConfig::normalize((string) ($input['payment_method'] ?? 'cod')),
            'coupon_code' => strtoupper(trim((string) ($input['coupon'] ?? $input['coupon_code'] ?? ''))),
            'courier_id' => (int) ($input['courier_id'] ?? 0),
        ];
        if (! PaymentConfig::known((string) ($input['payment_method'] ?? 'cod'))) {
            return $json(['status' => 'fail', 'reason' => 'Unsupported payment method.'], 422);
        }
        if (! $post['courier_id']) {
            $post['courier_id'] = CourierService::resolveId((string) ($input['courier'] ?? ''));
        }

        $rxMsgs = [
            'no_file' => 'No file received.', 'upload_failed' => 'Upload failed, please try again.',
            'too_large' => 'File must be 5 MB or smaller.', 'bad_type' => 'Only JPG, PNG, WEBP or PDF files are accepted.',
            'store_failed' => 'Could not store the file, please try again.',
        ];
        if ($request->hasFile('rx_file') && PrescriptionService::present($request->file('rx_file'))) {
            $rx = PrescriptionService::store($request->file('rx_file'), $post['name'], $post['phone']);
            if (isset($rx['error'])) {
                return $json(['status' => 'fail', 'reason' => 'Prescription upload rejected',
                    'errors' => ['rx_file' => $rxMsgs[$rx['error']] ?? 'Invalid file.']], 422);
            }
            $post['prescription_id'] = (int) $rx['id'];
        }

        $result = CheckoutService::createOrder($post);
        if (! $result['ok']) {
            $fail = ['status' => 'fail', 'reason' => $result['error']];
            $fieldErrs = CheckoutService::errors($post);
            if ($fieldErrs) {
                $fail['errors'] = $fieldErrs;
            }
            return $json($fail, 422);
        }

        $customer = $result['customer'] ?? [
            'name' => $post['name'], 'phone' => $post['phone'], 'email' => $post['email'],
            'address' => $post['address'], 'area' => $post['area'],
        ];
        session(['last_order' => [
            'db_id' => (int) $result['order_id'], 'id' => (string) $result['order_number'],
            'order_number' => (string) $result['order_number'], 'name' => $customer['name'],
            'phone' => $customer['phone'], 'address' => $customer['address'],
            'total' => (float) ($result['total'] ?? 0), 'items' => [],
        ]]);

        if ($post['payment_method'] === 'cod') {
            Cart::clear();
            session()->forget('checkout_quote');
            return $json(['status' => 'success', 'redirect' => 'order-success',
                'order_number' => (string) $result['order_number'], 'method' => 'cod']);
        }

        $orderForGateway = $this->gatewayOrder($result);
        $init = PaymentService::initiate($post['payment_method'], $orderForGateway, $customer);
        if (! $init['ok']) {
            OrderService::markFailed((string) $result['order_number'], (string) $init['error']);
            return $json(['status' => 'fail', 'reason' => $init['error']], 502);
        }
        OrderService::markPending((string) $result['order_number'], [
            'payment_method' => $post['payment_method'], 'val_id' => (string) ($init['ref'] ?? ''), 'demo' => ! empty($init['demo']),
            'card_type' => ! empty($init['demo']) ? PaymentConfig::label($post['payment_method']) . ' (demo)' : PaymentConfig::label($post['payment_method']),
        ]);
        session(['pay_order' => [
            'order_number' => (string) $result['order_number'], 'order_id' => (int) $result['order_id'],
            'method' => $post['payment_method'], 'demo' => ! empty($init['demo']), 'ref' => (string) ($init['ref'] ?? ''),
        ]]);

        $resp = ['status' => 'success', 'data' => ['GatewayPageURL' => $init['gateway_url']],
            'order_number' => (string) $result['order_number'], 'method' => $post['payment_method'], 'demo' => ! empty($init['demo'])];
        if ($post['payment_method'] === 'sslcommerz') {
            if (empty($init['demo'])) {
                $resp['postdata'] = PaymentService::sslczEmbedPostdata($orderForGateway, $customer);
                $resp['gateway_api'] = PaymentConfig::base('sslcommerz') . '/gwprocess/v4/api.php';
            }
        } else {
            $resp['redirect'] = $init['gateway_url'];
        }
        return $json($resp);
    }

    /** GET /api/quote.php?code=&subtotal= */
    public function apiQuote(Request $request)
    {
        $code = strtoupper(trim((string) $request->query('code', '')));
        $subtotal = (float) $request->query('subtotal', 0);
        $json = fn (array $d) => response()->json($d, 200, [], JSON_UNESCAPED_UNICODE)->header('Cache-Control', 'no-store');

        if ($code === '') {
            return $json(['ok' => true, 'discount' => 0, 'message' => '']);
        }
        if ($subtotal <= 0) {
            return $json(['ok' => false, 'discount' => 0, 'message' => 'Invalid subtotal.']);
        }
        [$ok, $discount, $msg] = CouponService::validate($code, $subtotal);
        return $json([
            'ok' => $ok, 'discount' => $ok ? round($discount, 2) : 0,
            'message' => $ok ? ('Coupon ' . $code . ' applied — you save ' . money($discount) . '.') : $msg,
            'code' => $ok ? $code : '',
        ]);
    }
}

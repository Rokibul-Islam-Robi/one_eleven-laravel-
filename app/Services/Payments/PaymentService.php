<?php

namespace App\Services\Payments;

use App\Services\OrderService;
use App\Support\Districts;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Payment engine — SSLCommerz, bKash (Tokenized Checkout) and Nagad, plus the
 * unified callback pipeline used by payment/{success,fail,cancel,ipn}.
 * Faithful port of includes/payment.php. With no credentials configured the
 * local demo gateway (payment/demo) stands in, exactly as in the original.
 */
class PaymentService
{
    private const SSLCZ_SESSION_PATH = '/gwprocess/v4/api.php';
    private const SSLCZ_VALIDATE_PATH = '/validation/verificationAPI';
    private const SSLCZ_DEFAULT_POSTCODE = '1000';
    private const BKASH_GRANT_PATH = '/tokenized/checkout/token/grant';
    private const BKASH_CREATE_PATH = '/tokenized/checkout/create';
    private const BKASH_EXECUTE_PATH = '/tokenized/checkout/execute';
    private const BKASH_QUERY_PATH = '/tokenized/checkout/query';
    private const NAGAD_INIT_PATH = 'check-out/initialize/';
    private const NAGAD_COMPLETE_PATH = 'check-out/complete/';
    private const NAGAD_VERIFY_PATH = 'verify/payment/';

    /* ------------------------------ helpers ------------------------------ */
    public static function url(string $path): string
    {
        return url(ltrim($path, '/'));
    }

    private static function clientIp(): string
    {
        $ip = request()->ip();
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
    }

    private static function errorMessage(array $json, string $fallback = ''): string
    {
        foreach (['errorMessage', 'failedreason', 'statusMessage', 'message', 'reason', 'error'] as $key) {
            $v = trim((string) ($json[$key] ?? ''));
            if ($v !== '' && strcasecmp($v, 'success') !== 0) {
                return mb_substr($v, 0, 300);
            }
        }
        return $fallback !== '' ? $fallback : 'Unexpected gateway response.';
    }

    /** @return array{ok:bool,code:int,json:array,body:string,error:string} */
    private static function http(string $method, string $url, ?array $payload, array $headers = [], int $timeout = 30, bool $form = false): array
    {
        try {
            $req = Http::timeout($timeout)->connectTimeout(15)->withHeaders($headers)->acceptJson();
            if ($method === 'GET') {
                $res = $req->get($url);
            } elseif ($form) {
                $res = $req->asForm()->post($url, $payload ?? []);
            } else {
                $res = $req->withBody($payload === null ? '' : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'application/json')->post($url);
            }
            $json = json_decode($res->body(), true);
            return ['ok' => $res->successful(), 'code' => $res->status(), 'json' => is_array($json) ? $json : [], 'body' => $res->body(), 'error' => ''];
        } catch (Throwable $e) {
            return ['ok' => false, 'code' => 0, 'json' => [], 'body' => '', 'error' => $e->getMessage()];
        }
    }

    public static function demoRef(string $ref): bool
    {
        return str_starts_with(strtoupper(trim($ref)), 'DEMO');
    }

    public static function newDemoRef(): string
    {
        return 'DEMO' . date('ymdHis') . strtoupper(bin2hex(random_bytes(3)));
    }

    private static function demoResult(array $order, string $method): array
    {
        $url = self::url('payment/demo.php?tran_id=' . urlencode((string) $order['order_number'])
            . '&amount=' . urlencode(number_format((float) $order['total'], 2, '.', ''))
            . '&method=' . urlencode($method));
        return ['ok' => true, 'gateway_url' => $url, 'demo' => true, 'method' => $method, 'ref' => self::newDemoRef()];
    }

    /* ----------------------------- SSLCommerz ---------------------------- */
    private static function sslczLive(): bool
    {
        return PaymentConfig::configured('sslcommerz');
    }

    public static function sslczSessionParams(array $order, array $customer): array
    {
        $tranId = (string) $order['order_number'];
        $district = (string) ($customer['area'] ?? '');
        if ($district === '') {
            $district = 'Dhaka';
        }
        $division = Districts::divisionOf($district);
        $cfg = PaymentConfig::gateway('sslcommerz');
        $first = $order['items'][0]['product_name'] ?? 'bHerb order';

        $params = [
            'store_id' => (string) ($cfg['store_id'] ?? ''),
            'store_passwd' => (string) ($cfg['store_pass'] ?? ''),
            'total_amount' => number_format((float) $order['total'], 2, '.', ''),
            'currency' => 'BDT',
            'tran_id' => $tranId,
            'success_url' => self::url('payment/success.php?tran_id=' . urlencode($tranId)),
            'fail_url' => self::url('payment/fail.php?tran_id=' . urlencode($tranId)),
            'cancel_url' => self::url('payment/cancel.php?tran_id=' . urlencode($tranId)),
            'ipn_url' => self::url('payment/ipn.php'),
            'shipping_method' => ((float) $order['shipping'] > 0 ? 'Courier' : 'NO'),
            'product_name' => mb_substr((string) $first, 0, 250),
            'product_category' => 'Supplements',
            'product_profile' => 'general',
            'cus_name' => mb_substr((string) $customer['name'], 0, 60),
            'cus_email' => mb_substr((string) (($customer['email'] ?? '') ?: 'customer@bherb.example'), 0, 60),
            'cus_add1' => mb_substr((string) $customer['address'], 0, 100),
            'cus_add2' => mb_substr($district, 0, 100),
            'cus_city' => mb_substr($district, 0, 50),
            'cus_state' => mb_substr($division, 0, 50),
            'cus_postcode' => self::SSLCZ_DEFAULT_POSTCODE,
            'cus_country' => 'Bangladesh',
            'cus_phone' => mb_substr((string) $customer['phone'], 0, 20),
            'ship_name' => mb_substr((string) $customer['name'], 0, 60),
            'ship_add1' => mb_substr((string) $customer['address'], 0, 100),
            'ship_add2' => mb_substr($district, 0, 100),
            'ship_city' => mb_substr($district, 0, 50),
            'ship_state' => mb_substr($division, 0, 50),
            'ship_postcode' => self::SSLCZ_DEFAULT_POSTCODE,
            'ship_country' => 'Bangladesh',
            'value_a' => $tranId,
            'value_b' => 'bkash',
            'value_c' => 'nagad',
            'value_d' => PaymentConfig::env(),
        ];
        $cart = [];
        foreach (array_slice($order['items'] ?? [], 0, 5, true) as $it) {
            $cart[] = [
                'product' => mb_substr((string) $it['product_name'], 0, 120),
                'amount' => number_format((float) $it['price'], 2, '.', ''),
                'qty' => (int) $it['qty'],
            ];
        }
        if ($cart) {
            $params['cart'] = json_encode($cart);
        }
        return $params;
    }

    public static function sslczEmbedPostdata(array $order, array $customer): array
    {
        return self::sslczSessionParams($order, $customer);
    }

    private static function sslczInit(array $order, array $customer): array
    {
        if (! self::sslczLive()) {
            return self::demoResult($order, 'sslcommerz');
        }
        $res = self::http('POST', PaymentConfig::base('sslcommerz') . self::SSLCZ_SESSION_PATH, self::sslczSessionParams($order, $customer), [], 30, true);
        if ($res['body'] === '' && $res['error'] !== '') {
            return ['ok' => false, 'error' => 'Gateway unreachable: ' . $res['error']];
        }
        $json = $res['json'];
        if (($json['status'] ?? '') !== 'success' || empty($json['data']['GatewayPageURL'])) {
            return ['ok' => false, 'error' => self::errorMessage($json, 'Gateway session failed')];
        }
        return ['ok' => true, 'gateway_url' => (string) $json['data']['GatewayPageURL'], 'demo' => false,
            'method' => 'sslcommerz', 'ref' => (string) ($json['sessionkey'] ?? '')];
    }

    private static function sslczValidate(string $valId): array
    {
        if (! self::sslczLive()) {
            return ['valid' => self::demoRef($valId), 'demo' => true];
        }
        $cfg = PaymentConfig::gateway('sslcommerz');
        $url = PaymentConfig::base('sslcommerz') . self::SSLCZ_VALIDATE_PATH . '?val_id=' . urlencode($valId)
            . '&store_id=' . urlencode((string) $cfg['store_id']) . '&store_passwd=' . urlencode((string) $cfg['store_pass']) . '&format=json';
        $json = self::http('GET', $url, null, [], 20)['json'];
        if (($json['status'] ?? '') !== 'VALID') {
            return ['valid' => false, 'demo' => false, 'raw' => $json];
        }
        return ['valid' => true, 'demo' => false, 'tran_id' => (string) ($json['tran_id'] ?? ''),
            'bank_tran_id' => (string) ($json['bank_tran_id'] ?? ''), 'card_type' => (string) ($json['card_type'] ?? ''),
            'amount' => (string) ($json['amount'] ?? ''), 'currency_type' => (string) ($json['currency_type'] ?? '')];
    }

    /* -------------------------------- bKash ------------------------------ */
    private static function bkashToken(): array
    {
        $cfg = PaymentConfig::gateway('bkash');
        $cached = session('bherb_bkash_token');
        if (is_array($cached) && ($cached['app_key'] ?? '') === (string) $cfg['app_key']
            && ($cached['expires'] ?? 0) > time() && ! empty($cached['token'])) {
            return ['ok' => true, 'token' => (string) $cached['token']];
        }
        $res = self::http('POST', PaymentConfig::base('bkash') . self::BKASH_GRANT_PATH,
            ['app_key' => (string) $cfg['app_key'], 'app_secret' => (string) $cfg['app_secret']],
            ['Authorization' => 'Basic ' . base64_encode($cfg['username'] . ':' . $cfg['password'])]);
        $token = (string) ($res['json']['id_token'] ?? $res['json']['token'] ?? '');
        if ($token === '') {
            return ['ok' => false, 'error' => 'bKash token grant failed: '
                . self::errorMessage($res['json'], $res['error'] !== '' ? $res['error'] : 'HTTP ' . $res['code'])];
        }
        session(['bherb_bkash_token' => ['token' => $token, 'app_key' => (string) $cfg['app_key'], 'expires' => time() + 3000]]);
        return ['ok' => true, 'token' => $token];
    }

    private static function bkashHeaders(array $cfg, string $token): array
    {
        return ['Authorization' => $token, 'X-App-Key' => (string) $cfg['app_key']];
    }

    private static function bkashCall(string $path, array $payload): array
    {
        $cfg = PaymentConfig::gateway('bkash');
        $token = self::bkashToken();
        if (! $token['ok']) {
            return $token;
        }
        return self::http('POST', PaymentConfig::base('bkash') . $path, $payload, self::bkashHeaders($cfg, $token['token']));
    }

    private static function bkashInit(array $order, array $customer): array
    {
        if (! PaymentConfig::configured('bkash')) {
            return self::demoResult($order, 'bkash');
        }
        $cfg = PaymentConfig::gateway('bkash');
        $token = self::bkashToken();
        if (! $token['ok']) {
            return ['ok' => false, 'error' => $token['error']];
        }
        $phone = preg_replace('/[^0-9]/', '', (string) ($customer['phone'] ?? ''));
        $res = self::http('POST', PaymentConfig::base('bkash') . self::BKASH_CREATE_PATH, [
            'mode' => '0001',
            'payerReference' => $phone !== '' ? mb_substr($phone, 0, 20) : (string) $order['order_number'],
            'callbackURL' => self::url('payment/success.php'),
            'amount' => number_format((float) $order['total'], 2, '.', ''),
            'currency' => 'BDT',
            'intent' => 'sale',
            'merchantInvoiceNumber' => (string) $order['order_number'],
        ], self::bkashHeaders($cfg, $token['token']));
        $paymentId = (string) ($res['json']['paymentID'] ?? '');
        $url = (string) ($res['json']['bkashURL'] ?? $res['json']['bKashURL'] ?? '');
        if ($paymentId === '' || $url === '') {
            return ['ok' => false, 'error' => 'bKash payment creation failed: '
                . self::errorMessage($res['json'], $res['error'] !== '' ? $res['error'] : 'HTTP ' . $res['code'])];
        }
        return ['ok' => true, 'gateway_url' => $url, 'demo' => false, 'method' => 'bkash', 'ref' => $paymentId];
    }

    private static function bkashVerify(string $paymentID): array
    {
        $out = ['ok' => false, 'pending' => false, 'status' => '', 'trx' => '', 'amount' => null, 'msisdn' => '', 'error' => ''];
        if ($paymentID === '') {
            $out['error'] = 'Missing bKash paymentID.';
            return $out;
        }
        $ex = self::bkashCall(self::BKASH_EXECUTE_PATH, ['paymentID' => $paymentID]);
        $json = $ex['json'] ?? [];
        if (empty($json['transactionStatus'])) {
            $q = self::bkashCall(self::BKASH_QUERY_PATH, ['paymentID' => $paymentID]);
            if (! isset($q['json'])) {
                $out['error'] = 'bKash verification failed: ' . (string) ($q['error'] ?? 'no response');
                return $out;
            }
            $json = $q['json'];
        }
        $status = (string) ($json['transactionStatus'] ?? '');
        $out['status'] = $status;
        $out['trx'] = (string) ($json['trxID'] ?? '');
        $out['msisdn'] = (string) ($json['customerMsisdn'] ?? '');
        $out['amount'] = is_numeric($json['amount'] ?? null) ? (float) $json['amount'] : null;
        if ($status === 'Completed') {
            $out['ok'] = true;
        } elseif ($status === 'Pending') {
            $out['pending'] = true;
        } else {
            $out['error'] = 'bKash payment ' . ($status !== '' ? $status : 'not completed') . '.';
        }
        return $out;
    }

    /* -------------------------------- Nagad ------------------------------ */
    private static function nagadKeyPem(string $material, bool $public): string
    {
        $material = trim($material);
        if ($material === '') {
            return '';
        }
        if (str_contains($material, '-----BEGIN')) {
            return $material;
        }
        if (is_file($material)) {
            $c = trim((string) file_get_contents($material));
            return $c !== '' ? $c : $material;
        }
        $body = preg_replace('/\s+/', '', $material);
        if ($body !== null && preg_match('#^[A-Za-z0-9+/=]+$#', $body)) {
            $label = $public ? 'PUBLIC KEY' : 'RSA PRIVATE KEY';
            return "-----BEGIN {$label}-----\n" . chunk_split($body, 64, "\n") . "-----END {$label}-----";
        }
        return $material;
    }

    private static function nagadEncrypt(string $plain): string
    {
        $pem = self::nagadKeyPem((string) (PaymentConfig::gateway('nagad')['pg_public_key'] ?? ''), true);
        $key = $pem !== '' ? @openssl_pkey_get_public($pem) : false;
        if ($key === false || ! openssl_public_encrypt($plain, $cipher, $key)) {
            Log::warning('[nagad] public key encryption failed');
            return '';
        }
        return base64_encode($cipher);
    }

    private static function nagadSign(string $plain): string
    {
        $pem = self::nagadKeyPem((string) (PaymentConfig::gateway('nagad')['merchant_private_key'] ?? ''), false);
        $key = $pem !== '' ? @openssl_pkey_get_private($pem) : false;
        if ($key === false || ! openssl_sign($plain, $signature, $key, OPENSSL_ALGO_SHA256)) {
            Log::warning('[nagad] request signing failed — check the merchant private key');
            return '';
        }
        return base64_encode($signature);
    }

    private static function nagadDecrypt(string $cipher): string
    {
        $pem = self::nagadKeyPem((string) (PaymentConfig::gateway('nagad')['merchant_private_key'] ?? ''), false);
        $key = $pem !== '' ? @openssl_pkey_get_private($pem) : false;
        if ($key === false || ! openssl_private_decrypt((string) base64_decode($cipher, true), $plain, $key)) {
            return '';
        }
        return (string) $plain;
    }

    private static function nagadHeaders(): array
    {
        return ['X-KM-Api-Version' => 'v-0.2.0', 'X-KM-IP-V4' => self::clientIp(), 'X-KM-Client-Type' => 'PC_WEB'];
    }

    private static function nagadInit(array $order, array $customer): array
    {
        if (! PaymentConfig::configured('nagad')) {
            return self::demoResult($order, 'nagad');
        }
        $cfg = PaymentConfig::gateway('nagad');
        $base = PaymentConfig::base('nagad');
        $orderId = (string) $order['order_number'];
        $now = date('YmdHis');

        $sensitive = json_encode(['merchantId' => (string) $cfg['merchant_id'], 'datetime' => $now, 'orderId' => $orderId,
            'challenge' => 'you' . strtoupper(bin2hex(random_bytes(20))) . 'me']);
        $enc = self::nagadEncrypt($sensitive);
        $sig = self::nagadSign($sensitive);
        if ($enc === '' || $sig === '') {
            return ['ok' => false, 'error' => 'Nagad RSA keys are missing or invalid — check the merchant private key and PG public key.'];
        }
        $init = self::http('POST', $base . self::NAGAD_INIT_PATH . rawurlencode((string) $cfg['merchant_id']) . '/' . rawurlencode($orderId), [
            'accountNumber' => (string) (($cfg['account_number'] ?? '') ?: $cfg['merchant_id']),
            'dateTime' => $now, 'sensitiveData' => $enc, 'signature' => $sig,
        ], self::nagadHeaders());
        if (! empty($init['json']['error']) || ! empty($init['json']['reason'])) {
            return ['ok' => false, 'error' => 'Nagad initialize failed: ' . self::errorMessage($init['json'])];
        }
        $meta = json_decode(self::nagadDecrypt((string) ($init['json']['sensitiveData'] ?? '')), true) ?: [];
        $reference = (string) ($meta['paymentReferenceId'] ?? '');
        $challenge = (string) ($meta['challenge'] ?? '');
        if ($reference === '') {
            return ['ok' => false, 'error' => 'Nagad initialize failed: '
                . self::errorMessage($init['json'], $init['error'] !== '' ? $init['error'] : 'no payment reference (HTTP ' . $init['code'] . ')')];
        }
        $orderSensitive = json_encode(['merchantId' => (string) $cfg['merchant_id'], 'orderId' => $orderId,
            'currencyCode' => (string) ($cfg['currency_code'] ?? '050'),
            'amount' => number_format((float) $order['total'], 2, '.', ''), 'challenge' => $challenge]);
        $complete = self::http('POST', $base . self::NAGAD_COMPLETE_PATH . rawurlencode($reference), [
            'sensitiveData' => self::nagadEncrypt($orderSensitive), 'signature' => self::nagadSign($orderSensitive),
            'merchantCallbackURL' => self::url('payment/success.php'),
        ], self::nagadHeaders());
        $gatewayUrl = (string) ($complete['json']['callBackUrl'] ?? '');
        if (($complete['json']['status'] ?? '') !== 'Success' || $gatewayUrl === '') {
            return ['ok' => false, 'error' => 'Nagad checkout failed: '
                . self::errorMessage($complete['json'], $complete['error'] !== '' ? $complete['error'] : 'HTTP ' . $complete['code'])];
        }
        return ['ok' => true, 'gateway_url' => $gatewayUrl, 'ref' => $reference, 'demo' => false, 'method' => 'nagad'];
    }

    private static function nagadVerify(string $ref): array
    {
        $out = ['reached' => false, 'status' => '', 'trx' => '', 'amount' => null, 'order_id' => '', 'error' => ''];
        if ($ref === '') {
            $out['error'] = 'Missing Nagad payment reference.';
            return $out;
        }
        $res = self::http('GET', PaymentConfig::base('nagad') . self::NAGAD_VERIFY_PATH . rawurlencode($ref), null, self::nagadHeaders(), 20);
        if (! $res['ok'] && ! $res['json']) {
            $out['error'] = 'Nagad verification unreachable: ' . ($res['error'] !== '' ? $res['error'] : 'HTTP ' . $res['code']);
            return $out;
        }
        $json = $res['json'];
        $out['reached'] = true;
        $out['status'] = (string) ($json['transactionStatus'] ?? $json['status'] ?? '');
        $out['trx'] = (string) ($json['issuerPaymentRefNo'] ?? $json['issuerPaymentRef'] ?? '');
        $out['order_id'] = (string) ($json['orderId'] ?? '');
        $out['amount'] = is_numeric($json['amount'] ?? null) ? (float) $json['amount'] : null;
        $out['error'] = (string) ($json['message'] ?? $json['reason'] ?? '');
        return $out;
    }

    /* ---------------------- routing + callback pipeline ------------------- */
    /** @return array{ok:bool, gateway_url?:string, demo?:bool, method?:string, ref?:string, error?:string} */
    public static function initiate(string $method, array $order, array $customer): array
    {
        switch (PaymentConfig::normalize($method)) {
            case 'sslcommerz':
                return self::sslczInit($order, $customer);
            case 'bkash':
                return self::bkashInit($order, $customer);
            case 'nagad':
                return self::nagadInit($order, $customer);
            case 'cod':
                return ['ok' => true, 'gateway_url' => self::url('order-success'), 'demo' => false, 'method' => 'cod', 'ref' => ''];
            default:
                return ['ok' => false, 'error' => 'Unsupported payment method.'];
        }
    }

    public static function guessGateway(array $request): string
    {
        $explicit = strtolower(trim((string) ($request['gateway'] ?? '')));
        if (in_array($explicit, ['sslcommerz', 'bkash', 'nagad', 'cod'], true)) {
            return $explicit;
        }
        if (isset($request['paymentID'])) return 'bkash';
        if (isset($request['payment_ref_id']) || isset($request['merchant'])) return 'nagad';
        if (isset($request['val_id']) || isset($request['tran_id'])) return 'sslcommerz';
        if (isset($request['order_id'])) return 'nagad';
        return 'sslcommerz';
    }

    public static function resolveOrder(array $request): ?array
    {
        foreach (['tran_id', 'merchantInvoiceNumber', 'order_id', 'order_number'] as $key) {
            $v = trim((string) ($request[$key] ?? ''));
            if ($v !== '' && ($o = OrderService::byNumber($v))) {
                return $o;
            }
        }
        foreach (['paymentID', 'payment_ref_id'] as $key) {
            $v = trim((string) ($request[$key] ?? ''));
            if ($v !== '' && ($o = OrderService::byGatewayRef($v))) {
                return $o;
            }
        }
        return null;
    }

    public static function orderTx(array $order): array
    {
        return ['ref' => (string) ($order['val_id'] ?? ''), 'trx' => (string) ($order['bank_tran_id'] ?? ''),
            'instrument' => (string) ($order['card_type'] ?? ''), 'demo' => (bool) ($order['demo_payment'] ?? false),
            'amount' => isset($order['total']) ? (float) $order['total'] : null];
    }

    private static function amountOk(?array $order, ?float $amount): bool
    {
        return $order === null || $amount === null || abs($amount - (float) $order['total']) <= 1.0;
    }

    private static function fail(string $reason, bool $cancelled = false): array
    {
        return ['ok' => false, 'pending' => false, 'cancelled' => $cancelled, 'demo' => false,
            'ref' => '', 'trx' => '', 'instrument' => '', 'amount' => null, 'reason' => $reason];
    }

    private static function good(string $ref, string $trx, string $instrument, ?float $amount, bool $demo = false): array
    {
        return ['ok' => true, 'pending' => false, 'cancelled' => false, 'demo' => $demo,
            'ref' => $ref, 'trx' => $trx, 'instrument' => $instrument, 'amount' => $amount, 'reason' => ''];
    }

    public static function verify(string $method, array $request, ?array $order): array
    {
        switch ($method) {
            case 'sslcommerz':
                $valId = trim((string) ($request['val_id'] ?? ''));
                if ($valId === '') return self::fail('Missing SSLCommerz validation id.');
                $check = self::sslczValidate($valId);
                if (! $check['valid']) return self::fail('Gateway validation failed for val_id ' . $valId);
                if (! empty($check['demo'])) {
                    if (PaymentConfig::env() !== 'demo') return self::fail('Rejected demo transaction reference.');
                    return self::good($valId, (string) ($request['bank_tran_id'] ?? ''), (string) ($request['card_type'] ?? 'DEMO'), null, true);
                }
                if ($order !== null) {
                    $t = (string) ($check['tran_id'] ?? '');
                    $a = (string) ($check['amount'] ?? '');
                    if ($t !== '' && $t !== (string) $order['order_number']) {
                        return self::fail('Transaction ' . $t . ' does not belong to order ' . $order['order_number']);
                    }
                    if ($a !== '' && ! self::amountOk($order, is_numeric($a) ? (float) $a : null)) {
                        return self::fail('Paid amount does not match the order total.');
                    }
                }
                return self::good($valId, (string) (($request['bank_tran_id'] ?? '') ?: ($check['bank_tran_id'] ?? '')),
                    (string) (($request['card_type'] ?? '') ?: ($check['card_type'] ?? '')),
                    isset($check['amount']) && is_numeric($check['amount']) ? (float) $check['amount'] : null);

            case 'bkash':
                $cb = strtolower(trim((string) ($request['status'] ?? 'success')));
                if (in_array($cb, ['cancelled', 'canceled'], true)) return self::fail('Customer cancelled the bKash payment.', true);
                if (in_array($cb, ['failure', 'failed'], true)) return self::fail('bKash reported a failed payment.');
                $pid = trim((string) ($request['paymentID'] ?? ''));
                if ($pid === '') return self::fail('Missing bKash paymentID.');
                if (self::demoRef($pid)) {
                    if (PaymentConfig::env() !== 'demo') return self::fail('Rejected demo transaction reference.');
                    return self::good($pid, (string) ($request['trxID'] ?? ''), 'bKash', null, true);
                }
                $v = self::bkashVerify($pid);
                if ($v['ok']) {
                    if (! self::amountOk($order, $v['amount'])) return self::fail('Paid amount does not match the order total.');
                    return self::good($pid, $v['trx'], 'bKash', $v['amount']);
                }
                if ($v['pending']) {
                    return ['ok' => false, 'pending' => true, 'cancelled' => false, 'demo' => false, 'ref' => $pid,
                        'trx' => $v['trx'], 'instrument' => 'bKash', 'amount' => $v['amount'], 'reason' => 'bKash payment is pending confirmation.'];
                }
                return self::fail($v['error'] !== '' ? $v['error'] : 'bKash verification failed.');

            case 'nagad':
                $cb = trim((string) ($request['status'] ?? ''));
                $code = trim((string) ($request['status_code'] ?? ''));
                if ($cb !== '' && strcasecmp($cb, 'Success') !== 0) {
                    $cancelled = stripos($cb, 'abort') !== false || stripos($cb, 'cancel') !== false;
                    return self::fail('Nagad reported status "' . $cb . '".', $cancelled);
                }
                $ref = trim((string) ($request['payment_ref_id'] ?? ''));
                if ($ref === '') return self::fail('Missing Nagad payment reference.');
                if (self::demoRef($ref)) {
                    if (PaymentConfig::env() !== 'demo') return self::fail('Rejected demo transaction reference.');
                    return self::good($ref, (string) ($request['issuer_payment_ref'] ?? ''), 'Nagad', null, true);
                }
                $v = self::nagadVerify($ref);
                if ($v['reached']) {
                    $st = trim($v['status']);
                    if ($st !== '' && strcasecmp($st, 'Success') !== 0) return self::fail('Nagad verification returned status "' . $st . '".');
                    if ($st === '' && strcasecmp($cb, 'Success') !== 0) return self::fail('Nagad transaction could not be verified.');
                    if (! self::amountOk($order, $v['amount'])) return self::fail('Paid amount does not match the order total.');
                    return self::good($ref, $v['trx'] !== '' ? $v['trx'] : (string) ($request['issuer_payment_ref'] ?? ''), 'Nagad', $v['amount']);
                }
                $looksReal = strcasecmp($cb, 'Success') === 0 && $code === '00_0000_000' && strlen($ref) >= 20;
                if (! $looksReal) {
                    return self::fail($v['error'] !== '' ? $v['error'] : 'Nagad transaction could not be verified.');
                }
                Log::warning('[nagad] paid without verification (verify API unreachable) ref=' . $ref);
                return self::good($ref, (string) ($request['issuer_payment_ref'] ?? ''), 'Nagad', null);

            default:
                return self::fail('No online verification for cash on delivery.');
        }
    }

    /**
     * Resolve + settle one payment callback ($force: auto|failed|cancelled).
     * @return array{status:string, method:string, order:?array, order_number:string, tx:array, reason:string, already:bool}
     */
    public static function finalize(array $request, ?string $method = null, string $force = 'auto'): array
    {
        $method = $method !== null ? PaymentConfig::normalize($method) : self::guessGateway($request);
        $base = ['method' => $method, 'tx' => [], 'reason' => '', 'already' => false];

        $order = self::resolveOrder($request);
        if (! $order) {
            return ['status' => 'unknown', 'order' => null, 'order_number' => ''] + $base
                + ['reason' => 'No matching order was found for this payment callback.'];
        }
        $number = (string) $order['order_number'];

        if ((string) $order['payment_status'] === 'paid') {
            $closed = in_array((string) ($order['status'] ?? ''), ['cancelled', 'returned'], true);
            return ['status' => 'paid', 'order' => $order, 'order_number' => $number, 'already' => true,
                'tx' => self::orderTx($order),
                'reason' => $closed ? 'Payment was recorded, but the order is closed and requires administrator refund review.' : '',
                'method' => $method];
        }
        if (in_array($force, ['failed', 'cancelled'], true)) {
            $reason = ($force === 'cancelled' ? 'Customer cancelled at ' : 'Payment failed at ') . PaymentConfig::label($method);
            $saved = OrderService::markFailed($number, $reason);
            $settledOrder = OrderService::byNumber($number);
            if ($settledOrder && (string) $settledOrder['payment_status'] === 'paid') {
                return ['status' => 'paid', 'order' => $settledOrder, 'order_number' => $number,
                    'tx' => self::orderTx($settledOrder), 'reason' => '', 'already' => true, 'method' => $method];
            }
            if (! $saved || ! $settledOrder || (string) $settledOrder['payment_status'] !== 'failed') {
                return ['status' => 'failed', 'order' => $settledOrder, 'order_number' => $number,
                    'tx' => [], 'reason' => 'The order state changed before this payment failure could be applied.',
                    'already' => false, 'method' => $method];
            }
            return ['status' => $force, 'order' => $settledOrder, 'order_number' => $number,
                'tx' => [], 'reason' => $reason, 'already' => false, 'method' => $method];
        }

        $v = self::verify($method, $request, $order);
        $tx = ['ref' => $v['ref'], 'trx' => $v['trx'], 'instrument' => $v['instrument'], 'demo' => $v['demo'], 'amount' => $v['amount']];

        if ($v['ok']) {
            $saved = OrderService::markPaid($number, ['val_id' => $v['ref'], 'bank_tran_id' => $v['trx'], 'card_type' => $v['instrument'],
                'demo' => $v['demo'], 'payment_method' => $method === 'cod' ? '' : $method]);
            $settledOrder = OrderService::byNumber($number);
            if (! $saved || ! $settledOrder || (string) $settledOrder['payment_status'] !== 'paid') {
                return ['status' => 'failed', 'order' => $settledOrder, 'order_number' => $number,
                    'tx' => $tx, 'reason' => 'Payment verification succeeded, but the order could not be safely settled.',
                    'already' => false, 'method' => $method];
            }
            $closed = in_array((string) $settledOrder['status'], ['cancelled', 'returned'], true);
            return ['status' => 'paid', 'order' => $settledOrder, 'order_number' => $number,
                'tx' => $tx,
                'reason' => $closed ? 'Payment was recorded, but the order is closed and requires administrator refund review.' : '',
                'already' => false, 'method' => $method];
        }
        if ($v['pending']) {
            $saved = OrderService::markPending($number, ['val_id' => $v['ref'], 'bank_tran_id' => $v['trx'], 'card_type' => $v['instrument'],
                'payment_method' => $method === 'cod' ? '' : $method]);
            $settledOrder = OrderService::byNumber($number);
            if ($settledOrder && (string) $settledOrder['payment_status'] === 'paid') {
                return ['status' => 'paid', 'order' => $settledOrder, 'order_number' => $number,
                    'tx' => self::orderTx($settledOrder), 'reason' => '', 'already' => true, 'method' => $method];
            }
            if (! $saved || ! $settledOrder || (string) $settledOrder['payment_status'] !== 'pending') {
                return ['status' => 'failed', 'order' => $settledOrder, 'order_number' => $number,
                    'tx' => $tx, 'reason' => 'The order is no longer open for pending payment.',
                    'already' => false, 'method' => $method];
            }
            return ['status' => 'pending', 'order' => $settledOrder, 'order_number' => $number,
                'tx' => $tx, 'reason' => $v['reason'], 'already' => false, 'method' => $method];
        }
        $failureReason = $v['reason'] !== '' ? $v['reason'] : 'Payment verification failed';
        $saved = OrderService::markFailed($number, $failureReason);
        $settledOrder = OrderService::byNumber($number);
        if ($settledOrder && (string) $settledOrder['payment_status'] === 'paid') {
            return ['status' => 'paid', 'order' => $settledOrder, 'order_number' => $number,
                'tx' => self::orderTx($settledOrder), 'reason' => '', 'already' => true, 'method' => $method];
        }
        if (! $saved || ! $settledOrder || (string) $settledOrder['payment_status'] !== 'failed') {
            return ['status' => 'failed', 'order' => $settledOrder, 'order_number' => $number,
                'tx' => [], 'reason' => 'The order state changed before payment failure could be applied.',
                'already' => false, 'method' => $method];
        }
        return ['status' => ! empty($v['cancelled']) ? 'cancelled' : 'failed', 'order' => $settledOrder,
            'order_number' => $number, 'tx' => [], 'reason' => $failureReason, 'already' => false, 'method' => $method];
    }

    /** Session side effects + the URL to land on after a gateway callback. */
    public static function redirectTarget(array $result): string
    {
        $status = (string) ($result['status'] ?? 'failed');
        $number = (string) ($result['order_number'] ?? '');
        $order = $result['order'] ?? null;

        session(['pay_result' => $status]);
        if (in_array($status, ['paid', 'pending'], true) && is_array($order)) {
            session()->forget(['checkout_quote', 'pay_order']);
            session(['cart' => [], 'last_order' => [
                'db_id' => (int) $order['id'], 'id' => $number, 'order_number' => $number,
                'name' => (string) $order['shipping_name'], 'phone' => (string) $order['shipping_phone'],
                'address' => (string) $order['shipping_address'], 'total' => (float) $order['total'],
                'items' => [], 'payment_status' => $status,
            ]]);
        } elseif ((string) (session('last_order.order_number') ?? '') === $number && $number !== '') {
            session(['last_order.payment_status' => $status]);
        }

        return match ($status) {
            'paid' => self::url('order-success?order=' . urlencode($number)),
            'pending' => self::url('order-success?order=' . urlencode($number) . '&pay=pending'),
            'cancelled' => self::url('checkout?pay=cancelled' . ($number !== '' ? '&order=' . urlencode($number) : '')),
            'failed' => self::url('checkout?pay=fail' . ($number !== '' ? '&order=' . urlencode($number) : '')),
            default => self::url('checkout'),
        };
    }
}

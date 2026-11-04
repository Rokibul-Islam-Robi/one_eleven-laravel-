<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentConfig;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;

/** Gateway return handlers: payment/success|fail|cancel|ipn|demo (original payment/*.php). */
class PaymentController extends Controller
{
    private function request(Request $r): array
    {
        return array_merge($r->query(), $r->post());
    }

    /** HTML that breaks out of a gateway popup/frame, as the original payment_redirect(). */
    private function breakOut(array $result)
    {
        $target = PaymentService::redirectTarget($result);
        $html = '<!doctype html><meta charset="utf-8"><title>Returning…</title>'
            . '<script>window.top.location.href=' . json_encode($target) . ';</script>'
            . '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($target, ENT_QUOTES) . '"></noscript>';
        return response($html)->header('Content-Type', 'text/html; charset=utf-8');
    }

    public function success(Request $r)
    {
        $req = $this->request($r);
        $result = PaymentService::finalize($req, PaymentService::guessGateway($req));
        if ($result['status'] === 'unknown') {
            return $this->breakOut(['status' => 'failed', 'order' => null,
                'order_number' => (string) ($req['tran_id'] ?? $req['merchantInvoiceNumber'] ?? '')]);
        }
        return $this->breakOut($result);
    }

    public function fail(Request $r)
    {
        $req = $this->request($r);
        return $this->breakOut(PaymentService::finalize($req, PaymentService::guessGateway($req), 'failed'));
    }

    public function cancel(Request $r)
    {
        $req = $this->request($r);
        return $this->breakOut(PaymentService::finalize($req, PaymentService::guessGateway($req), 'cancelled'));
    }

    public function ipn(Request $r)
    {
        $req = $this->request($r);
        if (empty($req['tran_id']) && empty($req['val_id']) && empty($req['paymentID']) && empty($req['payment_ref_id'])) {
            return response('Bad request', 400);
        }
        $order = PaymentService::resolveOrder($req);
        if (! $order) {
            return response('Unknown order', 404);
        }
        if ((string) $order['payment_status'] === 'paid') {
            return response('OK');
        }
        $result = PaymentService::finalize($req, PaymentService::guessGateway($req));
        if (in_array($result['status'], ['paid', 'pending'], true)) {
            return response('OK');
        }
        return response($result['status'] === 'unknown' ? 'Unknown order'
            : ($result['reason'] !== '' ? $result['reason'] : 'Validation failed'), 422);
    }

    public function demo(Request $r)
    {
        $tranId = (string) $r->query('tran_id', '');
        $amount = (string) $r->query('amount', '0.00');
        $method = PaymentConfig::normalize((string) $r->query('method', 'sslcommerz'));
        if ($method === 'cod') {
            $method = 'sslcommerz';
        }
        if ($tranId === '') {
            return response('Missing tran_id', 400);
        }
        $demoRef = PaymentService::newDemoRef();
        $successArgs = match ($method) {
            'bkash' => 'status=success&paymentID=' . urlencode($demoRef) . '&merchantInvoiceNumber=' . urlencode($tranId)
                . '&trxID=DEMO-TRX-' . date('ymdHis') . '&amount=' . urlencode($amount) . '&currency=BDT',
            'nagad' => 'status=Success&status_code=00_0000_000&order_id=' . urlencode($tranId)
                . '&payment_ref_id=' . urlencode($demoRef) . '&issuer_payment_ref=' . urlencode('DEMO-NGD-' . date('ymdHis'))
                . '&message=' . urlencode('Successful Transaction') . '&payment_dt=' . date('YmdHis') . '&merchant=bHerbDemo',
            default => 'tran_id=' . urlencode($tranId) . '&val_id=' . urlencode($demoRef)
                . '&card_type=VISA&bank_tran_id=DEMO-BANK-' . date('ymdHis'),
        };
        return view('storefront.payment.demo', [
            'tranId' => $tranId, 'amount' => $amount, 'label' => PaymentConfig::label($method),
            'success' => PaymentService::url('payment/success.php?' . $successArgs),
            'fail' => PaymentService::url('payment/fail.php?tran_id=' . urlencode($tranId) . '&gateway=' . urlencode($method)),
            'cancel' => PaymentService::url('payment/cancel.php?tran_id=' . urlencode($tranId) . '&gateway=' . urlencode($method)),
        ]);
    }
}

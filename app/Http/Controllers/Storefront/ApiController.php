<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Support\Cart;
use App\Support\Catalog;
use App\Support\CustomerAuth;
use App\Support\Render;
use Illuminate\Http\Request;

/** JSON endpoints: api/cart.php, api/search.php, api/auth.php (ported 1:1). */
class ApiController extends Controller
{
    private function json(array $data, int $status = 200)
    {
        return response()->json($data, $status, ['Cache-Control' => 'no-store'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function cart(Request $request)
    {
        $action = (string) ($request->input('action') ?? $request->query('action', 'status'));
        if ($request->isMethod('get')) {
            if ($action !== 'status') {
                return $this->json(['ok' => false, 'error' => 'Cart updates require POST'], 405);
            }

            return $this->json(Render::cartPayload());
        }
        if (! in_array($action, ['add', 'qty', 'remove', 'wish', 'clear'], true)) {
            return $this->json(['ok' => false, 'error' => 'Unknown action'], 400);
        }
        $id = (string) $request->input('id', '');
        $rawQty = $request->input('qty', 1);
        $parsedQty = filter_var($rawQty, FILTER_VALIDATE_INT);
        if ($parsedQty === false) {
            return $this->json(['ok' => false, 'error' => 'Quantity must be a whole number'], 422);
        }
        $qty = (int) $parsedQty;
        if ($action === 'qty' && ($qty < 1 || $qty > 99)) {
            return $this->json(['ok' => false, 'error' => 'Quantity must be between 1 and 99'], 422);
        }
        $qty = $action === 'add' ? max(1, min(99, $qty)) : max(0, min(99, $qty));
        $variant = trim((string) $request->input('variant', ''));
        if ($variant !== '' && $action === 'add') {
            $id .= '#' . $variant;
        }
        if ($action !== 'clear' && ! Catalog::byId($id)) {
            return $this->json(['ok' => false, 'error' => 'Unknown product'], 422);
        }
        Cart::apply($action, $id, $qty);

        $name = '';
        if ($id !== '' && ($p = Catalog::byId($id))) {
            $name = (string) $p['n'];
        }
        $payload = Render::cartPayload();
        $payload['action'] = $action;
        $payload['product'] = $name;
        if ($action === 'wish') {
            $payload['wished'] = Cart::isWished($id);
            $payload['id'] = $id;
        }
        return $this->json($payload);
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $limit = max(1, min(12, (int) $request->query('limit', 8)));
        $items = [];
        foreach (Catalog::search($q, $limit) as $p) {
            $items[] = [
                'id' => (string) $p['id'], 'name' => (string) $p['n'], 'brand' => (string) $p['b'],
                'price' => (string) $p['p'], 'base' => priceval($p['p']), 'old' => (string) $p['o'],
                'off' => Catalog::discPct($p), 'image' => (string) $p['i'], 'category' => (string) $p['c'],
                'rating' => (float) $p['r'], 'reviews' => (int) $p['rc'], 'url' => 'product/' . $p['id'],
            ];
        }
        return $this->json(['ok' => true, 'q' => $q, 'count' => count($items), 'items' => $items]);
    }

    public function auth(Request $request)
    {
        $action = (string) ($request->input('action') ?? $request->query('action', 'status'));
        if ($request->isMethod('get') && $action === 'status') {
            $u = CustomerAuth::current();
            return $this->json(['ok' => true, 'user' => $u, 'name' => $u ? $u['name'] : '', 'display' => CustomerAuth::displayName($u)]);
        }
        if (! in_array($action, ['login', 'create_account', 'logout'], true)) {
            return $this->json(['ok' => false, 'error' => 'Unknown action'], 400);
        }
        if ($action === 'logout') {
            CustomerAuth::logout();
            return $this->json(['ok' => true, 'user' => null, 'name' => '', 'display' => '', 'action' => 'logout']);
        }
        $res = $action === 'create_account'
            ? CustomerAuth::create((string) $request->input('name', ''), (string) $request->input('contact', ''))
            : CustomerAuth::login((string) $request->input('contact', ''));
        if (! $res['ok']) {
            return $this->json(['ok' => false, 'error' => (string) $res['error'], 'action' => $action], 422);
        }
        $u = $res['user'];
        return $this->json(['ok' => true, 'action' => $action, 'user' => $u, 'name' => $u['name'] ?? '',
            'display' => CustomerAuth::displayName($u)]);
    }
}

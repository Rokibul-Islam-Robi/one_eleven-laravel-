<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\SellerLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SellerOrderController extends Controller
{
    private const TRANSITIONS = [
        'confirmed' => ['packed'],
        'packed' => ['shipped'],
        'shipped' => ['delivered'],
    ];

    public function index(Request $request)
    {
        $seller = $this->seller($request);

        return response()->view('seller.orders.index', [
            'items' => $seller->sellerOrderItems()
                ->with('order')
                ->latest('created_at')
                ->paginate(20),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $item = $this->seller($request)->sellerOrderItems()
            ->with('order')
            ->findOrFail($id);

        return response()->view('seller.orders.show', [
            'item' => $item,
            'nextStatuses' => self::TRANSITIONS[$item->status] ?? [],
            'showDeliveryDetails' => in_array($item->status, ['confirmed', 'packed', 'shipped', 'delivered'], true),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $seller = $this->seller($request);
        $validated = $request->validate([
            'status' => ['required', 'in:packed,shipped,delivered'],
            'tracking_code' => ['nullable', 'string', 'max:80'],
            'seller_note' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($request, $seller, $id, $validated): void {
            $ownedItem = $seller->sellerOrderItems()->whereKey($id)->firstOrFail();
            $order = DB::table('orders')->where('id', $ownedItem->order_id)
                ->lockForUpdate()->first(['id', 'status']);
            abort_unless($order, 404);

            $item = DB::table('seller_order_items')->where('id', $id)
                ->where('seller_id', $seller->id)
                ->lockForUpdate()->first();
            abort_unless($item, 404);

            if (in_array($order->status, ['cancelled', 'returned'], true)
                || ! in_array($validated['status'], self::TRANSITIONS[$item->status] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => 'This order item cannot be moved to that fulfillment status.',
                ]);
            }

            $changes = [
                'status' => $validated['status'],
                'updated_at' => now(),
            ];
            if (array_key_exists('tracking_code', $validated)) {
                $changes['tracking_code'] = trim((string) $validated['tracking_code']) ?: null;
            }
            if (array_key_exists('seller_note', $validated)) {
                $changes['seller_note'] = trim((string) $validated['seller_note']) ?: null;
            }
            if ($validated['status'] === 'delivered') {
                $changes['delivered_at'] = now();
            }

            DB::table('seller_order_items')->where('id', $id)
                ->where('seller_id', $seller->id)
                ->update($changes);

            SellerLog::query()->create([
                'seller_id' => $seller->id,
                'action' => 'seller.order_item.status_updated',
                'detail' => 'Order item #'.$id.': '.$item->status.' -> '.$validated['status'],
                'ip' => $request->ip(),
                'created_at' => now(),
            ]);
        });

        return redirect('/seller/orders/'.$id)->with('status', 'Order item fulfillment status updated.');
    }

    private function seller(Request $request): Seller
    {
        return $request->attributes->get('seller');
    }
}

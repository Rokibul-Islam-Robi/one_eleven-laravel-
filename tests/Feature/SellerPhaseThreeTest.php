<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\Services\Payments\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SellerPhaseThreeTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_creates_pending_seller_order_items_without_commission_values(): void
    {
        $seller = $this->createSeller();
        $product = $this->createSellerProduct($seller, 'Seller-owned item');
        $result = $this->createOrder([(string) $product->json_id => 2]);

        $this->assertTrue($result['ok'], $result['error'] ?? 'Checkout failed.');
        $item = DB::table('seller_order_items')->where('order_id', $result['order_id'])->first();

        $this->assertNotNull($item);
        $this->assertSame((int) $seller->id, (int) $item->seller_id);
        $this->assertSame((int) $product->id, (int) $item->product_id);
        $this->assertSame('Seller-owned item', $item->product_name);
        $this->assertSame(2, (int) $item->qty);
        $this->assertSame('pending', $item->status);
        $this->assertNull($item->commission_rate);
        $this->assertNull($item->commission_amount);
        $this->assertNull($item->seller_earning);

        Auth::guard('seller')->login($seller);
        $this->get('/seller/orders/'.$item->id)->assertOk()
            ->assertDontSee('Test delivery address');
        $this->post('/seller/orders/'.$item->id.'/status', ['status' => 'packed'])
            ->assertSessionHasErrors('status');
        $this->assertSame('pending', DB::table('seller_order_items')->where('id', $item->id)->value('status'));

        $admin = $this->createAdmin();
        $this->withSession([
            'admin_user' => ['id' => $admin->id, 'name' => $admin->name, 'email' => $admin->email, 'role' => 'admin'],
            'admin_login_time' => time(),
            'admin_last_activity' => time(),
        ])->post('/admin/orders/update/'.$result['order_id'], [
            'status' => 'processing',
            'payment_status' => 'unpaid',
            'courier_id' => '',
            'tracking_code' => '',
            'admin_notes' => '',
        ])->assertRedirect();
        $this->assertSame('confirmed', DB::table('seller_order_items')->where('id', $item->id)->value('status'));

        $this->assertTrue(OrderService::markPaid($result['order_number']));
        $this->assertSame('processing', DB::table('orders')->where('id', $result['order_id'])->value('status'));
    }

    public function test_seller_fulfillment_is_scoped_and_follows_the_confirmed_status_flow(): void
    {
        $seller = $this->createSeller('seller-one@example.test');
        $otherSeller = $this->createSeller('seller-two@example.test');
        $product = $this->createSellerProduct($seller, 'Seller one product');
        $otherProduct = $this->createSellerProduct($otherSeller, 'Seller two product');
        $result = $this->createOrder([
            (string) $product->json_id => 1,
            (string) $otherProduct->json_id => 1,
        ]);
        $this->assertTrue($result['ok'], $result['error'] ?? 'Checkout failed.');
        $this->assertTrue(OrderService::markPaid($result['order_number']));

        $sellerItem = DB::table('seller_order_items')->where('seller_id', $seller->id)->first();
        $otherItem = DB::table('seller_order_items')->where('seller_id', $otherSeller->id)->first();
        $this->assertNotNull($sellerItem);
        $this->assertNotNull($otherItem);

        Auth::guard('seller')->login($seller);
        $this->get('/seller/orders')->assertOk()
            ->assertSee('Seller one product')
            ->assertDontSee('Seller two product');
        $this->get('/seller/orders/'.$otherItem->id)->assertNotFound();
        $this->post('/seller/orders/'.$otherItem->id.'/status', ['status' => 'packed'])->assertNotFound();
        $this->post('/seller/orders/'.$sellerItem->id.'/status', ['status' => 'cancelled'])->assertSessionHasErrors('status');

        $this->post('/seller/orders/'.$sellerItem->id.'/status', [
            'status' => 'packed',
            'tracking_code' => '',
            'seller_note' => 'Packed for dispatch',
        ])->assertRedirect('/seller/orders/'.$sellerItem->id);
        $this->assertSame('packed', DB::table('seller_order_items')->where('id', $sellerItem->id)->value('status'));

        $this->post('/seller/orders/'.$sellerItem->id.'/status', [
            'status' => 'shipped',
            'tracking_code' => 'TRACK-123',
            'seller_note' => 'Handed to carrier',
        ])->assertRedirect('/seller/orders/'.$sellerItem->id);
        $this->post('/seller/orders/'.$sellerItem->id.'/status', ['status' => 'delivered'])
            ->assertRedirect('/seller/orders/'.$sellerItem->id);

        $this->assertDatabaseHas('seller_order_items', [
            'id' => $sellerItem->id,
            'seller_id' => $seller->id,
            'status' => 'delivered',
            'tracking_code' => 'TRACK-123',
        ]);
        $this->assertNotNull(DB::table('seller_order_items')->where('id', $sellerItem->id)->value('delivered_at'));
        $this->assertSame('confirmed', DB::table('seller_order_items')->where('id', $otherItem->id)->value('status'));
        $this->assertSame('processing', DB::table('orders')->where('id', $result['order_id'])->value('status'));
    }

    public function test_seller_inventory_history_is_limited_to_owned_products(): void
    {
        $seller = $this->createSeller('inventory-history@example.test');
        $otherSeller = $this->createSeller('other-inventory-history@example.test');
        $product = $this->createSellerProduct($seller, 'Seller history product');
        $otherProduct = $this->createSellerProduct($otherSeller, 'Other seller history product');

        DB::table('stock_movements')->insert([
            [
                'product_id' => $product->json_id,
                'order_id' => null,
                'delta' => 4,
                'type' => 'manual_adjust',
                'note' => 'Seller-owned restock',
                'created_at' => now(),
            ],
            [
                'product_id' => $otherProduct->json_id,
                'order_id' => null,
                'delta' => 9,
                'type' => 'manual_adjust',
                'note' => 'Other seller private movement',
                'created_at' => now(),
            ],
        ]);

        Auth::guard('seller')->login($seller);
        $this->get('/seller/inventory')->assertOk()
            ->assertSee('Inventory movement history')
            ->assertSee('Seller history product')
            ->assertSee('Seller-owned restock')
            ->assertDontSee('Other seller history product')
            ->assertDontSee('Other seller private movement');
    }

    public function test_admin_cancellation_atomically_restores_stock_and_closes_seller_items(): void
    {
        $seller = $this->createSeller();
        $product = $this->createSellerProduct($seller, 'Cancellation test product', stock: 5);
        $result = $this->createOrder([(string) $product->json_id => 2]);
        $this->assertTrue($result['ok'], $result['error'] ?? 'Checkout failed.');
        $this->assertTrue(OrderService::markPaid($result['order_number']));

        $this->assertSame(3, (int) $product->fresh()->stock);
        $this->assertSame('confirmed', DB::table('seller_order_items')->where('order_id', $result['order_id'])->value('status'));

        $manager = $this->createAdmin('manager');
        $this->withSession([
            'admin_user' => ['id' => $manager->id, 'name' => $manager->name, 'email' => $manager->email, 'role' => 'manager'],
            'admin_login_time' => time(),
            'admin_last_activity' => time(),
        ])->post('/admin/orders/update/'.$result['order_id'], [
            'status' => 'cancelled',
            'payment_status' => 'paid',
            'courier_id' => '',
            'tracking_code' => '',
            'admin_notes' => '',
        ])->assertRedirect();
        $this->assertSame('processing', DB::table('orders')->where('id', $result['order_id'])->value('status'));
        $this->assertSame(3, (int) $product->fresh()->stock);

        $this->withSession([
            'admin_user' => ['id' => $manager->id, 'name' => $manager->name, 'email' => $manager->email, 'role' => 'manager'],
            'admin_login_time' => time(),
            'admin_last_activity' => time(),
        ])->post('/admin/orders/update/'.$result['order_id'], [
            'status' => 'processing',
            'payment_status' => 'refunded',
            'courier_id' => '',
            'tracking_code' => '',
            'admin_notes' => '',
        ])->assertRedirect();
        $this->assertSame('paid', DB::table('orders')->where('id', $result['order_id'])->value('payment_status'));
        $this->assertSame(3, (int) $product->fresh()->stock);

        $admin = $this->createAdmin();
        $adminSession = [
            'admin_user' => ['id' => $admin->id, 'name' => $admin->name, 'email' => $admin->email, 'role' => 'admin'],
            'admin_login_time' => time(),
            'admin_last_activity' => time(),
        ];
        $this->withSession($adminSession)->post('/admin/orders/update/'.$result['order_id'], [
            'status' => 'processing',
            'payment_status' => 'refunded',
            'courier_id' => '',
            'tracking_code' => '',
            'admin_notes' => '',
        ])->assertRedirect();
        $this->assertSame('paid', DB::table('orders')->where('id', $result['order_id'])->value('payment_status'));
        $this->assertSame(3, (int) $product->fresh()->stock);

        $this->withSession($adminSession)->post('/admin/orders/update/'.$result['order_id'], [
            'status' => 'cancelled',
            'payment_status' => 'unpaid',
            'courier_id' => '',
            'tracking_code' => '',
            'admin_notes' => '',
        ])->assertRedirect();

        $this->assertSame('cancelled', DB::table('orders')->where('id', $result['order_id'])->value('status'));
        $this->assertSame('paid', DB::table('orders')->where('id', $result['order_id'])->value('payment_status'));
        $this->assertStringContainsString('refund review is required', (string) DB::table('orders')->where('id', $result['order_id'])->value('failed_reason'));
        $this->assertSame('cancelled', DB::table('seller_order_items')->where('order_id', $result['order_id'])->value('status'));
        $this->assertSame(5, (int) $product->fresh()->stock);
        $this->assertSame(5, (int) DB::table('catalog_stock')->where('json_id', $product->json_id)->value('stock'));
        $this->assertTrue(OrderService::releaseStock((int) $result['order_id'], 'order_cancelled'));
        $this->assertSame(5, (int) $product->fresh()->stock);

        Auth::guard('seller')->login($seller);
        $this->post('/seller/orders/'.DB::table('seller_order_items')->where('order_id', $result['order_id'])->value('id').'/status', [
            'status' => 'packed',
        ])->assertSessionHasErrors('status');
        $this->assertSame(5, (int) $product->fresh()->stock);
    }

    public function test_cancelling_a_paid_order_keeps_refund_review_visible_and_releases_stock_once(): void
    {
        $seller = $this->createSeller();
        $product = $this->createSellerProduct($seller, 'Paid cancellation product', stock: 5);
        $result = $this->createOrder([(string) $product->json_id => 2]);
        $this->assertTrue($result['ok'], $result['error'] ?? 'Checkout failed.');
        $this->assertTrue(OrderService::markPaid($result['order_number']));

        $this->assertTrue(OrderService::cancel((int) $result['order_id'], 'Cancellation requested concurrently.'));
        $order = DB::table('orders')->where('id', $result['order_id'])->first();

        $this->assertSame('cancelled', $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertStringContainsString('refund review is required', strtolower($order->failed_reason));
        $this->assertSame('cancelled', DB::table('seller_order_items')->where('order_id', $result['order_id'])->value('status'));
        $this->assertSame(5, (int) $product->fresh()->stock);
        $this->assertSame(1, DB::table('stock_movements')->where('order_id', $result['order_id'])->where('type', 'order_release')->count());
        $this->assertFalse(OrderService::cancel((int) $result['order_id'], 'Duplicate cancellation.'));
        $this->assertSame(5, (int) $product->fresh()->stock);
        $this->assertSame(1, DB::table('stock_movements')->where('order_id', $result['order_id'])->where('type', 'order_release')->count());
    }

    public function test_admin_return_receipt_restores_stock_and_closes_seller_item_as_returned(): void
    {
        $seller = $this->createSeller();
        $product = $this->createSellerProduct($seller, 'Returned seller product', stock: 5);
        $result = $this->createOrder([(string) $product->json_id => 2]);
        $this->assertTrue($result['ok'], $result['error'] ?? 'Checkout failed.');
        $this->assertTrue(OrderService::markPaid($result['order_number']));
        $admin = $this->createAdmin();
        $adminSession = [
            'admin_user' => ['id' => $admin->id, 'name' => $admin->name, 'email' => $admin->email, 'role' => 'admin'],
            'admin_login_time' => time(),
            'admin_last_activity' => time(),
        ];

        $this->withSession($adminSession)->post('/admin/orders/update/'.$result['order_id'], [
            'status' => 'shipped',
            'payment_status' => 'paid',
            'courier_id' => '',
            'tracking_code' => '',
            'admin_notes' => '',
        ])->assertRedirect();
        $this->withSession($adminSession)->post('/admin/orders/update/'.$result['order_id'], [
            'status' => 'delivered',
            'payment_status' => 'paid',
            'courier_id' => '',
            'tracking_code' => '',
            'admin_notes' => '',
        ])->assertRedirect();

        DB::table('order_returns')->insert([
            'order_id' => $result['order_id'],
            'reason' => 'Approved test return',
            'status' => 'approved',
            'created_at' => now(),
        ]);
        $this->withSession($adminSession)->post('/admin/orders/return/'.$result['order_id'], [
            'decision' => 'received',
        ])->assertRedirect();

        $this->assertSame('returned', DB::table('orders')->where('id', $result['order_id'])->value('status'));
        $this->assertSame('paid', DB::table('orders')->where('id', $result['order_id'])->value('payment_status'));
        $this->assertSame('received', DB::table('order_returns')->where('order_id', $result['order_id'])->value('status'));
        $this->assertSame('returned', DB::table('seller_order_items')->where('order_id', $result['order_id'])->value('status'));
        $this->assertSame(5, (int) $product->fresh()->stock);
        $this->assertSame(5, (int) DB::table('catalog_stock')->where('json_id', $product->json_id)->value('stock'));

        $this->withSession($adminSession)->post('/admin/orders/update/'.$result['order_id'], [
            'status' => 'returned',
            'payment_status' => 'refunded',
            'courier_id' => '',
            'tracking_code' => '',
            'admin_notes' => '',
        ])->assertRedirect();
        $this->assertSame('paid', DB::table('orders')->where('id', $result['order_id'])->value('payment_status'));

        $this->withSession($adminSession)->post('/admin/orders/update/'.$result['order_id'], [
            'status' => 'returned',
            'payment_status' => 'refunded',
            'courier_id' => '',
            'tracking_code' => '',
            'admin_notes' => 'External refund confirmed: QA-REFUND-001',
        ])->assertRedirect();
        $this->assertSame('refunded', DB::table('orders')->where('id', $result['order_id'])->value('payment_status'));
        $this->assertSame(5, (int) $product->fresh()->stock);
    }

    public function test_admin_return_status_does_not_claim_a_refund_without_external_confirmation(): void
    {
        $seller = $this->createSeller();
        $product = $this->createSellerProduct($seller, 'Manual refund confirmation product', stock: 5);
        $result = $this->createOrder([(string) $product->json_id => 2]);
        $this->assertTrue($result['ok'], $result['error'] ?? 'Checkout failed.');
        $this->assertTrue(OrderService::markPaid($result['order_number']));

        $admin = $this->createAdmin();
        $adminSession = [
            'admin_user' => ['id' => $admin->id, 'name' => $admin->name, 'email' => $admin->email, 'role' => 'admin'],
            'admin_login_time' => time(),
            'admin_last_activity' => time(),
        ];

        $this->withSession($adminSession)->post('/admin/orders/update/'.$result['order_id'], [
            'status' => 'shipped',
            'payment_status' => 'paid',
            'courier_id' => '',
            'tracking_code' => '',
            'admin_notes' => '',
        ])->assertRedirect();
        $this->withSession($adminSession)->post('/admin/orders/update/'.$result['order_id'], [
            'status' => 'failed_delivery',
            'payment_status' => 'paid',
            'courier_id' => '',
            'tracking_code' => '',
            'admin_notes' => '',
        ])->assertRedirect();
        $this->withSession($adminSession)->post('/admin/orders/update/'.$result['order_id'], [
            'status' => 'returned',
            'payment_status' => 'paid',
            'courier_id' => '',
            'tracking_code' => '',
            'admin_notes' => '',
        ])->assertRedirect();

        $this->assertSame('returned', DB::table('orders')->where('id', $result['order_id'])->value('status'));
        $this->assertSame('paid', DB::table('orders')->where('id', $result['order_id'])->value('payment_status'));
        $this->assertSame(5, (int) $product->fresh()->stock);
        $this->assertSame(1, DB::table('stock_movements')->where('order_id', $result['order_id'])->where('type', 'order_release')->count());

        $this->withSession($adminSession)->post('/admin/orders/update/'.$result['order_id'], [
            'status' => 'returned',
            'payment_status' => 'refunded',
            'courier_id' => '',
            'tracking_code' => '',
            'admin_notes' => 'External refund confirmed: QA-REFUND-002',
        ])->assertRedirect();
        $this->assertSame('refunded', DB::table('orders')->where('id', $result['order_id'])->value('payment_status'));
        $this->assertSame(5, (int) $product->fresh()->stock);
        $this->assertSame(1, DB::table('stock_movements')->where('order_id', $result['order_id'])->where('type', 'order_release')->count());
    }

    public function test_abandoned_online_payment_expires_after_thirty_minutes_but_cod_and_recent_orders_do_not(): void
    {
        $seller = $this->createSeller();
        $expiredProduct = $this->createSellerProduct($seller, 'Expired payment product', stock: 5);
        $recentProduct = $this->createSellerProduct($seller, 'Recent payment product', stock: 5);
        $codProduct = $this->createSellerProduct($seller, 'COD order product', stock: 5);
        $prescriptionProduct = $this->createSellerProduct($seller, 'Prescription payment product', stock: 5);

        $expired = $this->createOrder([(string) $expiredProduct->json_id => 2]);
        $recent = $this->createOrder([(string) $recentProduct->json_id => 2]);
        $cod = $this->createOrder([(string) $codProduct->json_id => 2]);
        $prescription = $this->createOrder([(string) $prescriptionProduct->json_id => 2]);
        foreach ([$expired, $recent] as $order) {
            $this->assertTrue($order['ok'], $order['error'] ?? 'Checkout failed.');
            $this->assertTrue(OrderService::markPending($order['order_number'], ['payment_method' => 'sslcommerz']));
        }
        $this->assertTrue($cod['ok'], $cod['error'] ?? 'Checkout failed.');
        $this->assertTrue($prescription['ok'], $prescription['error'] ?? 'Checkout failed.');
        $this->assertTrue(OrderService::markPending($prescription['order_number'], ['payment_method' => 'sslcommerz']));
        DB::table('orders')->where('id', $expired['order_id'])
            ->update(['created_at' => now()->subMinutes(31)]);
        DB::table('orders')->where('id', $prescription['order_id'])
            ->update([
                'created_at' => now()->subMinutes(31),
                'status' => 'awaiting_prescription',
            ]);

        $this->artisan('orders:expire-pending-payments', ['--dry-run' => true])
            ->expectsOutput('Would expire 1 pending online order(s).')
            ->assertSuccessful();
        $this->assertSame('pending', DB::table('orders')->where('id', $expired['order_id'])->value('payment_status'));
        $this->assertSame('awaiting_prescription', DB::table('orders')->where('id', $prescription['order_id'])->value('status'));
        $this->assertSame('pending', DB::table('orders')->where('id', $prescription['order_id'])->value('payment_status'));

        $this->artisan('orders:expire-pending-payments')
            ->expectsOutput('Expired 1 pending online order(s).')
            ->assertSuccessful();

        $this->assertSame('cancelled', DB::table('orders')->where('id', $expired['order_id'])->value('status'));
        $this->assertSame('failed', DB::table('orders')->where('id', $expired['order_id'])->value('payment_status'));
        $this->assertStringContainsString(
            'expired after 30 minutes',
            (string) DB::table('orders')->where('id', $expired['order_id'])->value('failed_reason')
        );
        $this->assertSame(5, (int) $expiredProduct->fresh()->stock);
        $this->assertSame(5, (int) DB::table('catalog_stock')->where('json_id', $expiredProduct->json_id)->value('stock'));
        $this->assertSame('cancelled', DB::table('seller_order_items')->where('order_id', $expired['order_id'])->value('status'));
        $this->assertSame(1, DB::table('stock_movements')->where('order_id', $expired['order_id'])->where('type', 'order_release')->count());

        $this->artisan('orders:expire-pending-payments')->assertSuccessful();
        $this->assertSame(1, DB::table('stock_movements')->where('order_id', $expired['order_id'])->where('type', 'order_release')->count());

        $latePayment = PaymentService::finalize([
            'tran_id' => $expired['order_number'],
            'val_id' => 'DEMO-EXPIRED-LATE-SUCCESS',
        ], 'sslcommerz');
        $this->assertSame('paid', $latePayment['status']);
        $this->assertSame('cancelled', DB::table('orders')->where('id', $expired['order_id'])->value('status'));
        $this->assertSame('paid', DB::table('orders')->where('id', $expired['order_id'])->value('payment_status'));
        $this->assertSame(5, (int) $expiredProduct->fresh()->stock);

        $this->assertSame('pending', DB::table('orders')->where('id', $recent['order_id'])->value('payment_status'));
        $this->assertSame(3, (int) $recentProduct->fresh()->stock);
        $this->assertSame('awaiting_prescription', DB::table('orders')->where('id', $prescription['order_id'])->value('status'));
        $this->assertSame('pending', DB::table('orders')->where('id', $prescription['order_id'])->value('payment_status'));
        $this->assertSame(3, (int) $prescriptionProduct->fresh()->stock);
        $this->assertSame('unpaid', DB::table('orders')->where('id', $cod['order_id'])->value('payment_status'));
        $this->assertSame(3, (int) $codProduct->fresh()->stock);
        $this->assertSame(0, DB::table('stock_movements')->where('order_id', $recent['order_id'])->where('type', 'order_release')->count());
        $this->assertSame(0, DB::table('stock_movements')->where('order_id', $prescription['order_id'])->where('type', 'order_release')->count());
        $this->assertSame(0, DB::table('stock_movements')->where('order_id', $cod['order_id'])->where('type', 'order_release')->count());
    }

    public function test_late_payment_after_cancellation_records_payment_without_reopening_order_or_reducing_stock(): void
    {
        config(['payments.mode' => 'demo']);
        $seller = $this->createSeller();
        $product = $this->createSellerProduct($seller, 'Late payment product', stock: 5);
        $result = $this->createOrder([(string) $product->json_id => 2]);
        $this->assertTrue($result['ok'], $result['error'] ?? 'Checkout failed.');

        $this->assertTrue(OrderService::markPending($result['order_number'], ['val_id' => 'GATEWAY-PENDING']));
        $this->assertSame('pending', DB::table('orders')->where('id', $result['order_id'])->value('payment_status'));
        $this->assertSame(3, (int) $product->fresh()->stock);

        $admin = $this->createAdmin();
        $adminSession = [
            'admin_user' => ['id' => $admin->id, 'name' => $admin->name, 'email' => $admin->email, 'role' => 'admin'],
            'admin_login_time' => time(),
            'admin_last_activity' => time(),
        ];
        $this->withSession($adminSession)->post('/admin/orders/update/'.$result['order_id'], [
            'status' => 'cancelled',
            'payment_status' => 'pending',
            'courier_id' => '',
            'tracking_code' => '',
            'admin_notes' => 'Cancelled while payment was pending.',
        ])->assertRedirect();
        $this->assertSame('cancelled', DB::table('orders')->where('id', $result['order_id'])->value('status'));
        $this->assertSame('pending', DB::table('orders')->where('id', $result['order_id'])->value('payment_status'));
        $this->assertSame(5, (int) $product->fresh()->stock);
        $this->assertSame('cancelled', DB::table('seller_order_items')->where('order_id', $result['order_id'])->value('status'));
        $this->withSession($adminSession)->post('/admin/orders/update/'.$result['order_id'], [
            'status' => 'cancelled',
            'payment_status' => 'pending',
            'courier_id' => '',
            'tracking_code' => '',
            'admin_notes' => '',
        ])->assertRedirect();
        $this->assertSame(1, DB::table('stock_movements')->where('order_id', $result['order_id'])->where('type', 'order_release')->count());

        $lateCallback = PaymentService::finalize([
            'tran_id' => $result['order_number'],
            'val_id' => 'DEMO-LATE-SUCCESS',
            'bank_tran_id' => 'DEMO-LATE-TRX',
        ], 'sslcommerz');
        $this->assertSame('paid', $lateCallback['status']);
        $this->assertStringContainsString('refund review', strtolower($lateCallback['reason']));
        $order = DB::table('orders')->where('id', $result['order_id'])->first();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertStringContainsString('refund review', strtolower($order->failed_reason));
        $this->assertSame('cancelled', DB::table('seller_order_items')->where('order_id', $result['order_id'])->value('status'));
        $this->assertSame(5, (int) $product->fresh()->stock);

        $duplicateCallback = PaymentService::finalize([
            'tran_id' => $result['order_number'],
            'val_id' => 'DEMO-DUPLICATE-SUCCESS',
        ], 'sslcommerz');
        $this->assertTrue($duplicateCallback['already']);
        $this->get('/order-success?order='.$result['order_number'])
            ->assertOk()
            ->assertSee('Payment was received after this order was closed');
        $this->assertFalse(OrderService::markFailed($result['order_number'], 'Late duplicate failure'));
        $this->assertTrue(OrderService::markPending($result['order_number']));
        $this->assertFalse(OrderService::cancel((int) $result['order_id']));
        $this->assertSame('cancelled', DB::table('orders')->where('id', $result['order_id'])->value('status'));
        $this->assertSame('paid', DB::table('orders')->where('id', $result['order_id'])->value('payment_status'));
        $this->assertSame(5, (int) $product->fresh()->stock);
        $this->assertSame(1, DB::table('stock_movements')->where('order_id', $result['order_id'])->where('type', 'order_release')->count());
    }

    public function test_duplicate_payment_and_failure_callbacks_are_idempotent_for_order_and_inventory(): void
    {
        $seller = $this->createSeller();
        $product = $this->createSellerProduct($seller, 'Duplicate callback product', stock: 5);
        $result = $this->createOrder([(string) $product->json_id => 2]);
        $this->assertTrue($result['ok'], $result['error'] ?? 'Checkout failed.');

        $this->assertTrue(OrderService::markPending($result['order_number']));
        $this->assertTrue(OrderService::markPaid($result['order_number'], ['val_id' => 'GATEWAY-SUCCESS']));
        $this->assertTrue(OrderService::markPaid($result['order_number'], ['val_id' => 'GATEWAY-DUPLICATE']));
        $this->assertFalse(OrderService::markFailed($result['order_number'], 'Stale failure callback'));

        $this->assertSame('processing', DB::table('orders')->where('id', $result['order_id'])->value('status'));
        $this->assertSame('paid', DB::table('orders')->where('id', $result['order_id'])->value('payment_status'));
        $this->assertSame('confirmed', DB::table('seller_order_items')->where('order_id', $result['order_id'])->value('status'));
        $this->assertSame(3, (int) $product->fresh()->stock);
        $this->assertSame(1, DB::table('stock_movements')->where('order_id', $result['order_id'])->where('type', 'order_hold')->count());
        $this->assertSame(0, DB::table('stock_movements')->where('order_id', $result['order_id'])->whereIn('type', ['order_release', 'order_return', 'order_cancelled'])->count());

        $failedSeller = $this->createSeller('failure@example.test');
        $failedProduct = $this->createSellerProduct($failedSeller, 'Duplicate failure product', stock: 5);
        $failedOrder = $this->createOrder([(string) $failedProduct->json_id => 2]);
        $this->assertTrue($failedOrder['ok'], $failedOrder['error'] ?? 'Checkout failed.');

        $this->assertTrue(OrderService::markPending($failedOrder['order_number']));
        $this->assertTrue(OrderService::markFailed($failedOrder['order_number'], 'Gateway failure'));
        $this->assertTrue(OrderService::markFailed($failedOrder['order_number'], 'Duplicate gateway failure'));
        $this->assertFalse(OrderService::markPending($failedOrder['order_number']));
        $this->assertSame('cancelled', DB::table('orders')->where('id', $failedOrder['order_id'])->value('status'));
        $this->assertSame('failed', DB::table('orders')->where('id', $failedOrder['order_id'])->value('payment_status'));
        $this->assertSame(5, (int) $failedProduct->fresh()->stock);
        $this->assertSame(1, DB::table('stock_movements')->where('order_id', $failedOrder['order_id'])->where('type', 'order_release')->count());
    }

    private function createOrder(array $cart): array
    {
        static $courierCounter = 0;
        $courierCounter++;
        DB::table('courier_methods')->insert([
            'name' => 'Phase Three Courier '.$courierCounter,
            'code' => 'phase-three-'.$courierCounter,
            'fee' => 60,
            'cod_fee' => 0,
            'free_delivery_min' => 0,
            'is_default' => true,
            'status' => 'active',
            'sort_order' => 1,
            'created_at' => now(),
        ]);
        session(['cart' => $cart]);

        return CheckoutService::createOrder([
            'name' => 'Test Buyer',
            'phone' => '01712345678',
            'email' => 'buyer@example.test',
            'address' => 'Test delivery address',
            'area' => 'Dhaka',
            'payment_method' => 'cod',
        ]);
    }

    private function createSeller(string $email = 'seller@example.test'): Seller
    {
        static $counter = 0;
        $counter++;

        return Seller::query()->create([
            'shop_name' => 'Seller Shop '.$counter,
            'slug' => 'seller-shop-'.$counter,
            'owner_name' => 'Seller Owner',
            'email' => $email,
            'phone' => '+8801712345678',
            'password_hash' => password_hash('long-secure-password', PASSWORD_DEFAULT),
            'status' => 'active',
            'application_status' => 'approved',
            'is_active' => true,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createSellerProduct(Seller $seller, string $name, int $stock = 5): Product
    {
        static $counter = 0;
        $counter++;
        $category = Category::query()->create([
            'name' => 'Seller Category '.$counter,
            'slug' => 'seller-category-'.$counter,
        ]);
        $jsonId = 98000 + $counter;
        $product = Product::query()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'name' => $name,
            'slug' => 'phase-three-product-'.$counter,
            'short_description' => 'Seller fulfillment test item',
            'description' => 'A product used for seller fulfillment tests.',
            'price' => '100.00',
            'stock' => $stock,
            'low_stock_threshold' => 1,
            'requires_prescription' => false,
            'approval_status' => 'approved',
            'is_active' => true,
            'json_id' => $jsonId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('catalog_stock')->insert([
            'json_id' => $jsonId,
            'stock' => $stock,
            'requires_prescription' => false,
        ]);

        return $product;
    }

    private function createAdmin(string $role = 'admin'): User
    {
        static $counter = 0;
        $counter++;

        return User::query()->create([
            'name' => 'Administrator',
            'email' => 'admin'.$counter.'@example.test',
            'password_hash' => password_hash('not-used', PASSWORD_DEFAULT),
            'role' => $role,
            'status' => 'active',
            'created_at' => now(),
        ]);
    }
}

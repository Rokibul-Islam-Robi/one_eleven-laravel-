<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerLog;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\Support\Catalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SellerPhaseTwoTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_product_is_private_until_reviewed_and_can_be_rejected_then_resubmitted(): void
    {
        $seller = $this->createSeller();
        $otherSeller = $this->createSeller(['email' => 'other@example.test']);
        $category = $this->createCategory();
        Auth::guard('seller')->login($seller);

        $this->post('/seller/products', $this->productInput($category, 'draft'))
            ->assertRedirect('/seller/products');

        $product = Product::query()->where('seller_id', $seller->id)->firstOrFail();
        $this->assertSame('draft', $product->approval_status);
        $this->assertNotNull($product->json_id);
        $this->assertNull(Catalog::byId((string) $product->json_id));

        Auth::guard('seller')->login($otherSeller);
        $this->get('/seller/products/'.$product->id.'/edit')->assertNotFound();

        Auth::guard('seller')->login($seller);
        $this->post('/seller/products/'.$product->id, $this->productInput($category, 'submit'))
            ->assertRedirect('/seller/products');
        $product->refresh();
        $this->assertSame('pending', $product->approval_status);
        $this->assertFalse($product->is_active);
        $this->assertNull(Catalog::byId((string) $product->json_id));

        $admin = $this->createAdmin();
        $this->actingAsAdmin($admin);
        $this->get('/admin/products/review-queue')->assertOk()->assertSee('Phase Two Product');
        $this->post('/admin/products/reject/'.$product->id, ['reason' => 'Please provide a clearer label.'])
            ->assertRedirect();
        $this->assertSame('rejected', $product->fresh()->approval_status);
        $this->assertSame('Please provide a clearer label.', $product->fresh()->rejection_reason);

        Auth::guard('seller')->login($seller);
        $this->post('/seller/products/'.$product->id, $this->productInput($category, 'submit', [
            'name' => 'Phase Two Product Revised',
        ]))->assertRedirect('/seller/products');
        $this->assertSame('pending', $product->fresh()->approval_status);
        $this->assertNull($product->fresh()->rejection_reason);

        $this->actingAsAdmin($admin);
        $this->post('/admin/products/approve/'.$product->id)->assertRedirect();
        $product->refresh();
        $this->assertSame('approved', $product->approval_status);
        $this->assertTrue($product->is_active);
        $this->assertSame('Phase Two Product Revised', Catalog::byId((string) $product->json_id)['n']);
        $this->assertDatabaseHas('seller_logs', [
            'seller_id' => $seller->id,
            'actor_type' => 'admin',
            'action' => 'admin.product.approved',
        ]);
    }

    public function test_seller_inventory_adjustments_sync_catalog_stock_and_enforce_ownership_and_bounds(): void
    {
        $seller = $this->createSeller();
        $otherSeller = $this->createSeller(['email' => 'other@example.test']);
        $product = $this->createSellerProduct($seller, stock: 5);
        Auth::guard('seller')->login($seller);

        $this->post('/seller/products/'.$product->id.'/inventory', [
            'adjustment' => 3,
            'note' => 'Received new stock',
        ])->assertRedirect('/seller/inventory');

        $this->assertSame(8, (int) $product->fresh()->stock);
        $this->assertSame(8, (int) DB::table('catalog_stock')->where('json_id', $product->json_id)->value('stock'));
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->json_id,
            'delta' => 3,
            'type' => 'manual_adjust',
        ]);

        $this->post('/seller/products/'.$product->id.'/inventory', [
            'adjustment' => -9,
            'note' => 'Invalid overdraw',
        ])->assertSessionHasErrors('adjustment');
        $this->assertSame(8, (int) $product->fresh()->stock);

        Auth::guard('seller')->login($otherSeller);
        $this->post('/seller/products/'.$product->id.'/inventory', [
            'adjustment' => 1,
            'note' => 'Should not be allowed',
        ])->assertNotFound();
        $this->assertSame(8, (int) $product->fresh()->stock);
    }

    public function test_checkout_and_stock_release_keep_seller_product_and_storefront_inventory_in_sync(): void
    {
        $seller = $this->createSeller();
        $product = $this->createSellerProduct($seller, stock: 5, approved: true);
        DB::table('courier_methods')->insert([
            'name' => 'Test Courier',
            'code' => 'test',
            'fee' => 60,
            'cod_fee' => 0,
            'free_delivery_min' => 0,
            'is_default' => true,
            'status' => 'active',
            'sort_order' => 1,
            'created_at' => now(),
        ]);
        session(['cart' => [(string) $product->json_id => 2]]);

        $result = CheckoutService::createOrder([
            'name' => 'Test Buyer',
            'phone' => '01712345678',
            'email' => 'buyer@example.test',
            'address' => 'Test delivery address',
            'area' => 'Dhaka',
            'payment_method' => 'cod',
        ]);

        $this->assertTrue($result['ok'], $result['error'] ?? 'Checkout failed.');
        $this->assertSame(3, (int) $product->fresh()->stock);
        $this->assertSame(3, (int) DB::table('catalog_stock')->where('json_id', $product->json_id)->value('stock'));

        $this->assertTrue(OrderService::releaseStock((int) $result['order_id']));
        $this->assertSame(5, (int) $product->fresh()->stock);
        $this->assertSame(5, (int) DB::table('catalog_stock')->where('json_id', $product->json_id)->value('stock'));
    }

    private function productInput(Category $category, string $intent, array $overrides = []): array
    {
        return array_replace([
            'name' => 'Phase Two Product',
            'category_id' => $category->id,
            'price' => '100.00',
            'stock' => 5,
            'low_stock_threshold' => 2,
            'requires_prescription' => 0,
            'description' => 'A product submitted for marketplace review.',
            'intent' => $intent,
        ], $overrides);
    }

    private function createCategory(): Category
    {
        static $counter = 0;
        $counter++;

        return Category::query()->create([
            'name' => 'Seller Category '.$counter,
            'slug' => 'seller-category-'.$counter,
        ]);
    }

    private function createSeller(array $overrides = []): Seller
    {
        static $counter = 0;
        $counter++;

        return Seller::query()->create(array_replace([
            'shop_name' => 'Seller Shop '.$counter,
            'slug' => 'seller-shop-'.$counter,
            'owner_name' => 'Seller Owner',
            'email' => 'seller'.$counter.'@example.test',
            'phone' => '+8801712345678',
            'password_hash' => password_hash('long-secure-password', PASSWORD_DEFAULT),
            'status' => 'active',
            'application_status' => 'approved',
            'is_active' => true,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function createSellerProduct(Seller $seller, int $stock, bool $approved = false): Product
    {
        $category = $this->createCategory();
        $jsonId = 9000 + (int) Product::query()->count();
        $product = Product::query()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'name' => 'Inventory Test Product '.$jsonId,
            'slug' => 'inventory-test-product-'.$jsonId,
            'short_description' => 'Inventory test item',
            'description' => 'A seller product used to test inventory synchronization.',
            'price' => '100.00',
            'stock' => $stock,
            'low_stock_threshold' => 2,
            'requires_prescription' => false,
            'approval_status' => $approved ? 'approved' : 'pending',
            'is_active' => $approved,
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

    private function createAdmin(): User
    {
        return User::query()->create([
            'name' => 'Administrator',
            'email' => 'admin@example.test',
            'password_hash' => password_hash('not-used', PASSWORD_DEFAULT),
            'role' => 'admin',
            'status' => 'active',
            'created_at' => now(),
        ]);
    }

    private function actingAsAdmin(User $admin): void
    {
        $this->withSession([
            'admin_user' => ['id' => $admin->id, 'name' => $admin->name, 'email' => $admin->email, 'role' => $admin->role],
            'admin_login_time' => time(),
            'admin_last_activity' => time(),
        ]);
    }
}

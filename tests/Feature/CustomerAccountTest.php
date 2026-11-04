<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'full_name' => 'Ayesha Rahman',
            'email' => 'ayesha@example.test',
            'phone' => '+8801712345678',
            'password_hash' => password_hash('not-used', PASSWORD_DEFAULT),
        ]);
        $this->signInAs($this->customer);
    }

    public function test_sidebar_pages_require_a_customer_session_and_profile_updates_are_persisted(): void
    {
        $this->get('/account/profile')->assertOk()->assertSee('My Products')->assertSee('Logout');
        $this->post('/account/profile', [
            'full_name' => 'Ayesha R. Rahman',
            'gender' => 'female',
            'date_of_birth' => '1995-05-12',
        ])->assertRedirect('/account/profile')->assertSessionHas('account_flash');

        $updated = Customer::findOrFail($this->customer->id);
        $this->assertSame('Ayesha R. Rahman', $updated->full_name);
        $this->assertSame('female', $updated->gender);
        $this->assertSame('1995-05-12', $updated->date_of_birth->format('Y-m-d'));
        $this->assertNull($updated->email_verified_at);
        $this->assertNull($updated->phone_verified_at);

        \App\Support\CustomerAuth::logout();
        $this->get('/account/patients')->assertRedirect('/account');
    }

    public function test_profile_photo_upload_is_stored_and_shown_from_the_customer_account(): void
    {
        Storage::fake('local');

        $this->post('/account/profile/avatar', [
            'avatar' => UploadedFile::fake()->image('profile.png'),
        ])->assertRedirect('/account/profile')->assertSessionHas('account_flash');

        $customer = Customer::findOrFail($this->customer->id);
        $this->assertNotEmpty($customer->profile_photo_path);
        Storage::disk('local')->assertExists($customer->profile_photo_path);

        $this->get('/account/avatar')->assertOk();
        $this->get('/account/profile')
            ->assertOk()
            ->assertSee('Profile information')
            ->assertSee('src="account/avatar"', false);
    }

    public function test_addresses_are_owned_defaulted_and_prefilled_for_checkout(): void
    {
        $this->post('/account/addresses', $this->addressInput())
            ->assertRedirect('/account/addresses');
        $firstId = DB::table('customer_addresses')->value('id');
        $this->assertDatabaseHas('customer_addresses', ['id' => $firstId, 'customer_id' => $this->customer->id, 'is_default' => 1]);

        $second = $this->addressInput();
        $second['full_name'] = 'Ayesha Office';
        $second['area'] = 'Gulshan';
        $second['is_default'] = '1';
        $this->post('/account/addresses', $second)
            ->assertRedirect('/account/addresses')
            ->assertSessionHas('account_flash');
        $this->assertDatabaseHas('customer_addresses', ['id' => $firstId, 'is_default' => 0]);

        session(['cart' => ['1' => 1]]);
        $this->get('/checkout')->assertOk()
            ->assertSee('value="Ayesha Office"', false)
            ->assertSee('House 10, Road 2, Gulshan');

        $other = Customer::create([
            'full_name' => 'Other Customer',
            'email' => 'other@example.test',
            'password_hash' => password_hash('not-used', PASSWORD_DEFAULT),
        ]);
        $otherAddressId = DB::table('customer_addresses')->insertGetId($this->addressInput() + [
            'customer_id' => $other->id,
            'is_default' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->signInAs($other);
        $this->post('/account/addresses', ['operation' => 'delete', 'id' => $firstId])->assertNotFound();
        $this->assertDatabaseHas('customer_addresses', ['id' => $firstId, 'customer_id' => $this->customer->id]);
        $this->post('/account/addresses', ['operation' => 'delete', 'id' => $otherAddressId])
            ->assertRedirect('/account/addresses');
        $this->assertDatabaseHas('customer_addresses', ['id' => $firstId]);
        $this->assertDatabaseMissing('customer_addresses', ['id' => $otherAddressId]);
    }

    public function test_patient_records_support_crud_and_are_scoped_to_the_customer(): void
    {
        $this->post('/account/patients', [
            'full_name' => 'Maliha Rahman',
            'date_of_birth' => '2018-03-04',
            'gender' => 'female',
            'relationship' => 'daughter',
        ])->assertRedirect('/account/patients');
        $id = DB::table('customer_patients')->value('id');

        $this->get('/account/patients')->assertOk()->assertSee('Maliha Rahman');
        $this->post('/account/patients', [
            'operation' => 'update',
            'id' => $id,
            'full_name' => 'Maliha A. Rahman',
            'date_of_birth' => '2018-03-04',
            'gender' => 'female',
            'relationship' => 'daughter',
        ])->assertRedirect('/account/patients');
        $this->assertDatabaseHas('customer_patients', ['id' => $id, 'full_name' => 'Maliha A. Rahman']);

        $other = Customer::create([
            'full_name' => 'Other Customer',
            'email' => 'other-patient@example.test',
            'password_hash' => password_hash('not-used', PASSWORD_DEFAULT),
        ]);
        $otherPatientId = DB::table('customer_patients')->insertGetId([
            'customer_id' => $other->id,
            'full_name' => 'Other child',
            'relationship' => 'child',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->signInAs($other);
        $this->post('/account/patients', [
            'operation' => 'delete',
            'id' => $id,
        ])->assertNotFound();
        $this->assertDatabaseHas('customer_patients', ['id' => $id, 'customer_id' => $this->customer->id]);
        $this->post('/account/patients', [
            'operation' => 'delete',
            'id' => $otherPatientId,
        ])->assertRedirect('/account/patients');
    }

    public function test_prescriptions_upload_and_private_file_access_are_customer_scoped(): void
    {
        Storage::fake('local');
        $this->post('/account/prescriptions', [
            'prescription' => UploadedFile::fake()->image('prescription.png'),
            'notes' => 'Current prescription',
        ])->assertRedirect('/account/prescriptions')->assertSessionHas('account_flash');

        $prescription = DB::table('prescriptions')->where('store_customer_id', $this->customer->id)->first();
        $this->assertNotNull($prescription);
        Storage::disk('local')->assertExists($prescription->file_path);
        $fileResponse = $this->get('/account/prescriptions/' . $prescription->id . '/file')->assertOk();
        $this->assertStringContainsString('private', $fileResponse->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $fileResponse->headers->get('Cache-Control'));
        $this->get('/account/prescriptions')->assertOk()->assertSee('Current prescription');

        $other = Customer::create([
            'full_name' => 'Other Customer',
            'email' => 'other-rx@example.test',
            'password_hash' => password_hash('not-used', PASSWORD_DEFAULT),
        ]);
        $this->signInAs($other);
        $this->get('/account/prescriptions/' . $prescription->id . '/file')->assertNotFound();
    }

    public function test_prescriptions_work_with_legacy_customer_id_and_without_store_customer_column(): void
    {
        Storage::fake('local');
        Schema::table('prescriptions', function ($table) {
            $table->dropIndex('prescriptions_store_customer_idx');
        });
        Schema::table('prescriptions', function ($table) {
            $table->dropColumn('store_customer_id');
        });

        $legacyUserId = DB::table('users')->insertGetId([
            'name' => 'Ayesha Rahman',
            'email' => 'AYESHA@example.test',
            'phone' => $this->customer->phone,
            'password_hash' => password_hash('not-used', PASSWORD_DEFAULT),
            'role' => 'customer',
            'status' => 'active',
            'created_at' => now(),
        ]);
        DB::table('prescriptions')->insert([
            'customer_id' => $legacyUserId,
            'customer_name' => 'Legacy prescription',
            'phone' => null,
            'original_name' => 'legacy.pdf',
            'status' => 'pending',
            'created_at' => now()->subDay(),
        ]);

        $this->get('/account/prescriptions')->assertOk()->assertSee('legacy.pdf');
        $this->post('/account/prescriptions', [
            'prescription' => UploadedFile::fake()->image('current.png'),
            'notes' => 'Uploaded without optional account ID',
        ])->assertRedirect('/account/prescriptions')->assertSessionHas('account_flash');

        $uploaded = DB::table('prescriptions')->where('original_name', 'current.png')->first();
        $this->assertNotNull($uploaded);
        $this->assertNull($uploaded->customer_id);
        $this->assertSame($this->customer->phone, $uploaded->phone);
        Storage::disk('local')->assertExists($uploaded->file_path);
        $this->get('/account/prescriptions')->assertOk()
            ->assertSee('legacy.pdf')
            ->assertSee('current.png')
            ->assertSee('Uploaded without optional account ID');
        $this->get('/account/prescriptions/' . $uploaded->id . '/file')->assertOk();

        $other = Customer::create([
            'full_name' => 'Other Customer',
            'email' => 'other-prescription@example.test',
            'phone' => '01922222222',
            'password_hash' => password_hash('not-used', PASSWORD_DEFAULT),
        ]);
        $this->signInAs($other);
        $this->get('/account/prescriptions')->assertOk()
            ->assertDontSee('legacy.pdf')
            ->assertDontSee('current.png');
        $this->get('/account/prescriptions/' . $uploaded->id . '/file')->assertNotFound();
        $this->post('/account/prescriptions/' . $uploaded->id . '/delete')->assertNotFound();
    }

    public function test_purchased_products_and_reviews_require_a_delivered_customer_order(): void
    {
        $orderId = DB::table('orders')->insertGetId([
            'order_number' => 'TEST-DELIVERED',
            'customer_email' => $this->customer->email,
            'subtotal' => 100,
            'shipping' => 0,
            'cod_fee' => 0,
            'total' => 100,
            'status' => 'delivered',
            'shipping_name' => $this->customer->full_name,
            'shipping_phone' => $this->customer->phone,
            'shipping_address' => 'Test address',
            'created_at' => now(),
        ]);
        DB::table('order_items')->insert([
            'order_id' => $orderId,
            'product_id' => 1,
            'product_name' => 'Vitamin C 1000mg',
            'price' => 100,
            'qty' => 1,
        ]);

        $this->get('/account/products')->assertOk()->assertSee('Vitamin C 1000mg');
        $this->get('/account/reviews')->assertOk()->assertSee('Select a delivered product');
        $this->post('/account/reviews', [
            'product_id' => '1',
            'rating' => 5,
            'review' => 'A good product.',
        ])->assertRedirect('/account/reviews')->assertSessionHas('account_flash');
        $this->assertDatabaseHas('storefront_customer_reviews', [
            'customer_id' => $this->customer->id,
            'catalog_product_id' => '1',
            'rating' => 5,
        ]);
        $this->get('/account/reviews')->assertOk()->assertSee('A good product.');

        $other = Customer::create([
            'full_name' => 'Other Customer',
            'email' => 'other-review@example.test',
            'password_hash' => password_hash('not-used', PASSWORD_DEFAULT),
        ]);
        $this->signInAs($other);
        $this->post('/account/reviews', [
            'product_id' => '1',
            'rating' => 5,
            'review' => 'Not purchased by this customer.',
        ])->assertForbidden();
        $this->get('/account/products')->assertOk()->assertDontSee('Vitamin C 1000mg');
    }

    public function test_order_history_uses_existing_contacts_without_optional_customer_link_column(): void
    {
        $this->dropStoreCustomerLink();

        $this->insertOrderItem('TEST-OWN-EMAIL', 'delivered', 'Ayesha@Example.test', '01911111111', 'Owned by email', 1);
        $this->insertOrderItem('TEST-CANCELLED', 'cancelled', $this->customer->email, $this->customer->phone, 'Cancelled item', 2);
        $this->insertOrderItem('TEST-RETURNED', 'returned', $this->customer->email, $this->customer->phone, 'Returned item', 3);

        $other = Customer::create([
            'full_name' => 'Other Customer',
            'email' => 'other-order@example.test',
            'phone' => '01922222222',
            'password_hash' => password_hash('not-used', PASSWORD_DEFAULT),
        ]);
        $this->insertOrderItem('TEST-OTHER', 'delivered', $other->email, $other->phone, 'Another customer item', 4);

        $this->get('/account/products')->assertOk()
            ->assertSee('Owned by email')
            ->assertDontSee('Cancelled item')
            ->assertDontSee('Returned item')
            ->assertDontSee('Another customer item');

        $phoneOnly = Customer::create([
            'full_name' => 'Phone Customer',
            'phone' => '+8801912345678',
            'password_hash' => password_hash('not-used', PASSWORD_DEFAULT),
        ]);
        $this->insertOrderItem('TEST-OWN-PHONE', 'processing', null, '01912345678', 'Owned by phone', 5);
        $this->signInAs($phoneOnly);
        $this->get('/account/products')->assertOk()->assertSee('Owned by phone')->assertDontSee('Owned by email');

        $noContact = Customer::create([
            'full_name' => 'No Contact',
            'password_hash' => password_hash('not-used', PASSWORD_DEFAULT),
        ]);
        $this->signInAs($noContact);
        $this->get('/account/products')->assertOk()
            ->assertDontSee('Owned by phone')
            ->assertDontSee('Another customer item');
    }

    public function test_checkout_creates_order_without_optional_customer_link_column(): void
    {
        $this->dropStoreCustomerLink();
        DB::table('catalog_stock')->insert(['json_id' => 1, 'stock' => 5]);
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
        session(['cart' => ['1' => 1]]);

        $result = CheckoutService::createOrder([
            'name' => $this->customer->full_name,
            'phone' => '01712345678',
            'email' => $this->customer->email,
            'address' => 'Test delivery address',
            'area' => 'Dhaka',
            'payment_method' => 'cod',
        ]);

        $this->assertTrue($result['ok'], $result['error'] ?? 'Checkout failed.');
        $this->assertDatabaseHas('orders', [
            'id' => $result['order_id'],
            'customer_email' => $this->customer->email,
            'shipping_phone' => '01712345678',
        ]);
    }

    public function test_logout_invalidates_customer_session_and_protected_account_routes(): void
    {
        $this->post('/account/logout')->assertRedirect('/account')->assertSessionMissing('user');
        $this->get('/account/profile')->assertRedirect('/account');
    }

    private function signInAs(Customer $customer): void
    {
        session(['user' => [
            'id' => (int) $customer->id,
            'name' => (string) $customer->full_name,
            'contact' => (string) ($customer->email ?? $customer->phone),
        ]]);
    }

    private function insertOrderItem(
        string $orderNumber,
        string $status,
        ?string $email,
        string $phone,
        string $productName,
        int $productId
    ): void {
        $orderId = DB::table('orders')->insertGetId([
            'order_number' => $orderNumber,
            'customer_email' => $email,
            'subtotal' => 100,
            'shipping' => 0,
            'cod_fee' => 0,
            'total' => 100,
            'status' => $status,
            'shipping_name' => 'Test Customer',
            'shipping_phone' => $phone,
            'shipping_address' => 'Test address',
            'created_at' => now(),
        ]);
        DB::table('order_items')->insert([
            'order_id' => $orderId,
            'product_id' => $productId,
            'product_name' => $productName,
            'price' => 100,
            'qty' => 1,
        ]);
    }

    private function dropStoreCustomerLink(): void
    {
        Schema::table('orders', function ($table) {
            $table->dropIndex('orders_store_customer_idx');
        });
        Schema::table('orders', function ($table) {
            $table->dropColumn('store_customer_id');
        });
    }

    private function addressInput(): array
    {
        return [
            'full_name' => $this->customer->full_name,
            'phone' => '01712345678',
            'division' => 'Dhaka',
            'district' => 'Dhaka',
            'area' => 'Banani',
            'street_address' => 'House 10, Road 2',
            'instructions' => 'Call on arrival',
        ];
    }
}

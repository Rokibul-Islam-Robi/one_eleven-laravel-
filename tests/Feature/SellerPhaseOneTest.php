<?php

namespace Tests\Feature;

use App\Models\Seller;
use App\Models\SellerDocument;
use App\Models\SellerLog;
use App\Models\User;
use App\Notifications\SellerAccountStatusNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SellerPhaseOneTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_persists_a_pending_application_and_private_document(): void
    {
        Notification::fake();
        Storage::fake('local');

        $this->post('/seller/register', $this->applicationInput([
            'document_type' => 'trade_license',
            'document' => UploadedFile::fake()->create('license.pdf', 120, 'application/pdf'),
        ]))->assertRedirect('/seller/email/verify');

        $seller = Seller::query()->where('email', 'seller@example.test')->firstOrFail();
        $this->assertSame('pending', $seller->application_status);
        $this->assertSame('pending', $seller->status);
        $this->assertNull($seller->email_verified_at);
        $this->assertDatabaseHas('seller_documents', [
            'seller_id' => $seller->id,
            'document_type' => 'trade_license',
            'status' => 'pending',
        ]);
        $document = $seller->sellerDocuments()->firstOrFail();
        Storage::disk('local')->assertExists($document->storage_path);
        Notification::assertSentTo($seller, VerifyEmail::class);
    }

    public function test_seller_login_password_reset_and_signed_email_verification_use_the_seller_guard(): void
    {
        Notification::fake();
        $seller = $this->createSeller();

        $this->post('/seller/login', [
            'email' => $seller->email,
            'password' => 'long-secure-password',
        ])->assertRedirect('/seller/dashboard');
        $this->get('/seller/dashboard')->assertOk()->assertSee('Pending');

        Auth::guard('seller')->logout();
        $this->post('/seller/password/email', ['email' => $seller->email])->assertRedirect();
        $resetNotification = Notification::sent($seller, ResetPassword::class)->first();
        $this->assertNotNull($resetNotification);

        $this->post('/seller/password/reset', [
            'token' => $resetNotification->token,
            'email' => $seller->email,
            'password' => 'replacement-secure-password',
            'password_confirmation' => 'replacement-secure-password',
        ])->assertRedirect('/seller/login');
        $this->assertTrue(password_verify('replacement-secure-password', $seller->fresh()->password_hash));

        Auth::guard('seller')->login($seller->fresh());
        $verificationUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), [
            'id' => $seller->id,
            'hash' => sha1($seller->email),
        ]);
        $this->get($verificationUrl)->assertRedirect('/seller/dashboard');
        $this->assertNotNull($seller->fresh()->email_verified_at);
    }

    public function test_seller_can_only_retrieve_their_own_private_documents_and_suspended_access_is_denied(): void
    {
        Storage::fake('local');
        $owner = $this->createSeller(['status' => 'active', 'application_status' => 'approved', 'email_verified_at' => now()]);
        $other = $this->createSeller(['email' => 'other@example.test']);
        $path = 'seller-documents/'.$owner->id.'/private.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 test');
        $document = SellerDocument::query()->create([
            'seller_id' => $owner->id,
            'document_type' => 'other',
            'storage_path' => $path,
            'original_name' => 'private.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 14,
            'status' => 'pending',
            'created_at' => now(),
        ]);

        Auth::guard('seller')->login($owner);
        $this->get('/seller/documents/'.$document->id)->assertOk();

        Auth::guard('seller')->login($other);
        $this->get('/seller/documents/'.$document->id)->assertNotFound();

        Auth::guard('seller')->login($owner);
        DB::table('sellers')->where('id', $owner->id)->update(['status' => 'suspended', 'is_active' => false]);
        $this->post('/seller/profile', $this->profileInput($owner))
            ->assertForbidden();
    }

    public function test_payout_changes_require_password_and_audit_omits_account_values(): void
    {
        $seller = $this->createSeller([
            'status' => 'active',
            'application_status' => 'approved',
            'email_verified_at' => now(),
            'payout_account' => 'old-private-account',
            'payout_account_name' => 'Seller Owner',
        ]);
        Auth::guard('seller')->login($seller);

        $this->post('/seller/profile/payout', [
            'current_password' => 'incorrect-password',
            'payout_method' => 'bank',
            'payout_account' => 'new-private-account',
            'payout_account_name' => 'Seller Owner',
        ])->assertSessionHasErrors('current_password');
        $this->assertSame('old-private-account', $seller->fresh()->payout_account);

        $this->post('/seller/profile/payout', [
            'current_password' => 'long-secure-password',
            'payout_method' => 'bank',
            'payout_account' => 'new-private-account',
            'payout_account_name' => 'Seller Owner',
        ])->assertRedirect('/seller/profile');
        $this->assertSame('new-private-account', $seller->fresh()->payout_account);
        $this->assertFalse($seller->fresh()->payout_verified);
        $details = SellerLog::query()->where('seller_id', $seller->id)->pluck('detail')->implode(' ');
        $this->assertStringNotContainsString('new-private-account', $details);
        $this->assertStringNotContainsString('old-private-account', $details);
    }

    public function test_admin_must_verify_email_and_has_audited_approval_and_activation_actions(): void
    {
        Notification::fake();
        $admin = User::query()->create([
            'name' => 'Administrator',
            'email' => 'admin@example.test',
            'password_hash' => password_hash('not-used', PASSWORD_DEFAULT),
            'role' => 'admin',
            'status' => 'active',
            'created_at' => now(),
        ]);
        $seller = $this->createSeller();
        $this->actingAsAdmin($admin);

        $this->post('/admin/sellers/approve/'.$seller->id)
            ->assertRedirect('/admin/sellers/view/'.$seller->id);
        $this->assertSame('pending', $seller->fresh()->application_status);

        $seller->forceFill(['email_verified_at' => now()])->save();
        $this->post('/admin/sellers/approve/'.$seller->id)
            ->assertRedirect('/admin/sellers/view/'.$seller->id);
        $this->assertSame('approved', $seller->fresh()->application_status);
        $this->assertSame('pending', $seller->fresh()->status);

        $this->post('/admin/sellers/activate/'.$seller->id)
            ->assertRedirect('/admin/sellers/view/'.$seller->id);
        $this->assertSame('active', $seller->fresh()->status);
        $this->assertTrue($seller->fresh()->is_active);

        $this->post('/admin/sellers/suspend/'.$seller->id, ['reason' => 'Application details require review.'])
            ->assertRedirect('/admin/sellers/view/'.$seller->id);
        $this->assertSame('suspended', $seller->fresh()->status);
        $this->assertFalse($seller->fresh()->is_active);
        $this->post('/admin/sellers/activate/'.$seller->id)
            ->assertRedirect('/admin/sellers/view/'.$seller->id);
        $this->post('/admin/sellers/deactivate/'.$seller->id)
            ->assertRedirect('/admin/sellers/view/'.$seller->id);
        $this->assertFalse($seller->fresh()->is_active);
        $this->assertDatabaseHas('seller_logs', [
            'seller_id' => $seller->id,
            'actor_type' => 'admin',
            'actor_id' => $admin->id,
            'action' => 'admin.activated',
        ]);
        Notification::assertSentToTimes($seller, SellerAccountStatusNotification::class, 5);

        $rejectedSeller = $this->createSeller([
            'email_verified_at' => now(),
            'email' => 'rejected@example.test',
        ]);
        $this->post('/admin/sellers/reject/'.$rejectedSeller->id, ['reason' => 'Required license was not provided.'])
            ->assertRedirect('/admin/sellers/view/'.$rejectedSeller->id);
        $this->assertSame('rejected', $rejectedSeller->fresh()->application_status);
        $this->assertSame('rejected', $rejectedSeller->fresh()->status);
        $this->assertFalse($rejectedSeller->fresh()->is_active);
        $this->assertDatabaseHas('seller_logs', [
            'seller_id' => $rejectedSeller->id,
            'actor_id' => $admin->id,
            'action' => 'admin.rejected',
        ]);
        Notification::assertSentToTimes($rejectedSeller, SellerAccountStatusNotification::class, 1);
    }

    public function test_admin_seller_detail_masks_payout_account_and_serves_documents_privately(): void
    {
        Storage::fake('local');
        $admin = User::query()->create([
            'name' => 'Administrator',
            'email' => 'admin@example.test',
            'password_hash' => password_hash('not-used', PASSWORD_DEFAULT),
            'role' => 'admin',
            'status' => 'active',
            'created_at' => now(),
        ]);
        $seller = $this->createSeller([
            'payout_account' => '1234567890',
            'email_verified_at' => now(),
        ]);
        $path = 'seller-documents/'.$seller->id.'/private.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 test');
        $document = SellerDocument::query()->create([
            'seller_id' => $seller->id,
            'document_type' => 'other',
            'storage_path' => $path,
            'original_name' => 'private document.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 14,
            'status' => 'pending',
            'created_at' => now(),
        ]);
        $this->actingAsAdmin($admin);

        $this->get('/admin/sellers')->assertOk()->assertSee($seller->shop_name);
        $this->get('/admin/sellers/view/'.$seller->id)
            ->assertOk()
            ->assertSee('******7890')
            ->assertDontSee('1234567890');
        $this->post('/admin/sellers/approve/'.$seller->id)
            ->assertRedirect('/admin/sellers/view/'.$seller->id);
        $this->assertSame('pending', $seller->fresh()->application_status);
        $this->post('/admin/sellers/document-approve/'.$document->id)
            ->assertRedirect('/admin/sellers/view/'.$seller->id);
        $this->post('/admin/sellers/approve/'.$seller->id)
            ->assertRedirect('/admin/sellers/view/'.$seller->id);
        $this->assertSame('approved', $seller->fresh()->application_status);
        $documentResponse = $this->get('/admin/sellers/document/'.$document->id);
        $documentResponse
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('private', $documentResponse->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $documentResponse->headers->get('Cache-Control'));
    }

    public function test_non_admin_roles_cannot_view_seller_applications(): void
    {
        $staff = User::query()->create([
            'name' => 'Staff',
            'email' => 'staff@example.test',
            'password_hash' => password_hash('not-used', PASSWORD_DEFAULT),
            'role' => 'staff',
            'status' => 'active',
            'created_at' => now(),
        ]);
        $this->actingAsAdmin($staff);

        $this->get('/admin/sellers')->assertForbidden();
    }

    private function applicationInput(array $overrides = []): array
    {
        return array_replace([
            'shop_name' => 'Herbal Shop',
            'owner_name' => 'Seller Owner',
            'category' => 'Herbal',
            'email' => 'seller@example.test',
            'phone' => '+8801712345678',
            'pickup_address' => 'House 1, Dhaka',
            'password' => 'long-secure-password',
            'password_confirmation' => 'long-secure-password',
        ], $overrides);
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
            'status' => 'pending',
            'application_status' => 'pending',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function profileInput(Seller $seller): array
    {
        return [
            'shop_name' => $seller->shop_name,
            'owner_name' => $seller->owner_name,
            'category' => 'Herbal',
            'phone' => $seller->phone,
            'pickup_address' => 'House 1, Dhaka',
        ];
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

<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\SellerLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class SellerPortalController extends Controller
{
    public function dashboard(Request $request)
    {
        $seller = $this->seller($request);
        $products = $seller->products();

        return response()->view('seller.dashboard', [
            'seller' => $seller,
            'documents' => $seller->sellerDocuments()->latest('created_at')->get(),
            'productSummary' => [
                'total' => (clone $products)->count(),
                'pending' => (clone $products)->where('approval_status', 'pending')->count(),
                'approved' => (clone $products)->where('approval_status', 'approved')->where('is_active', true)->count(),
                'low_stock' => (clone $products)->whereColumn('stock', '<=', 'low_stock_threshold')->count(),
            ],
        ]);
    }

    public function profile(Request $request)
    {
        $seller = $this->seller($request);

        return response()->view('seller.profile', [
            'seller' => $seller,
            'documents' => $seller->sellerDocuments()->latest('created_at')->get(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $seller = $this->seller($request);
        $this->assertSellerCanWrite($seller);

        $validated = $request->validate([
            'shop_name' => ['required', 'string', 'min:2', 'max:150'],
            'legal_business_name' => ['nullable', 'string', 'max:150'],
            'owner_name' => ['required', 'string', 'min:2', 'max:120'],
            'category' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:40', 'regex:/^[0-9+()\\-\\s]{7,40}$/'],
            'pickup_address' => ['required', 'string', 'max:2000'],
            'return_address' => ['nullable', 'string', 'max:2000'],
            'city' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:5000'],
            'tax_identifier' => ['nullable', 'string', 'max:100'],
            'trade_license' => ['nullable', 'string', 'max:80'],
            'drug_license' => ['nullable', 'string', 'max:80'],
        ]);

        $phoneChanged = trim($validated['phone']) !== $seller->phone;
        $changed = [];
        foreach ([
            'shop_name', 'legal_business_name', 'owner_name', 'category', 'phone',
            'pickup_address', 'return_address', 'city', 'description',
            'tax_identifier', 'trade_license', 'drug_license',
        ] as $field) {
            $value = trim((string) ($validated[$field] ?? '')) ?: null;
            if ($field === 'shop_name' || $field === 'owner_name' || $field === 'category' || $field === 'phone' || $field === 'pickup_address') {
                $value = trim((string) $validated[$field]);
            }
            if ((string) ($seller->{$field} ?? '') !== (string) ($value ?? '')) {
                $changed[] = $field;
                $seller->{$field} = $value;
            }
        }
        $seller->address = $seller->pickup_address;
        if ($phoneChanged) {
            $seller->phone_verified_at = null;
            $changed[] = 'phone_verification_reset';
        }
        $seller->updated_at = now();
        $seller->save();
        $this->log($seller, 'profile.updated', $changed ? 'Fields changed: '.implode(', ', array_unique($changed)) : 'No profile fields changed.');

        return redirect('/seller/profile')->with('status', 'Seller profile updated.');
    }

    public function updatePayout(Request $request)
    {
        $seller = $this->seller($request);
        if (! Hash::check((string) $request->input('current_password'), $seller->password_hash)) {
            throw ValidationException::withMessages(['current_password' => 'The current password is incorrect.']);
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string', 'max:255'],
            'payout_method' => ['required', 'in:bkash,nagad,bank'],
            'payout_account' => ['required', 'string', 'max:120'],
            'payout_account_name' => ['required', 'string', 'max:120'],
        ]);

        $seller->forceFill([
            'payout_method' => $validated['payout_method'],
            'payout_account' => trim($validated['payout_account']),
            'payout_account_name' => trim($validated['payout_account_name']),
            'payout_verified' => false,
            'updated_at' => now(),
        ])->save();
        $this->log($seller, 'payout_account.updated', 'Payout account details changed; values omitted.');

        return redirect('/seller/profile')->with('status', 'Payout details updated and marked for verification.');
    }

    public function uploadDocument(Request $request)
    {
        $seller = $this->seller($request);
        $this->assertSellerCanWrite($seller);
        $validated = $request->validate([
            'document_type' => ['required', 'in:trade_license,drug_license,tax,identity,other'],
            'document' => ['required', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,webp'],
        ]);
        $file = $validated['document'];
        $path = $file->storeAs(
            'seller-documents/'.$seller->id,
            Str::random(40).'.'.$file->extension(),
            'local'
        );
        if (! $path) {
            throw ValidationException::withMessages(['document' => 'The document could not be stored. Please try again.']);
        }

        try {
            DB::transaction(function () use ($seller, $file, $path, $validated) {
                $seller->sellerDocuments()->create([
                    'document_type' => $validated['document_type'],
                    'storage_path' => $path,
                    'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                    'mime_type' => (string) $file->getMimeType(),
                    'file_size' => (int) $file->getSize(),
                    'status' => 'pending',
                    'created_at' => now(),
                ]);
                $this->log($seller, 'document.uploaded', 'Document type: '.$validated['document_type']);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return redirect('/seller/profile')->with('status', 'Supporting document uploaded securely.');
    }

    public function document(Request $request, int $id)
    {
        $seller = $this->seller($request);
        $document = $seller->sellerDocuments()->findOrFail($id);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($document->storage_path), 404);
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($document->original_name)) ?: 'seller-document';

        $response = response()->file($disk->path($document->storage_path), [
            'Content-Type' => $document->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');

        return $response;
    }

    private function seller(Request $request): Seller
    {
        return $request->attributes->get('seller');
    }

    private function assertSellerCanWrite(Seller $seller): void
    {
        abort_unless(
            $seller->is_active
            && in_array($seller->status, ['pending', 'active'], true)
            && in_array($seller->application_status, ['pending', 'approved'], true),
            403,
            'This seller account cannot make changes in its current status.'
        );
    }

    private function log(Seller $seller, string $action, ?string $detail): void
    {
        SellerLog::query()->create([
            'seller_id' => $seller->id,
            'actor_type' => 'seller',
            'actor_id' => $seller->id,
            'action' => $action,
            'detail' => mb_substr((string) $detail, 0, 255),
            'ip' => substr((string) request()->ip(), 0, 45),
            'created_at' => now(),
        ]);
    }
}

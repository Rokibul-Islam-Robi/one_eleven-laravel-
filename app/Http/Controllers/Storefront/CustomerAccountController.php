<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Prescription;
use App\Models\StorefrontCustomerReview;
use App\Services\PrescriptionService;
use App\Support\Catalog;
use App\Support\CustomerAuth;
use App\Support\Districts;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\Request;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CustomerAccountController extends Controller
{
    private const SECTIONS = ['products', 'prescriptions', 'addresses', 'reviews', 'profile', 'patients'];
    private const GENDERS = ['male', 'female', 'other', 'prefer_not_to_say'];

    public function show(string $section)
    {
        $customer = $this->customer();
        if (! $customer) {
            return redirect(site_base_path() . '/account')->with('auth_error', 'Sign in to view your account.');
        }
        abort_unless(in_array($section, self::SECTIONS, true), 404);

        $data = match ($section) {
            'products' => $this->products($customer),
            'prescriptions' => [
                'prescriptions' => $this->ownedPrescriptionQuery($customer)
                    ->latest('created_at')
                    ->get(),
            ],
            'addresses' => [
                'addresses' => DB::table('customer_addresses')
                    ->where('customer_id', $customer->id)
                    ->orderByDesc('is_default')
                    ->orderByDesc('updated_at')
                    ->get(),
                'divisions' => Districts::byDivision(),
            ],
            'reviews' => $this->reviews($customer),
            'patients' => [
                'patients' => DB::table('customer_patients')
                    ->where('customer_id', $customer->id)
                    ->orderBy('full_name')
                    ->get(),
            ],
            default => [],
        };

        return PageController::render('account-section', [
            'accountSection' => $section,
            'accountCustomer' => $customer,
            'accountData' => $data,
        ])->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function save(Request $request, string $section)
    {
        $customer = $this->customer();
        if (! $customer) {
            return redirect(site_base_path() . '/account')->with('auth_error', 'Sign in to manage your account.');
        }

        return match ($section) {
            'profile' => $this->saveProfile($request, $customer),
            'prescriptions' => $this->uploadPrescription($request, $customer),
            'addresses' => $this->saveAddress($request, $customer),
            'reviews' => $this->saveReview($request, $customer),
            'patients' => $this->savePatient($request, $customer),
            default => abort(404),
        };
    }

    public function uploadAvatar(Request $request)
    {
        $customer = $this->customer();
        if (! $customer) {
            return redirect(site_base_path() . '/account')->with('auth_error', 'Sign in to manage your account.');
        }
        $validated = $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $file = $validated['avatar'];
        $oldPath = $customer->profile_photo_path;
        $path = $file->storeAs(
            'customer-avatars/' . $customer->id,
            bin2hex(random_bytes(16)) . '.' . $file->extension(),
            'local'
        );
        if (! $path) {
            throw ValidationException::withMessages(['avatar' => 'The profile picture could not be stored. Please try again.']);
        }

        try {
            $customer->profile_photo_path = $path;
            if (! $customer->save()) {
                throw ValidationException::withMessages(['avatar' => 'Your profile picture could not be saved. Please try again.']);
            }
        } catch (Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }
        if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return $this->success('profile', 'Profile picture updated.');
    }

    public function avatar()
    {
        $customer = $this->customer();
        abort_unless($customer && $customer->profile_photo_path, 404);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($customer->profile_photo_path), 404);

        $response = response()->file($disk->path($customer->profile_photo_path), [
            'Content-Type' => $disk->mimeType($customer->profile_photo_path) ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');

        return $response;
    }

    public function prescriptionFile(int $id)
    {
        $customer = $this->customer();
        abort_unless($customer, 404);
        $prescription = $this->ownedPrescriptionQuery($customer)
            ->findOrFail($id);
        abort_unless($prescription->file_path, 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($prescription->file_path), 404);

        $response = response()->file($disk->path($prescription->file_path), [
            'Content-Type' => $prescription->mime_type ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');

        return $response;
    }

    public function deletePrescription(int $id)
    {
        $customer = $this->customer();
        if (! $customer) {
            return redirect(site_base_path() . '/account')->with('auth_error', 'Sign in to manage prescriptions.');
        }

        $prescription = $this->ownedPrescriptionQuery($customer)
            ->findOrFail($id);
        abort_unless($prescription->status === 'pending' && $prescription->order_id === null
            && ! DB::table('orders')->where('prescription_id', $prescription->id)->exists(), 403);

        $path = $prescription->file_path;
        $prescription->delete();
        if ($path) {
            Storage::disk('local')->delete($path);
        }

        return $this->success('prescriptions', 'Prescription deleted.');
    }

    public function logout()
    {
        if (! request()->isMethod('post')) {
            abort(405);
        }
        CustomerAuth::logout();

        return redirect(site_base_path() . '/account')->with('auth_flash', 'You have been signed out.');
    }

    private function customer(): ?Customer
    {
        $user = CustomerAuth::current();
        return $user ? Customer::find((int) $user['id']) : null;
    }

    private function saveProfile(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'min:2', 'max:190'],
            'gender' => ['nullable', Rule::in(self::GENDERS)],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $customer->full_name = trim(preg_replace('/\s+/', ' ', $validated['full_name']) ?? '');
        $customer->gender = $validated['gender'] ?? null;
        $customer->date_of_birth = $validated['date_of_birth'] ?? null;
        $customer->save();

        return $this->success('profile', 'Profile updated.');
    }

    private function uploadPrescription(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'prescription' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ]);
        $result = PrescriptionService::store(
            $validated['prescription'],
            $customer->full_name,
            (string) ($customer->phone ?? ''),
            (int) $customer->id,
            trim((string) ($validated['notes'] ?? ''))
        );

        if (isset($result['error'])) {
            $message = PrescriptionService::MESSAGES[$result['error']] ?? 'The prescription could not be saved.';
            throw ValidationException::withMessages(['prescription' => $message]);
        }

        return $this->success('prescriptions', 'Prescription uploaded for review.');
    }

    private function ownedPrescriptionQuery(Customer $customer): EloquentBuilder
    {
        $query = Prescription::query();
        $hasStoreCustomerId = Schema::hasColumn('prescriptions', 'store_customer_id');
        $hasLegacyCustomerId = Schema::hasColumn('prescriptions', 'customer_id')
            && Schema::hasTable('users');
        $email = strtolower(trim((string) $customer->email));
        $phoneVariants = $this->phoneVariants((string) ($customer->phone ?? ''));

        if (! $hasStoreCustomerId && ! $hasLegacyCustomerId && $phoneVariants === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (EloquentBuilder $ownership) use (
            $customer,
            $hasStoreCustomerId,
            $hasLegacyCustomerId,
            $email,
            $phoneVariants
        ) {
            $hasCondition = false;
            if ($hasStoreCustomerId) {
                $ownership->where('prescriptions.store_customer_id', $customer->id);
                $hasCondition = true;
            }
            if ($hasLegacyCustomerId && ($email !== '' || $phoneVariants !== [])) {
                $method = $hasCondition ? 'orWhereExists' : 'whereExists';
                $ownership->{$method}(function (Builder $legacyCustomer) use ($email, $phoneVariants) {
                    $legacyCustomer->selectRaw('1')
                        ->from('users')
                        ->whereColumn('users.id', 'prescriptions.customer_id')
                        ->where('users.role', 'customer')
                        ->where(function (Builder $contact) use ($email, $phoneVariants) {
                            if ($email !== '') {
                                $contact->whereRaw('LOWER(users.email) = ?', [$email]);
                            }
                            if ($phoneVariants !== []) {
                                $method = $email !== '' ? 'orWhereIn' : 'whereIn';
                                $contact->{$method}('users.phone', $phoneVariants);
                            }
                        });
                });
                $hasCondition = true;
            }
            if ($phoneVariants !== []) {
                $method = $hasCondition ? 'orWhereIn' : 'whereIn';
                $ownership->{$method}('prescriptions.phone', $phoneVariants);
            }
        });
    }

    private function saveAddress(Request $request, Customer $customer)
    {
        $operation = (string) $request->input('operation', 'create');
        if ($operation === 'delete') {
            $address = DB::table('customer_addresses')
                ->where('customer_id', $customer->id)
                ->where('id', $request->integer('id'))
                ->first();
            abort_unless($address, 404);
            DB::transaction(function () use ($customer, $address) {
                DB::table('customer_addresses')->where('id', $address->id)->delete();
                if ($address->is_default) {
                    $next = DB::table('customer_addresses')->where('customer_id', $customer->id)->orderBy('id')->value('id');
                    if ($next) {
                        DB::table('customer_addresses')->where('id', $next)->update(['is_default' => true]);
                    }
                }
            });

            return $this->success('addresses', 'Address deleted.');
        }
        if ($operation === 'default') {
            $address = DB::table('customer_addresses')
                ->where('customer_id', $customer->id)
                ->where('id', $request->integer('id'))
                ->first();
            abort_unless($address, 404);
            DB::transaction(function () use ($customer, $address) {
                DB::table('customer_addresses')->where('customer_id', $customer->id)->update(['is_default' => false]);
                DB::table('customer_addresses')->where('id', $address->id)->update(['is_default' => true]);
            });

            return $this->success('addresses', 'Default delivery address updated.');
        }

        $validated = $request->validate([
            'id' => ['nullable', 'integer'],
            'full_name' => ['required', 'string', 'min:2', 'max:190'],
            'phone' => ['required', 'string', 'max:40', 'regex:/^(?:\+?880|0)1[3-9][0-9]{8}$/'],
            'division' => ['required', Rule::in(array_keys(Districts::byDivision()))],
            'district' => ['required', Rule::in(Districts::all())],
            'area' => ['required', 'string', 'max:120'],
            'street_address' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'is_default' => ['nullable', 'boolean'],
        ]);
        if (Districts::divisionOf($validated['district']) !== $validated['division']) {
            throw ValidationException::withMessages(['district' => 'Select a district in the chosen division.']);
        }

        $id = (int) ($validated['id'] ?? 0);
        $address = $id
            ? DB::table('customer_addresses')->where('customer_id', $customer->id)->where('id', $id)->first()
            : null;
        if ($id && ! $address) {
            abort(404);
        }
        $makeDefault = (bool) ($validated['is_default'] ?? false)
            || ! DB::table('customer_addresses')->where('customer_id', $customer->id)->exists();
        $values = [
            'full_name' => trim($validated['full_name']),
            'phone' => trim($validated['phone']),
            'division' => $validated['division'],
            'district' => $validated['district'],
            'area' => trim($validated['area']),
            'street_address' => trim($validated['street_address']),
            'instructions' => trim((string) ($validated['instructions'] ?? '')) ?: null,
            'is_default' => $makeDefault,
            'updated_at' => now(),
        ];
        DB::transaction(function () use ($customer, $id, $values, $makeDefault) {
            if ($makeDefault) {
                DB::table('customer_addresses')->where('customer_id', $customer->id)->update(['is_default' => false]);
            }
            if ($id) {
                DB::table('customer_addresses')->where('customer_id', $customer->id)->where('id', $id)->update($values);
            } else {
                $values['customer_id'] = $customer->id;
                $values['created_at'] = now();
                DB::table('customer_addresses')->insert($values);
            }
        });

        return $this->success('addresses', $id ? 'Address updated.' : 'Address added.');
    }

    private function savePatient(Request $request, Customer $customer)
    {
        $operation = (string) $request->input('operation', 'create');
        $id = $request->integer('id');
        if ($operation === 'delete') {
            $deleted = DB::table('customer_patients')
                ->where('customer_id', $customer->id)
                ->where('id', $id)
                ->delete();
            abort_unless($deleted, 404);

            return $this->success('patients', 'Patient removed.');
        }

        $validated = $request->validate([
            'id' => ['nullable', 'integer'],
            'full_name' => ['required', 'string', 'min:2', 'max:190'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', Rule::in(self::GENDERS)],
            'relationship' => ['required', 'string', 'max:80'],
        ]);
        $values = [
            'full_name' => trim($validated['full_name']),
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'relationship' => trim($validated['relationship']),
            'updated_at' => now(),
        ];

        if ($id) {
            $query = DB::table('customer_patients')->where('customer_id', $customer->id)->where('id', $id);
            abort_unless($query->exists(), 404);
            $query->update($values);
            $message = 'Patient information updated.';
        } else {
            $values['customer_id'] = $customer->id;
            $values['created_at'] = now();
            DB::table('customer_patients')->insert($values);
            $message = 'Patient added.';
        }

        return $this->success('patients', $message);
    }

    private function saveReview(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'string', 'max:50'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'review' => ['required', 'string', 'min:3', 'max:5000'],
        ]);
        $productId = $validated['product_id'];
        abort_unless(Catalog::byId($productId), 404);
        $eligible = $this->orderQuery($customer)
            ->where('orders.status', 'delivered')
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $productId)
            ->exists();
        abort_unless($eligible, 403);

        StorefrontCustomerReview::query()->updateOrCreate(
            ['customer_id' => $customer->id, 'catalog_product_id' => $productId],
            [
                'product_name' => Catalog::byId($productId)['n'],
                'rating' => (int) $validated['rating'],
                'review' => trim($validated['review']),
            ]
        );

        return $this->success('reviews', 'Your review was saved.');
    }

    private function products(Customer $customer): array
    {
        $items = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id');
        $this->whereCustomerOwnsOrders($items, $customer);
        $items = $items
            ->whereNotIn('orders.status', ['cancelled', 'returned'])
            ->orderByDesc('orders.created_at')
            ->select([
                'order_items.product_id', 'order_items.product_name', 'order_items.image_url',
                'order_items.variant_label', 'order_items.price', 'order_items.qty',
                'orders.order_number', 'orders.status', 'orders.created_at',
            ])
            ->paginate(12);

        $reviewedIds = StorefrontCustomerReview::query()
            ->where('customer_id', $customer->id)
            ->pluck('catalog_product_id')
            ->all();
        foreach ($items as $item) {
            $product = Catalog::byId((string) $item->product_id);
            $item->image_url = $product['i'] ?? $item->image_url;
            $item->variant_label = $item->variant_label ?: '';
            $item->can_review = $item->status === 'delivered'
                && $product !== null;
            $item->reviewed = in_array((string) $item->product_id, $reviewedIds, true);
        }

        return ['products' => $items];
    }

    private function reviews(Customer $customer): array
    {
        return [
            'reviews' => StorefrontCustomerReview::query()
                ->where('customer_id', $customer->id)
                ->latest()
                ->paginate(12),
            'reviewableProducts' => $this->reviewableProductsQuery($customer),
        ];
    }

    private function reviewableProductsQuery(Customer $customer)
    {
        $query = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id');
        $this->whereCustomerOwnsOrders($query, $customer);

        return $query
            ->where('orders.status', 'delivered')
            ->select('order_items.product_id', 'order_items.product_name')
            ->distinct()
            ->orderBy('order_items.product_name')
            ->get()
            ->filter(fn ($row) => Catalog::byId((string) $row->product_id) !== null);
    }

    private function orderQuery(Customer $customer): Builder
    {
        $query = DB::table('orders');
        $this->whereCustomerOwnsOrders($query, $customer);

        return $query;
    }

    private function whereCustomerOwnsOrders(Builder $query, Customer $customer): void
    {
        $hasStoreCustomerId = Schema::hasColumn('orders', 'store_customer_id');
        $email = strtolower(trim((string) $customer->email));
        $phoneVariants = $this->phoneVariants((string) ($customer->phone ?? ''));

        if (! $hasStoreCustomerId && $email === '' && $phoneVariants === []) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->where(function (Builder $ownership) use ($customer, $hasStoreCustomerId, $email, $phoneVariants) {
            $hasCondition = false;
            if ($hasStoreCustomerId) {
                $ownership->where('orders.store_customer_id', $customer->id);
                $hasCondition = true;
            }
            if ($email !== '') {
                $method = $hasCondition ? 'orWhereRaw' : 'whereRaw';
                $ownership->{$method}('LOWER(orders.customer_email) = ?', [$email]);
                $hasCondition = true;
            }
            if ($phoneVariants !== []) {
                $method = $hasCondition ? 'orWhereIn' : 'whereIn';
                $ownership->{$method}('orders.shipping_phone', $phoneVariants);
            }
        });
    }

    private function phoneVariants(string $phone): array
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return [];
        }
        $variants = [$phone, $digits, '+' . $digits];
        if (str_starts_with($digits, '8801') && strlen($digits) === 13) {
            $local = '0' . substr($digits, 3);
            $variants[] = $local;
            $variants[] = '+880' . substr($digits, 3);
        } elseif (str_starts_with($digits, '01') && strlen($digits) === 11) {
            $variants[] = '+880' . substr($digits, 1);
            $variants[] = '880' . substr($digits, 1);
        }

        return array_values(array_unique($variants));
    }

    private function success(string $section, string $message)
    {
        return redirect(site_base_path() . '/account/' . $section)->with('account_flash', $message);
    }
}

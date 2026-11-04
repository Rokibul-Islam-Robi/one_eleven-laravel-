@php
\App\Support\PageMeta::$title = 'My Account — bHerb';
$labels = [
    'products' => 'My Products',
    'prescriptions' => 'Prescriptions',
    'addresses' => 'Delivery Address',
    'reviews' => 'Product Review',
    'profile' => 'Profile',
    'patients' => 'Patients',
];
$flash = (string) session('account_flash', '');
session()->forget('account_flash');
$data = $accountData ?? [];
@endphp
<main class="wrap page customer-account">
    <h1>My Account</h1>
    @if ($flash !== '')<p class="auth-flash ok">{{ $flash }}</p>@endif
    @if (session('account_info'))<p class="auth-flash">{{ session('account_info') }}</p>@endif
    @if ($errors->any())
        <div class="auth-flash bad" role="alert">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif
    <div class="customer-account-layout">
        <nav class="customer-account-nav" aria-label="Customer account">
            @foreach ($labels as $key => $label)
                <a class="{{ $accountSection === $key ? 'active' : '' }}" href="account/{{ $key }}" @if ($accountSection === $key) aria-current="page" @endif>
                    <span>{{ $label }}</span>{!! ic('arrow', 'ic-arrow-sm') !!}
                </a>
            @endforeach
            <form method="post" action="account/logout">@csrf
                <button type="submit"><span>Logout</span>{!! ic('arrow', 'ic-arrow-sm') !!}</button>
            </form>
        </nav>

        <section class="customer-account-content">
            @if ($accountSection === 'profile')
                <div class="account-box customer-account-card">
                    <div class="customer-profile-heading">
                        <div>
                            <span class="customer-profile-kicker">ACCOUNT SETTINGS</span>
                            <h2>Profile information</h2>
                            <p>Manage your personal details and profile picture.</p>
                        </div>
                    </div>
                    <div class="customer-profile-photo">
                        <div class="customer-avatar">
                            @if ($accountCustomer->profile_photo_path)
                                <img src="account/avatar" alt="Your profile picture">
                            @else
                                <span aria-label="No profile picture">{!! ic('user', 'ic-act') !!}</span>
                            @endif
                        </div>
                        <div class="customer-profile-photo-details">
                            <h3>Profile picture</h3>
                            <p>Use a clear JPG, PNG or WEBP image (maximum 2 MB).</p>
                            <form method="post" action="account/profile/avatar" enctype="multipart/form-data" class="customer-avatar-form">@csrf
                                <label for="profile-avatar">Choose an image</label>
                                <input id="profile-avatar" type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required>
                                <button class="btn-outline" type="submit">Upload picture</button>
                            </form>
                        </div>
                    </div>
                    <div class="customer-profile-info-heading">
                        <h3>Personal information</h3>
                        <p>Your saved details are used to make checkout easier.</p>
                    </div>
                    <form method="post" action="account/profile" class="checkout-form customer-account-form">@csrf
                        <label>Full name
                            <input name="full_name" value="{{ old('full_name', $accountCustomer->full_name) }}" required maxlength="190">
                        </label>
                        <label>Gender
                            <select name="gender">
                                <option value="">Select</option>
                                @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other', 'prefer_not_to_say' => 'Prefer not to say'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('gender', $accountCustomer->gender) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>Date of birth
                            <input type="date" name="date_of_birth" value="{{ old('date_of_birth', optional($accountCustomer->date_of_birth)->format('Y-m-d')) }}" max="{{ date('Y-m-d') }}">
                        </label>
                        <label>Email address
                            <input type="email" value="{{ $accountCustomer->email }}" readonly>
                            <small>{{ $accountCustomer->email_verified_at ? 'Verified' : 'Not verified' }}. Email changes require verification and are not available yet.</small>
                        </label>
                        <label>Mobile number
                            <input type="tel" value="{{ $accountCustomer->phone }}" readonly>
                            <small>{{ $accountCustomer->phone_verified_at ? 'Verified' : 'Not verified' }}. Mobile changes require OTP verification; no OTP service is configured.</small>
                        </label>
                        <button class="btn-green" type="submit">Update Profile</button>
                    </form>
                </div>
            @elseif ($accountSection === 'products')
                <div class="account-box customer-account-card">
                    <h2>My Products</h2>
                    @if ($data['products']->isEmpty())
                        <p>You do not have any purchased products linked to this account yet.</p>
                    @else
                        <div class="customer-account-list">
                            @foreach ($data['products'] as $item)
                                <article class="customer-account-row">
                                    @if ($item->image_url)<img src="{{ $item->image_url }}" alt="">@endif
                                    <div>
                                        <b>{{ $item->product_name }}</b>
                                        @if ($item->variant_label)<small>{{ $item->variant_label }}</small>@endif
                                        <small>Order {{ $item->order_number ?: '—' }} · {{ ucfirst(str_replace('_', ' ', $item->status)) }} · Qty {{ $item->qty }}</small>
                                        <small>{{ \Illuminate\Support\Carbon::parse($item->created_at)->format('M j, Y') }}</small>
                                        @if ($item->can_review)
                                            <a href="account/reviews?product_id={{ $item->product_id }}">{{ $item->reviewed ? 'Update your review' : 'Write a review' }}</a>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                        {{ $data['products']->links() }}
                    @endif
                </div>
            @elseif ($accountSection === 'prescriptions')
                <div class="account-box customer-account-card">
                    <h2>Prescriptions</h2>
                    <form method="post" action="account/prescriptions" enctype="multipart/form-data" class="checkout-form customer-account-form">@csrf
                        <label>Upload prescription
                            <input type="file" name="prescription" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
                        </label>
                        <small>JPG, PNG, WEBP or PDF; up to 5 MB. Files are stored privately.</small>
                        <label>Notes (optional)<textarea name="notes" rows="3" maxlength="10000">{{ old('notes') }}</textarea></label>
                        <button class="btn-green" type="submit">Upload Prescription</button>
                    </form>
                    @if ($data['prescriptions']->isEmpty())
                        <p>No prescriptions have been uploaded to this account.</p>
                    @else
                        <div class="customer-account-list">
                            @foreach ($data['prescriptions'] as $prescription)
                                <article class="customer-account-row">
                                    <div>
                                        <b>{{ $prescription->original_name ?: 'Prescription' }}</b>
                                        <small>{{ ucfirst($prescription->status) }} · {{ \Illuminate\Support\Carbon::parse($prescription->created_at)->format('M j, Y') }}</small>
                                        @if ($prescription->notes)<small>{{ $prescription->notes }}</small>@endif
                                        @if ($prescription->file_path)<a href="account/prescriptions/{{ $prescription->id }}/file">View prescription</a>@endif
                                    </div>
                                    @if ($prescription->status === 'pending' && ! $prescription->order_id)
                                        <form method="post" action="account/prescriptions/{{ $prescription->id }}/delete">@csrf
                                            <button class="btn-outline" type="submit">Delete</button>
                                        </form>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            @elseif ($accountSection === 'addresses')
                <div class="account-box customer-account-card">
                    <h2>Delivery Address</h2>
                    @foreach ($data['addresses'] as $address)
                        <details class="customer-account-entry">
                            <summary>{{ $address->full_name }} — {{ $address->district }} @if ($address->is_default)<b>Default</b>@endif</summary>
                            <form method="post" action="account/addresses" class="checkout-form customer-account-form">@csrf
                                <input type="hidden" name="operation" value="update">
                                <input type="hidden" name="id" value="{{ $address->id }}">
                                @include('partials.customer-address-fields', ['address' => $address, 'divisions' => $data['divisions']])
                                <label><input type="checkbox" name="is_default" value="1" @checked($address->is_default)> Set as default</label>
                                <button class="btn-green" type="submit">Save Address</button>
                            </form>
                            <div class="acct-actions">
                                @unless ($address->is_default)
                                    <form method="post" action="account/addresses">@csrf
                                        <input type="hidden" name="operation" value="default"><input type="hidden" name="id" value="{{ $address->id }}">
                                        <button class="btn-outline" type="submit">Make default</button>
                                    </form>
                                @endunless
                                <form method="post" action="account/addresses">@csrf
                                    <input type="hidden" name="operation" value="delete"><input type="hidden" name="id" value="{{ $address->id }}">
                                    <button class="btn-outline" type="submit">Delete</button>
                                </form>
                            </div>
                        </details>
                    @endforeach
                    <details class="customer-account-entry" open>
                        <summary>Add a delivery address</summary>
                        <form method="post" action="account/addresses" class="checkout-form customer-account-form">@csrf
                            <input type="hidden" name="operation" value="create">
                            @include('partials.customer-address-fields', ['address' => null, 'divisions' => $data['divisions']])
                            <label><input type="checkbox" name="is_default" value="1" @checked($data['addresses']->isEmpty())> Set as default</label>
                            <button class="btn-green" type="submit">Add Address</button>
                        </form>
                    </details>
                </div>
            @elseif ($accountSection === 'reviews')
                @php
                    $selectedProductId = (string) request()->query('product_id', '');
                    $currentReview = $data['reviews']->getCollection()->firstWhere('catalog_product_id', $selectedProductId);
                @endphp
                <div class="account-box customer-account-card">
                    <h2>Product Review</h2>
                    @if ($data['reviewableProducts']->isNotEmpty())
                        <form method="post" action="account/reviews" class="checkout-form customer-account-form">@csrf
                            <label>Purchased product
                                <select name="product_id" required>
                                    <option value="">Select a delivered product</option>
                                    @foreach ($data['reviewableProducts'] as $product)
                                        <option value="{{ $product->product_id }}" @selected($selectedProductId === (string) $product->product_id)>
                                            {{ $product->product_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                            <label>Rating
                                <select name="rating" required>
                                    @foreach (range(5, 1) as $rating)
                                        <option value="{{ $rating }}" @selected((int) old('rating', $currentReview->rating ?? 5) === $rating)>{{ $rating }} star{{ $rating === 1 ? '' : 's' }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>Your review
                                <textarea name="review" rows="4" minlength="3" maxlength="5000" required>{{ old('review', $currentReview->review ?? '') }}</textarea>
                            </label>
                            <button class="btn-green" type="submit">{{ $currentReview ? 'Update Review' : 'Submit Review' }}</button>
                        </form>
                    @else
                        <p>Reviews can be submitted for products in delivered orders. No eligible purchases were found.</p>
                    @endif
                    @if ($data['reviews']->isEmpty())
                        <p>You have not submitted any reviews yet.</p>
                    @else
                        <div class="customer-account-list">
                            @foreach ($data['reviews'] as $review)
                                <article class="customer-account-row">
                                    <div><b>{{ $review->product_name }}</b>
                                        <small>{{ $review->rating }} / 5 stars · {{ \Illuminate\Support\Carbon::parse($review->created_at)->format('M j, Y') }}</small>
                                        <p>{{ $review->review }}</p>
                                        <a href="account/reviews?product_id={{ $review->catalog_product_id }}">Edit review</a>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                        {{ $data['reviews']->links() }}
                    @endif
                </div>
            @elseif ($accountSection === 'patients')
                <div class="account-box customer-account-card">
                    <h2>Patients</h2>
                    @foreach ($data['patients'] as $patient)
                        <details class="customer-account-entry">
                            <summary>{{ $patient->full_name }} — {{ $patient->relationship }}</summary>
                            <form method="post" action="account/patients" class="checkout-form customer-account-form">@csrf
                                <input type="hidden" name="operation" value="update"><input type="hidden" name="id" value="{{ $patient->id }}">
                                @include('partials.customer-patient-fields', ['patient' => $patient])
                                <button class="btn-green" type="submit">Save Patient</button>
                            </form>
                            <form method="post" action="account/patients">@csrf
                                <input type="hidden" name="operation" value="delete"><input type="hidden" name="id" value="{{ $patient->id }}">
                                <button class="btn-outline" type="submit">Remove Patient</button>
                            </form>
                        </details>
                    @endforeach
                    <details class="customer-account-entry" open>
                        <summary>Add a patient</summary>
                        <form method="post" action="account/patients" class="checkout-form customer-account-form">@csrf
                            <input type="hidden" name="operation" value="create">
                            @include('partials.customer-patient-fields', ['patient' => null])
                            <button class="btn-green" type="submit">Add Patient</button>
                        </form>
                    </details>
                </div>
            @endif
        </section>
    </div>
</main>

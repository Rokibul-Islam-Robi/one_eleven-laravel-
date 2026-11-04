@extends('seller.layout', ['title' => $product ? 'Edit product' : 'Add product'])
@section('content')
<section class="seller-card">
    <h1>{{ $product ? 'Edit product' : 'Add a product' }}</h1>
    <p>Submitted products are hidden from the storefront until an administrator approves them. Editing an approved listing sends it back for review.</p>
    <form method="post" action="{{ $product ? '/seller/products/'.$product->id : '/seller/products' }}">
        @csrf
        <div class="seller-grid">
            <div>
                <label>Product name</label>
                <input name="name" value="{{ old('name', $product?->name) }}" maxlength="255" required>
            </div>
            <div>
                <label>Category</label>
                <select name="category_id" required>
                    <option value="">Select a category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('category_id', $product?->category_id) === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Brand (optional)</label>
                <select name="brand_id">
                    <option value="">No brand</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}" @selected((string) old('brand_id', $product?->brand_id) === (string) $brand->id)>{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>SKU (optional)</label>
                <input name="sku" value="{{ old('sku', $product?->sku) }}" maxlength="80">
            </div>
            <div>
                <label>Price (৳)</label>
                <input type="number" name="price" value="{{ old('price', $product?->price) }}" min="0" max="99999999.99" step="0.01" required>
            </div>
            <div>
                <label>Previous price (optional)</label>
                <input type="number" name="old_price" value="{{ old('old_price', $product?->old_price) }}" min="0" max="99999999.99" step="0.01">
            </div>
            <div>
                <label>Opening stock quantity</label>
                <input type="number" name="stock" value="{{ old('stock', $product?->stock ?? 0) }}" min="0" max="2147483647" required>
            </div>
            <div>
                <label>Low-stock alert threshold</label>
                <input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', $product?->low_stock_threshold ?? 10) }}" min="0" max="2147483647" required>
            </div>
            <div>
                <label>Prescription required?</label>
                <select name="requires_prescription" required>
                    <option value="0" @selected((string) old('requires_prescription', $product?->requires_prescription ?? 0) === '0')>No</option>
                    <option value="1" @selected((string) old('requires_prescription', $product?->requires_prescription ?? 0) === '1')>Yes</option>
                </select>
            </div>
            <div>
                <label>Product image URL (optional)</label>
                <input type="url" name="image" value="{{ old('image', $product?->image) }}" maxlength="255" placeholder="https://...">
            </div>
            <div>
                <label>Short description (optional)</label>
                <input name="short_description" value="{{ old('short_description', $product?->short_description) }}" maxlength="255">
            </div>
        </div>
        <label>Product details</label>
        <textarea name="description" rows="6" minlength="10" maxlength="10000" required>{{ old('description', $product?->description) }}</textarea>
        <div class="seller-product-actions">
            <button type="submit" name="intent" value="draft">Save draft</button>
            <button type="submit" name="intent" value="submit">{{ $product?->approval_status === 'approved' ? 'Submit changes for review' : 'Submit for review' }}</button>
            <a href="/seller/products">Cancel</a>
        </div>
    </form>
</section>
<style>
    .seller-product-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:12px}
    .seller-product-actions a{color:#245d38;font-weight:600;text-decoration:none}
</style>
@endsection

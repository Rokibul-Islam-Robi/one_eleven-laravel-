<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SellerProductController extends Controller
{
    public function index(Request $request)
    {
        $seller = $this->seller($request);
        $products = $seller->products()->latest('updated_at')->paginate(20);

        return response()->view('seller.products.index', compact('seller', 'products'));
    }

    public function create(Request $request)
    {
        return $this->productForm($request, null);
    }

    public function store(Request $request)
    {
        $seller = $this->seller($request);
        $data = $this->validatedProduct($request);
        $this->validateOldPrice($data);
        $intent = $data['intent'];

        $product = DB::transaction(function () use ($seller, $data, $intent) {
            $product = Product::query()->create($this->productValues($seller, $data, $intent));
            $jsonId = $this->nextCatalogId();
            $product->forceFill(['json_id' => $jsonId])->save();
            DB::table('catalog_stock')->insert([
                'json_id' => $jsonId,
                'stock' => (int) $data['stock'],
                'requires_prescription' => (int) ($data['requires_prescription'] ?? 0),
            ]);
            $this->log($seller, $intent === 'submit' ? 'product.submitted' : 'product.drafted', (string) $product->id);

            return $product;
        });

        return redirect('/seller/products')->with(
            'status',
            $intent === 'submit'
                ? 'Product submitted for admin review.'
                : 'Product draft saved.'
        );
    }

    public function edit(Request $request, int $id)
    {
        $seller = $this->seller($request);
        $product = $seller->products()->findOrFail($id);
        abort_if($product->approval_status === 'pending', 409, 'A product awaiting review cannot be edited.');

        return $this->productForm($request, $product);
    }

    public function update(Request $request, int $id)
    {
        $seller = $this->seller($request);
        $data = $this->validatedProduct($request, $id);
        $this->validateOldPrice($data);
        $intent = $data['intent'];

        DB::transaction(function () use ($seller, $data, $intent, $id) {
            $product = $seller->products()->lockForUpdate()->findOrFail($id);
            if ($product->approval_status === 'pending') {
                throw ValidationException::withMessages([
                    'product' => 'A product awaiting review cannot be edited.',
                ]);
            }

            $product->forceFill($this->productValues($seller, $data, $intent, $product))->save();
            $catalogUpdated = DB::table('catalog_stock')->where('json_id', $product->json_id)->update([
                'stock' => (int) $data['stock'],
                'requires_prescription' => (int) ($data['requires_prescription'] ?? 0),
            ]);
            if ($catalogUpdated === 0 && ! DB::table('catalog_stock')->where('json_id', $product->json_id)->exists()) {
                throw new \RuntimeException('The product stock mapping is missing.');
            }
            $this->log($seller, $intent === 'submit' ? 'product.resubmitted' : 'product.updated', (string) $product->id);
        });

        return redirect('/seller/products')->with(
            'status',
            $intent === 'submit'
                ? 'Product changes submitted for admin review.'
                : 'Product draft saved.'
        );
    }

    public function inventory(Request $request)
    {
        $seller = $this->seller($request);
        $products = $seller->products()->latest('updated_at')->paginate(25);
        $movements = DB::table('stock_movements as movements')
            ->join('products', 'products.json_id', '=', 'movements.product_id')
            ->leftJoin('orders', 'orders.id', '=', 'movements.order_id')
            ->where('products.seller_id', $seller->id)
            ->orderByDesc('movements.id')
            ->select([
                'movements.id',
                'movements.delta',
                'movements.type',
                'movements.note',
                'movements.created_at',
                'products.name as product_name',
                'orders.order_number',
            ])
            ->paginate(25, ['*'], 'movement_page');

        return response()->view('seller.products.inventory', compact('seller', 'products', 'movements'));
    }

    public function adjustInventory(Request $request, int $id)
    {
        $seller = $this->seller($request);
        $validated = $request->validate([
            'adjustment' => ['required', 'integer', 'between:-1000000,1000000', 'not_in:0'],
            'note' => ['required', 'string', 'max:255'],
        ]);
        $delta = (int) $validated['adjustment'];
        $note = trim($validated['note']);
        if ($note === '') {
            throw ValidationException::withMessages(['note' => 'Enter a reason for the stock adjustment.']);
        }

        DB::transaction(function () use ($seller, $id, $delta, $note) {
            $product = $seller->products()->lockForUpdate()->findOrFail($id);
            $newStock = (int) $product->stock + $delta;
            if ($newStock < 0 || $newStock > 2_147_483_647) {
                throw ValidationException::withMessages([
                    'adjustment' => 'The adjustment must keep stock between 0 and 2,147,483,647.',
                ]);
            }
            $product->forceFill(['stock' => $newStock, 'updated_at' => now()])->save();

            $stockUpdated = DB::table('catalog_stock')->where('json_id', $product->json_id)->update([
                'stock' => $newStock,
                'requires_prescription' => (int) $product->requires_prescription,
            ]);
            if ($stockUpdated === 0 && ! DB::table('catalog_stock')->where('json_id', $product->json_id)->exists()) {
                throw new \RuntimeException('The product stock mapping is missing.');
            }
            DB::table('stock_movements')->insert([
                'product_id' => (int) $product->json_id,
                'order_id' => null,
                'delta' => $delta,
                'type' => 'manual_adjust',
                'note' => mb_substr($note, 0, 255),
                'created_at' => now(),
            ]);
            $this->log($seller, 'inventory.adjusted', 'Product '.$product->id.' stock changed by '.$delta.'.');
        });

        return redirect('/seller/inventory')->with('status', 'Inventory quantity updated.');
    }

    private function productForm(Request $request, ?Product $product)
    {
        $seller = $this->seller($request);

        return response()->view('seller.products.form', [
            'seller' => $seller,
            'product' => $product,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function validatedProduct(Request $request, ?int $productId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')],
            'sku' => ['nullable', 'string', 'max:80', Rule::unique('products', 'sku')->ignore($productId)],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'old_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'stock' => ['required', 'integer', 'between:0,2147483647'],
            'low_stock_threshold' => ['required', 'integer', 'between:0,2147483647'],
            'requires_prescription' => ['required', 'boolean'],
            'image' => ['nullable', 'url:http,https', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:10000'],
            'intent' => ['required', Rule::in(['draft', 'submit'])],
        ]);
    }

    private function validateOldPrice(array $data): void
    {
        if (isset($data['old_price']) && (float) $data['old_price'] > 0 && (float) $data['old_price'] <= (float) $data['price']) {
            throw ValidationException::withMessages([
                'old_price' => 'The previous price must be greater than the current price.',
            ]);
        }
    }

    private function productValues(Seller $seller, array $data, string $intent, ?Product $product = null): array
    {
        $status = $intent === 'submit' ? 'pending' : 'draft';
        $slug = $this->uniqueSlug((string) $data['name'], $seller, $product);

        return [
            'seller_id' => $seller->id,
            'category_id' => (int) $data['category_id'],
            'brand_id' => isset($data['brand_id']) && $data['brand_id'] !== '' ? (int) $data['brand_id'] : null,
            'name' => trim($data['name']),
            'slug' => $slug,
            'sku' => trim((string) ($data['sku'] ?? '')) ?: null,
            'price' => number_format((float) $data['price'], 2, '.', ''),
            'old_price' => isset($data['old_price']) && $data['old_price'] !== '' ? number_format((float) $data['old_price'], 2, '.', '') : null,
            'stock' => (int) $data['stock'],
            'low_stock_threshold' => (int) $data['low_stock_threshold'],
            'requires_prescription' => (int) $data['requires_prescription'],
            'image' => trim((string) ($data['image'] ?? '')) ?: null,
            'short_description' => trim((string) ($data['short_description'] ?? '')),
            'description' => trim($data['description']),
            'featured' => false,
            'bestseller' => false,
            'trending' => false,
            'new_arrival' => false,
            'approval_status' => $status,
            'rejection_reason' => null,
            'submitted_at' => $intent === 'submit' ? now() : null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'is_active' => false,
            'updated_at' => now(),
            ...($product ? [] : ['created_at' => now()]),
        ];
    }

    private function uniqueSlug(string $name, Seller $seller, ?Product $product): string
    {
        $base = Str::slug($name) ?: 'seller-product';
        $base = mb_substr($base, 0, 255 - strlen('-seller-'.$seller->id));
        $base .= '-seller-'.$seller->id;
        $slug = $base;
        $suffix = 1;
        while (Product::query()->where('slug', $slug)->when($product, fn ($query) => $query->where('id', '!=', $product->id))->exists()) {
            $slug = mb_substr($base, 0, 245).'-'.$suffix++;
        }

        return $slug;
    }

    private function nextCatalogId(): int
    {
        $maxStockId = (int) DB::table('catalog_stock')->lockForUpdate()->max('json_id');
        $maxProductId = (int) DB::table('products')->lockForUpdate()->max('json_id');
        $jsonIds = array_map(fn ($item) => (int) $item['id'], \App\Support\Catalog::all());

        return max($maxStockId, $maxProductId, ...$jsonIds) + 1;
    }

    private function seller(Request $request): Seller
    {
        return $request->attributes->get('seller');
    }

    private function log(Seller $seller, string $action, string $detail): void
    {
        SellerLog::query()->create([
            'seller_id' => $seller->id,
            'actor_type' => 'seller',
            'actor_id' => $seller->id,
            'action' => $action,
            'detail' => mb_substr($detail, 0, 255),
            'ip' => substr((string) request()->ip(), 0, 45),
            'created_at' => now(),
        ]);
    }
}

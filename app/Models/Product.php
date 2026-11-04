<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Table `products` (see database/migrations). */
class Product extends Model
{
    protected $table = 'products';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'price' => 'decimal:2',
            'old_price' => 'decimal:2',
            'stock' => 'integer',
            'low_stock_threshold' => 'integer',
            'requires_prescription' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function inventoryBatches()
    {
        return $this->hasMany(InventoryBatch::class, 'product_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'product_id', 'id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'product_id', 'id');
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id', 'id');
    }

    public function sellerOrderItems()
    {
        return $this->hasMany(SellerOrderItem::class, 'product_id', 'id');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'product_id', 'id');
    }
}

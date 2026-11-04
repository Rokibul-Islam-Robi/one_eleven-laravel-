<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Table `seller_order_items` (see database/migrations). */
class SellerOrderItem extends Model
{
    protected $table = 'seller_order_items';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id', 'id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}

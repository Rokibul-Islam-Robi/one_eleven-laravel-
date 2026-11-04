<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Table `order_items` (see database/migrations). */
class OrderItem extends Model
{
    protected $table = 'order_items';
    public $timestamps = false;
    protected $guarded = [];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function sellerOrderItems()
    {
        return $this->hasMany(SellerOrderItem::class, 'order_item_id', 'id');
    }
}

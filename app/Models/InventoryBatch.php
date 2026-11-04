<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Table `inventory_batches` (see database/migrations). */
class InventoryBatch extends Model
{
    protected $table = 'inventory_batches';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'received_at' => 'date',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}

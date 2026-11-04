<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Table `payment_transactions` (see database/migrations). */
class PaymentTransaction extends Model
{
    protected $table = 'payment_transactions';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}

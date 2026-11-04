<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Table `order_returns` (see database/migrations). */
class OrderReturn extends Model
{
    protected $table = 'order_returns';
    public $timestamps = false;
    protected $guarded = [];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Table `courier_methods` (see database/migrations). */
class CourierMethod extends Model
{
    protected $table = 'courier_methods';
    public $timestamps = false;
    protected $guarded = [];

    public function orders()
    {
        return $this->hasMany(Order::class, 'courier_id', 'id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Table `prescriptions` (see database/migrations). */
class Prescription extends Model
{
    protected $table = 'prescriptions';
    public $timestamps = false;
    protected $guarded = [];

    public function orders()
    {
        return $this->hasMany(Order::class, 'prescription_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id', 'id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Table `coupons` (see database/migrations). */
class Coupon extends Model
{
    protected $table = 'coupons';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'coupon_id', 'id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Table `doctor_referrals` (see database/migrations). */
class DoctorReferral extends Model
{
    protected $table = 'doctor_referrals';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'referred_at' => 'date',
        ];
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
